<?php

use App\Models\Company;
use App\Models\Industry;
use App\Models\Job;
use App\Models\JobAlert;
use App\Models\JobCategory;
use App\Models\Location;
use App\Models\Skill;
use App\Models\User;
use App\Notifications\JobOpportunityNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->withoutVite());

function stepTenPublicJob(User $employer, string $slug, array $attributes = []): Job
{
    $industry = Industry::firstOrCreate(['name' => 'Step Ten Industry'], ['slug' => 'step-ten-industry']);
    $category = JobCategory::firstOrCreate(['name' => 'Step Ten Category'], ['slug' => 'step-ten-category']);
    $location = Location::firstOrCreate(['name' => 'Jaipur', 'state' => 'Rajasthan'], ['slug' => 'step-ten-jaipur']);
    $company = $employer->companies()->create([
        'name' => 'Step Ten Company '.str_replace('-', ' ', $slug),
        'slug' => 'step-ten-company-'.$slug,
        'industry_id' => $industry->id,
        'location_id' => $location->id,
        'is_visible' => true,
    ]);

    return stepTenJobForCompany($company, $slug, $attributes + [
        'job_category_id' => $category->id,
        'industry_id' => $industry->id,
        'location_id' => $location->id,
    ]);
}

function stepTenJobForCompany(Company $company, string $slug, array $attributes = []): Job
{
    return $company->jobs()->create(array_merge([
        'title' => 'Software Engineer '.$slug,
        'slug' => 'step-ten-job-'.$slug,
        'description' => 'Build useful recruitment software.',
        'requirements' => 'Experience with PHP.',
        'employment_type' => 'full_time',
        'experience_level' => 'mid',
        'salary_currency' => 'INR',
        'salary_period' => 'yearly',
        'status' => 'published',
        'published_at' => now()->subMinute(),
    ], $attributes));
}

test('candidate can manage owned named alerts and see only current public matches', function () {
    $candidate = User::factory()->candidate()->create();
    $employer = User::factory()->employer()->create();
    $matchingJob = stepTenPublicJob($employer, 'jaipur', ['title' => 'PHP Engineer Jaipur']);
    stepTenPublicJob($employer, 'udaipur', ['title' => 'PHP Engineer Udaipur', 'location_id' => Location::create(['name' => 'Udaipur', 'state' => 'Rajasthan', 'slug' => 'step-ten-udaipur'])->id]);

    $location = Location::where('slug', 'step-ten-jaipur')->firstOrFail();
    $this->actingAs($candidate)->post(route('candidate.job-alerts.store'), [
        'name' => 'Jaipur PHP roles', 'keyword' => 'PHP', 'location_id' => $location->id,
        'employment_type' => 'full_time', 'experience_level' => 'mid',
    ])->assertRedirect();

    $alert = JobAlert::query()->where('user_id', $candidate->id)->firstOrFail();
    expect($alert->is_active)->toBeTrue();
    $this->get(route('candidate.job-alerts.index'))->assertOk()->assertSee('Jaipur PHP roles');
    $this->get(route('candidate.job-alerts.show', $alert))->assertOk()->assertSee($matchingJob->title)->assertDontSee('Udaipur');

    $otherCandidate = User::factory()->candidate()->create();
    $this->actingAs($otherCandidate)->get(route('candidate.job-alerts.show', $alert))->assertNotFound();
    $this->put(route('candidate.job-alerts.update', $alert), ['name' => 'Stolen'])->assertNotFound();

    $this->actingAs($candidate)->patch(route('candidate.job-alerts.active', $alert), ['is_active' => false])->assertRedirect();
    expect($alert->fresh()->is_active)->toBeFalse();
    $this->put(route('candidate.job-alerts.update', $alert), ['name' => 'New alert', 'keyword' => 'PHP', 'is_active' => true])->assertRedirect();
    expect($alert->fresh()->name)->toBe('New alert')->and($alert->fresh()->is_active)->toBeTrue();
    $this->delete(route('candidate.job-alerts.destroy', $alert))->assertRedirect(route('candidate.job-alerts.index'));
    $this->assertDatabaseMissing('job_alerts', ['id' => $alert->id]);
});

test('alert filters reject inactive reference data and unsupported filter values', function () {
    $candidate = User::factory()->candidate()->create();
    $inactive = JobCategory::create(['name' => 'Inactive', 'slug' => 'step-ten-inactive', 'is_active' => false]);

    $this->actingAs($candidate)->post(route('candidate.job-alerts.store'), [
        'name' => 'Invalid filters', 'job_category_id' => $inactive->id, 'employment_type' => 'made_up',
    ])->assertSessionHasErrors(['job_category_id', 'employment_type']);
    $this->assertDatabaseCount('job_alerts', 0);
});

test('published job lifecycle sends one private opportunity notification across alerts and company follows', function () {
    $candidate = User::factory()->candidate()->create();
    $employer = User::factory()->employer()->create();
    $job = stepTenPublicJob($employer, 'dedupe', ['status' => 'draft', 'published_at' => null]);
    $candidate->jobAlerts()->create(['name' => 'Engineer alert', 'keyword' => 'Engineer', 'is_active' => true]);
    $candidate->followedCompanies()->attach($job->company_id);

    $job->update(['status' => 'published', 'published_at' => now()]);
    $job->update(['description' => 'More PHP engineer responsibilities']);

    $this->assertDatabaseCount('job_alert_matches', 1);
    $this->assertDatabaseCount('job_notification_deliveries', 1);
    $this->assertDatabaseCount('notifications', 1);
    $notification = $candidate->notifications()->firstOrFail();
    expect($notification->type)->toBe(JobOpportunityNotification::class)
        ->and($notification->data['job_id'])->toBe($job->id);
});

test('job skill matches are evaluated after a draft is published', function () {
    $candidate = User::factory()->candidate()->create();
    $employer = User::factory()->employer()->create();
    $job = stepTenPublicJob($employer, 'skill-event', ['title' => 'Platform specialist', 'status' => 'draft', 'published_at' => null]);
    $candidate->jobAlerts()->create(['name' => 'Kubernetes roles', 'keyword' => 'Kubernetes']);
    $job->skills()->attach(Skill::create(['name' => 'Kubernetes'])->getKey());

    $job->update(['status' => 'published', 'published_at' => now()]);

    $this->assertDatabaseHas('job_alert_matches', ['job_alert_id' => $candidate->jobAlerts()->value('id'), 'job_id' => $job->id]);
    $this->assertDatabaseCount('notifications', 1);
});

test('paused alerts and non-public jobs never create alert matches or opportunity notifications', function () {
    $candidate = User::factory()->candidate()->create();
    $employer = User::factory()->employer()->create();
    $candidate->jobAlerts()->create(['name' => 'Paused', 'keyword' => 'Engineer', 'is_active' => false]);

    stepTenPublicJob($employer, 'closed', ['status' => 'closed']);
    stepTenPublicJob($employer, 'expired', ['application_deadline' => today()->subDay()]);
    $privateCompanyJob = stepTenPublicJob($employer, 'private-company');
    $privateCompanyJob->company->update(['is_visible' => false]);
    stepTenPublicJob($employer, 'paused-alert');

    $this->assertDatabaseCount('job_alert_matches', 0);
    $this->assertDatabaseCount('notifications', 0);
    $this->assertDatabaseCount('job_notification_deliveries', 0);
});

test('only publicly available jobs are matched and private account notifications are removed on deletion', function () {
    $candidate = User::factory()->candidate()->create();
    $employer = User::factory()->employer()->create();
    $candidate->jobAlerts()->create(['name' => 'Engineer roles', 'keyword' => 'Engineer', 'is_active' => true]);

    stepTenPublicJob($employer, 'active-match');
    stepTenPublicJob($employer, 'draft-match', ['status' => 'draft', 'published_at' => null]);
    stepTenPublicJob($employer, 'closed-match', ['status' => 'closed']);
    stepTenPublicJob($employer, 'filled-match', ['is_filled' => true]);
    stepTenPublicJob($employer, 'expired-match', ['application_deadline' => today()->subDay()]);

    $industry = Industry::where('slug', 'step-ten-industry')->firstOrFail();
    $location = Location::where('slug', 'step-ten-jaipur')->firstOrFail();
    $privateCompany = $employer->companies()->create([
        'name' => 'Private Opportunity Employer', 'slug' => 'step-ten-private-company',
        'industry_id' => $industry->id, 'location_id' => $location->id, 'is_visible' => false,
    ]);
    stepTenJobForCompany($privateCompany, 'private-match');

    $this->assertDatabaseCount('job_alert_matches', 1);
    $this->assertDatabaseCount('job_notification_deliveries', 1);
    $notificationId = $candidate->notifications()->firstOrFail()->getKey();
    $candidate->delete();
    $this->assertDatabaseMissing('notifications', ['id' => $notificationId]);
});

test('candidate can follow public companies and only sees followed public companies in their own list', function () {
    $candidate = User::factory()->candidate()->create();
    $otherCandidate = User::factory()->candidate()->create();
    $employer = User::factory()->employer()->create();
    $job = stepTenPublicJob($employer, 'follow');
    $company = $job->company;

    $this->actingAs($candidate)->get(route('companies.show', $company))->assertOk()->assertSee('Follow company');
    $this->post(route('companies.follow', $company))->assertRedirect();
    $this->post(route('companies.follow', $company))->assertRedirect();
    $this->assertDatabaseCount('company_follows', 1);
    $this->get(route('candidate.following.index'))->assertOk()->assertSee($company->name)->assertSee('1 open role');
    $this->get(route('companies.show', $company))->assertSee('Following · Unfollow');
    stepTenJobForCompany($company, 'followed-company-new-role');
    expect($candidate->notifications()->count())->toBe(1);

    $this->actingAs($otherCandidate)->get(route('candidate.following.index'))->assertOk()->assertDontSee($company->name);
    $this->actingAs($candidate);
    $this->delete(route('companies.unfollow', $company))->assertRedirect();
    $this->assertDatabaseMissing('company_follows', ['user_id' => $candidate->id, 'company_id' => $company->id]);

    $this->post(route('companies.follow', $company))->assertRedirect();
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->post(route('admin.users.suspend', $employer), [
        'reason' => 'Employer account is under an active compliance review.',
    ])->assertRedirect();
    $this->actingAs($candidate)->get(route('candidate.following.index'))->assertOk()->assertDontSee($company->name);
    $this->post(route('companies.follow', $company))->assertNotFound();

    $this->actingAs($admin)->post(route('admin.users.reactivate', $employer), [
        'reason' => 'Employer account review is complete and verified.',
    ])->assertRedirect();
    $company->update(['is_visible' => false]);
    $this->get(route('companies.show', $company))->assertNotFound();
});

test('notification inbox is scoped to its owner and supports unread, mark-read, and mark-all actions', function () {
    $candidate = User::factory()->candidate()->create();
    $otherCandidate = User::factory()->candidate()->create();
    $employer = User::factory()->employer()->create();
    $job = stepTenPublicJob($employer, 'inbox');
    $candidate->jobAlerts()->create(['name' => 'Engineer', 'keyword' => 'Engineer']);
    $job->update(['description' => 'Updated engineer opening']);
    $otherCandidate->notify(JobOpportunityNotification::forJob($job));
    $ownNotification = $candidate->notifications()->firstOrFail();

    $this->actingAs($candidate)->get(route('candidate.notifications.index'))->assertOk()->assertSee($job->title)->assertSee('1 unread notification');
    $this->patch(route('candidate.notifications.read', $ownNotification->getKey()))->assertRedirect();
    expect($candidate->unreadNotifications()->count())->toBe(0);
    $this->patch(route('candidate.notifications.read', $otherCandidate->notifications()->firstOrFail()->getKey()))->assertNotFound();

    $job->update(['requirements' => 'Engineer required']);
    // The job is idempotent per recipient; make another notification using a
    // second public job to verify the account-wide action.
    $secondJob = stepTenPublicJob($employer, 'inbox-two');
    $candidate->followedCompanies()->attach($secondJob->company_id);
    $secondJob->update(['requirements' => 'New requirement']);
    $this->post(route('candidate.notifications.read-all'))->assertRedirect();
    expect($candidate->unreadNotifications()->count())->toBe(0);
});

test('application submission notifies the owning employer and status changes notify only the candidate', function () {
    $employer = User::factory()->employer()->create();
    $otherEmployer = User::factory()->employer()->create();
    $candidate = User::factory()->candidate()->create();
    $job = stepTenPublicJob($employer, 'application-notification');

    $this->actingAs($candidate)->post(route('jobs.apply.store', $job), ['cover_letter' => 'Hello'])->assertRedirect();
    $this->post(route('jobs.apply.store', $job), ['cover_letter' => 'Duplicate'])->assertRedirect();
    $this->assertDatabaseCount('notifications', 1);
    expect($employer->notifications()->firstOrFail()->data['event'])->toBe('new_job_application');

    $application = $candidate->jobApplications()->firstOrFail();
    $this->actingAs($employer)->patch(route('employer.applications.status', $application), ['status' => 'interview'])->assertRedirect();
    $this->assertDatabaseCount('notifications', 2);
    expect($candidate->notifications()->firstOrFail()->data['status'])->toBe('interview')
        ->and($otherEmployer->notifications()->count())->toBe(0);
    $this->actingAs($candidate)->get(route('candidate.notifications.index'))->assertOk()->assertSee('Application status updated');
    $this->actingAs($employer)->get(route('employer.notifications.index'))->assertOk()->assertSee('New job application')->assertSee('Review application');
});
