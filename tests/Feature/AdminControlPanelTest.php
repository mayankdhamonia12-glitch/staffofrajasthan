<?php

use App\Models\AdminAuditLog;
use App\Models\Company;
use App\Models\CompanyReview;
use App\Models\Industry;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\JobCategory;
use App\Models\Location;
use App\Models\PlatformSetting;
use App\Models\Skill;
use App\Models\User;
use App\ReviewStatus;
use App\Services\AccountManagementService;
use App\UserRole;
use Illuminate\Validation\ValidationException;

beforeEach(fn () => $this->withoutVite());

function adminPanelCompany(User $employer, string $key, array $overrides = []): Company
{
    $industry = Industry::query()->create(['name' => 'Admin Industry '.$key, 'slug' => 'admin-industry-'.$key]);
    $location = Location::query()->create(['name' => 'Admin Location '.$key, 'slug' => 'admin-location-'.$key]);

    return $employer->companies()->create(array_merge([
        'name' => 'Admin Company '.$key, 'slug' => 'admin-company-'.$key,
        'industry_id' => $industry->id, 'location_id' => $location->id,
        'description' => 'Company information for administration tests.', 'is_visible' => true,
    ], $overrides));
}

function adminPanelJob(Company $company, string $key, array $overrides = []): Job
{
    return $company->jobs()->create(array_merge([
        'title' => 'Admin Role '.$key, 'slug' => 'admin-role-'.$key,
        'description' => 'A complete job listing for control panel tests.',
        'employment_type' => 'full_time', 'location_id' => $company->location_id,
        'industry_id' => $company->industry_id, 'status' => 'published', 'published_at' => now()->subDay(),
    ], $overrides));
}

test('admin and owner routes are separated and only owners may promote or revoke administrators', function () {
    $candidate = User::factory()->candidate()->create();
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->owner()->create();

    $this->actingAs($candidate)->get(route('admin.dashboard'))->assertForbidden();
    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('live snapshot');
    $this->get(route('admin.settings.edit'))->assertForbidden();
    $this->get(route('admin.audit.index'))->assertForbidden();
    $this->patch(route('admin.users.role', $candidate), ['role' => 'admin'])->assertForbidden();

    $this->actingAs($owner)->patch(route('admin.users.role', $candidate), ['role' => 'admin'])->assertRedirect();
    expect($candidate->fresh()->role)->toBe(UserRole::Admin);
    $this->patch(route('admin.users.role', $candidate), ['role' => 'candidate'])->assertRedirect();
    expect($candidate->fresh()->role)->toBe(UserRole::Candidate)
        ->and(AdminAuditLog::query()->where('action', 'user.role_changed')->count())->toBe(2);
    $this->patch(route('admin.users.role', $owner), ['role' => 'candidate'])->assertForbidden();
    $this->patch(route('admin.users.role', $candidate), ['role' => 'owner'])->assertSessionHasErrors('role');
});

test('suspension blocks account access and hides employer content without deleting records', function () {
    $admin = User::factory()->admin()->create();
    $employer = User::factory()->employer()->create();
    $company = adminPanelCompany($employer, 'suspend');
    $job = adminPanelJob($company, 'suspend');
    $candidate = User::factory()->candidate()->create();
    $candidate->candidateProfile()->create(['slug' => 'admin-suspended-candidate', 'is_public' => true]);

    $this->actingAs($admin)->post(route('admin.users.suspend', $employer), ['reason' => 'Repeatedly posted misleading job information.'])->assertRedirect();
    expect($employer->fresh()->isSuspended())->toBeTrue();
    $this->actingAs($employer->fresh());
    $this->get(route('employer.dashboard'))->assertRedirect(route('account.suspended'));
    $this->actingAs($candidate);
    $this->get(route('companies.show', $company))->assertNotFound();
    $this->get(route('jobs.show', $job))->assertNotFound();
    expect($job->fresh())->not->toBeNull();
    $this->actingAs($admin);
    $this->post(route('admin.users.reactivate', $employer), ['reason' => 'Appeal reviewed and access restored.'])->assertRedirect();
    expect($employer->fresh()->isSuspended())->toBeFalse();

    $this->post(route('admin.users.suspend', $candidate), ['reason' => 'Policy review pending for this account.'])->assertRedirect();
    $this->get(route('candidates.show', $candidate->candidateProfile->slug))->assertNotFound();
    $this->actingAs($candidate->fresh());
    $this->get(route('candidate.dashboard'))->assertRedirect(route('account.suspended'));
    $this->get(route('account.suspended'))->assertOk()->assertSee('Account access is paused');
    $this->post(route('logout'))->assertRedirect();
});

test('the last active owner cannot be suspended through the management service', function () {
    $owner = User::factory()->owner()->create();

    expect(fn () => app(AccountManagementService::class)->suspend($owner, $owner, 'Attempt to suspend last owner.'))
        ->toThrow(ValidationException::class);
    expect($owner->fresh()->isSuspended())->toBeFalse();
});

test('job and company moderation are audited and preserve related applications', function () {
    $admin = User::factory()->admin()->create();
    $employer = User::factory()->employer()->create();
    $candidate = User::factory()->candidate()->create();
    $company = adminPanelCompany($employer, 'moderate');
    $job = adminPanelJob($company, 'moderate');
    $application = $candidate->jobApplications()->create(['job_id' => $job->id, 'status' => 'submitted', 'cover_letter' => 'Private letter body.']);

    $this->actingAs($admin)->patch(route('admin.jobs.moderate', $job), ['status' => 'closed', 'reason' => 'Listing requires employer verification.'])->assertRedirect();
    expect($job->fresh()->status)->toBe('closed')->and(JobApplication::query()->whereKey($application->id)->exists())->toBeTrue();
    $this->patch(route('admin.companies.visibility', $company), ['is_visible' => '0', 'reason' => 'Company details need verification.'])->assertRedirect();
    expect($company->fresh()->is_visible)->toBeFalse()
        ->and(AdminAuditLog::query()->where('action', 'job.moderated')->exists())->toBeTrue()
        ->and(AdminAuditLog::query()->where('action', 'company.visibility_changed')->exists())->toBeTrue();

    $this->actingAs($employer)->patch(route('employer.companies.update', $company), ['name' => $company->name, 'is_visible' => true])->assertRedirect();
    expect($company->fresh()->is_visible)->toBeFalse();
});

test('application oversight is read only and never renders private cover letters or candidate email', function () {
    $admin = User::factory()->admin()->create();
    $employer = User::factory()->employer()->create();
    $candidate = User::factory()->candidate()->create(['email' => 'private-candidate@example.test']);
    $job = adminPanelJob(adminPanelCompany($employer, 'application'), 'application');
    $application = $candidate->jobApplications()->create(['job_id' => $job->id, 'status' => 'submitted', 'cover_letter' => 'UNIQUE_PRIVATE_COVER_LETTER']);

    $this->actingAs($admin)->get(route('admin.applications.index'))->assertOk()
        ->assertSee($candidate->name)->assertDontSee('private-candidate@example.test')->assertDontSee('UNIQUE_PRIVATE_COVER_LETTER');
    $this->get(route('admin.applications.show', $application))->assertOk()
        ->assertSee($job->title)->assertDontSee('private-candidate@example.test')->assertDontSee('UNIQUE_PRIVATE_COVER_LETTER');
    $this->patch(route('admin.applications.show', $application))->assertMethodNotAllowed();
});

test('account overview does not disclose candidate profile contact data or resume paths', function () {
    $admin = User::factory()->admin()->create();
    $candidate = User::factory()->candidate()->create();
    $candidate->candidateProfile()->create(['phone' => 'PRIVATE_PHONE_123', 'address' => 'PRIVATE_HOME_ADDRESS', 'resume_path' => 'private/resumes/secret.pdf']);

    $this->actingAs($admin)->get(route('admin.users.show', $candidate))->assertOk()
        ->assertSee($candidate->name)->assertDontSee('PRIVATE_PHONE_123')->assertDontSee('PRIVATE_HOME_ADDRESS')->assertDontSee('private/resumes/secret.pdf');
});

test('owner-only platform settings are typed, audited, and applied to public pages', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->owner()->create();
    $this->actingAs($admin)->get(route('admin.settings.edit'))->assertForbidden();
    $this->actingAs($owner)->get(route('admin.settings.edit'))->assertOk();
    $this->patch(route('admin.settings.update'), [
        'site_name' => 'Rajasthan Careers', 'contact_email' => 'help@example.test', 'contact_phone' => '01410000000',
        'timezone' => 'Asia/Kolkata', 'locale' => 'hi', 'api_key' => 'must-not-be-stored',
    ])->assertRedirect();
    expect(PlatformSetting::query()->firstOrFail()->site_name)->toBe('Rajasthan Careers')
        ->and(PlatformSetting::query()->firstOrFail()->getAttributes())->not->toHaveKey('api_key')
        ->and(AdminAuditLog::query()->where('action', 'platform.settings_updated')->exists())->toBeTrue();
    $this->get(route('home'))->assertOk()->assertSee('Rajasthan Careers');
    $this->patch(route('admin.settings.update'), ['site_name' => 'Bad timezone', 'timezone' => 'Not/A_Timezone', 'locale' => 'xx'])->assertSessionHasErrors(['timezone', 'locale']);
    expect(PlatformSetting::query()->firstOrFail()->site_name)->toBe('Rajasthan Careers');
});

test('reference data can be deactivated without deleting existing relations and inactive skills cannot be selected', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->owner()->create();
    $employer = User::factory()->employer()->create();
    $candidate = User::factory()->candidate()->create();
    $company = adminPanelCompany($employer, 'reference');
    $category = JobCategory::query()->create(['name' => 'Existing category', 'slug' => 'existing-category']);
    $skill = Skill::query()->create(['name' => 'Legacy skill']);
    $job = adminPanelJob($company, 'reference', ['job_category_id' => $category->id]);

    $this->actingAs($candidate)->get(route('admin.reference-data.index'))->assertForbidden();
    $this->actingAs($admin)->get(route('admin.reference-data.index'))->assertOk();
    $this->patch(route('admin.reference-data.active', ['type' => 'categories', 'id' => $category->id]))->assertRedirect();
    $this->patch(route('admin.reference-data.active', ['type' => 'skills', 'id' => $skill->id]))->assertRedirect();
    $this->patch(route('admin.reference-data.update', ['type' => 'categories', 'id' => $category->id]), ['name' => 'Updated existing category'])->assertRedirect();
    expect($job->fresh()->job_category_id)->toBe($category->id)->and($category->fresh()->is_active)->toBeFalse();
    expect($category->fresh()->name)->toBe('Updated existing category');
    $this->actingAs($candidate)->post(route('candidate.skills.store'), ['name' => 'Legacy skill'])->assertSessionHasErrors('name');
    expect($owner->isOwner())->toBeTrue();
});

test('review moderation records a required note and audit while owner cannot moderate own company reviews', function () {
    $admin = User::factory()->admin()->create();
    $employer = User::factory()->employer()->create();
    $candidate = User::factory()->candidate()->create();
    $company = adminPanelCompany($employer, 'review');
    $review = $company->reviews()->create(['user_id' => $candidate->id, 'rating' => 5, 'title' => 'A clear review', 'body' => 'The process was clear and the company responded quickly.', 'status' => ReviewStatus::Pending]);

    $this->actingAs($admin)->patch(route('admin.company-reviews.moderate', $review), ['status' => 'approved'])->assertSessionHasErrors('moderation_note');
    $this->patch(route('admin.company-reviews.moderate', $review), ['status' => 'approved', 'moderation_note' => 'Content follows review guidelines.'])->assertRedirect();
    expect($review->fresh()->status)->toBe(ReviewStatus::Approved)
        ->and(AdminAuditLog::query()->where('action', 'company_review.moderated')->exists())->toBeTrue();

    $owner = User::factory()->owner()->create();
    $ownedCompany = adminPanelCompany($owner, 'owned-review');
    $ownedReview = $ownedCompany->reviews()->create(['user_id' => $candidate->id, 'rating' => 4, 'title' => 'Owner review', 'body' => 'This review is pending and should not be moderated by its owner.', 'status' => ReviewStatus::Pending]);
    $this->actingAs($owner)->patch(route('admin.company-reviews.moderate', $ownedReview), ['status' => 'approved', 'moderation_note' => 'Should be forbidden.'])->assertForbidden();
    expect($ownedReview->fresh()->status)->toBe(ReviewStatus::Pending);
});

test('audit log is owner-only and audit records reject model-level updates and deletion', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->owner()->create();
    $log = AdminAuditLog::query()->create(['actor_user_id' => $owner->id, 'action' => 'test.event', 'summary' => 'Audit event.']);

    $this->actingAs($admin)->get(route('admin.audit.index'))->assertForbidden();
    $this->actingAs($owner)->get(route('admin.audit.index'))->assertOk()->assertSee('Audit event.');
    expect(fn () => $log->update(['summary' => 'Altered']))->toThrow(LogicException::class);
    expect(fn () => $log->delete())->toThrow(LogicException::class);
    expect(AdminAuditLog::query()->findOrFail($log->id)->summary)->toBe('Audit event.');
});
