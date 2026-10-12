<?php

use App\Livewire\Employer\Jobs\Index;
use App\Models\Company;
use App\Models\Industry;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Location;
use App\Models\Skill;
use App\Models\User;
use Livewire\Livewire;

beforeEach(fn () => $this->withoutVite());

/** @return array{User, Company, Industry, Location} */
function employerManagementCompany(array $attributes = []): array
{
    static $sequence = 0;
    $sequence++;
    $employer = $attributes['employer'] ?? User::factory()->employer()->create();
    $industry = Industry::create(['name' => 'Management Industry '.$sequence, 'slug' => 'management-industry-'.$sequence, 'is_active' => true]);
    $location = Location::create(['name' => 'Jaipur '.$sequence, 'state' => 'Rajasthan', 'slug' => 'management-location-'.$sequence, 'is_active' => true]);
    $company = $employer->companies()->create([
        'name' => $attributes['name'] ?? 'Management Company '.$sequence,
        'slug' => $attributes['slug'] ?? 'management-company-'.$sequence,
        'industry_id' => $attributes['industry_id'] ?? $industry->id,
        'location_id' => $attributes['location_id'] ?? $location->id,
        'description' => 'A company profile for recruitment.',
        'is_visible' => $attributes['is_visible'] ?? true,
    ]);

    return [$employer, $company, $industry, $location];
}

function employerManagementJob(Company $company, array $attributes = []): Job
{
    static $sequence = 0;
    $sequence++;
    $industry = Industry::find($company->industry_id);
    $location = Location::find($company->location_id);

    return $company->jobs()->create(array_merge([
        'title' => 'Managed Role '.$sequence,
        'slug' => 'managed-role-'.$company->getKey().'-'.$sequence,
        'description' => 'A specific, useful description for this role.',
        'employment_type' => 'full_time',
        'location_id' => $location?->id,
        'industry_id' => $industry?->id,
        'status' => 'published',
        'published_at' => now()->subDay(),
        'is_filled' => false,
    ], $attributes));
}

function employerJobPayload(Company $company, array $attributes = []): array
{
    return array_merge([
        'company_id' => $company->id,
        'title' => 'Rajasthan Product Designer',
        'description' => 'Design simple, useful products with our team.',
        'responsibilities' => 'Partner with product and engineering.',
        'requirements' => 'Experience with product design.',
        'industry_id' => $company->industry_id,
        'location_id' => $company->location_id,
        'employment_type' => 'full_time',
        'experience_level' => 'mid',
        'salary_min' => '800000',
        'salary_max' => '1200000',
        'salary_period' => 'yearly',
        'vacancies' => '2',
        'application_deadline' => today()->addDays(30)->toDateString(),
        'skills' => [],
    ], $attributes);
}

test('employer dashboard shows only real owned job and application statistics', function () {
    [$employer, $company] = employerManagementCompany();
    $active = employerManagementJob($company, ['title' => 'My active listing']);
    employerManagementJob($company, ['title' => 'My draft listing', 'status' => 'draft', 'published_at' => null]);
    employerManagementJob($company, ['title' => 'My closed listing', 'status' => 'closed']);
    employerManagementJob($company, ['title' => 'My expired listing', 'application_deadline' => today()->subDay()]);
    [, $otherCompany] = employerManagementCompany();
    employerManagementJob($otherCompany, ['title' => 'Another employer listing']);

    $candidate = User::factory()->candidate()->create();
    $candidate->jobApplications()->create(['job_id' => $active->id, 'status' => 'submitted']);
    $candidate->jobApplications()->create(['job_id' => $otherCompany->jobs()->firstOrFail()->id, 'status' => 'submitted']);

    $response = $this->actingAs($employer)->get(route('employer.dashboard'))
        ->assertOk()
        ->assertSee('Total jobs')
        ->assertSee('Published now')
        ->assertSee('Applications received')
        ->assertSee('My active listing')
        ->assertDontSee('Another employer listing');

    expect($response->getContent())->toContain('>4</p>')
        ->and($response->getContent())->toContain('>1</p>')
        ->and($response->getContent())->toContain('>2</p>');
});

test('employer job management routes reject candidates and administrators', function () {
    $candidate = User::factory()->candidate()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($candidate)->get(route('employer.dashboard'))->assertForbidden();
    $this->actingAs($candidate)->get(route('employer.jobs.index'))->assertForbidden();
    $this->actingAs($admin)->get(route('employer.jobs.create'))->assertForbidden();
});

test('employer can save a draft and publish a complete job with real taxonomy and skills', function () {
    [$employer, $company, $industry, $location] = employerManagementCompany();
    $category = JobCategory::create(['name' => 'Product Design', 'slug' => 'product-design-management', 'is_active' => true]);
    $skill = Skill::create(['name' => 'Design systems']);

    $this->actingAs($employer)->post(route('employer.jobs.store'), employerJobPayload($company, [
        'intent' => 'draft',
        'job_category_id' => $category->id,
        'skills' => [$skill->id],
        'status' => 'published',
        'is_featured' => '1',
        'is_urgent' => '1',
        'is_filled' => '1',
        'salary_currency' => 'USD',
    ]))->assertRedirect();

    $draft = $company->jobs()->where('title', 'Rajasthan Product Designer')->firstOrFail();
    expect($draft->status)->toBe('draft')
        ->and($draft->published_at)->toBeNull()
        ->and($draft->is_featured)->toBeFalse()
        ->and($draft->is_urgent)->toBeFalse()
        ->and($draft->is_filled)->toBeFalse()
        ->and($draft->salary_currency)->toBe('INR')
        ->and($draft->industry_id)->toBe($industry->id)
        ->and($draft->location_id)->toBe($location->id)
        ->and($draft->skills->modelKeys())->toBe([$skill->id]);

    $this->get(route('jobs.index'))->assertOk()->assertDontSee('Rajasthan Product Designer');

    $this->actingAs($employer)->post(route('employer.jobs.store'), employerJobPayload($company, [
        'title' => 'Published Product Designer',
        'intent' => 'publish',
        'job_category_id' => $category->id,
        'skills' => [$skill->id],
    ]))->assertRedirect();

    $published = $company->jobs()->where('title', 'Published Product Designer')->firstOrFail();
    expect($published->status)->toBe('published')->and($published->published_at)->not->toBeNull();
    $this->get(route('jobs.index'))->assertOk()->assertSee('Published Product Designer');
});

test('employer workspace and job forms render with the shared dashboard navigation', function () {
    [$employer, $company] = employerManagementCompany();

    $this->actingAs($employer)->get(route('employer.dashboard'))->assertOk()->assertSee('Employer menu')->assertSee('Company profiles');
    $this->get(route('employer.companies.index'))->assertOk()->assertSee('Company Profiles')->assertSee($company->name);
    $this->get(route('employer.companies.edit', $company))->assertOk()->assertSee('Edit Company Profile')->assertSee('Public contact');
    $this->get(route('employer.jobs.create'))->assertOk()->assertSee('Job details')->assertSee($company->name)->assertSee('Description and requirements');

    $this->post(route('employer.jobs.store'), employerJobPayload($company, ['intent' => 'draft']))->assertRedirect();
    $job = $company->jobs()->latest('id')->firstOrFail();
    $this->get(route('employer.jobs.edit', $job))->assertOk()->assertSee($job->title)->assertSee('Save changes');
});

test('job validation rejects invalid salary ranges, forged company ids, and incomplete companies', function () {
    [$employer, $ownedCompany] = employerManagementCompany();
    [, $otherCompany] = employerManagementCompany();

    $this->actingAs($employer)->from(route('employer.jobs.create'))->post(route('employer.jobs.store'), employerJobPayload($ownedCompany, [
        'intent' => 'draft',
        'salary_min' => '1500000',
        'salary_max' => '500000',
    ]))->assertRedirect(route('employer.jobs.create'))->assertSessionHasErrors('salary_max');

    $this->actingAs($employer)->post(route('employer.jobs.store'), employerJobPayload($otherCompany, ['intent' => 'draft']))
        ->assertSessionHasErrors('company_id');
    expect(Job::query()->ownedByEmployer($employer)->count())->toBe(0);

    [$unreadyEmployer, $unreadyCompany] = employerManagementCompany(['is_visible' => false]);
    $this->actingAs($unreadyEmployer)->post(route('employer.jobs.store'), employerJobPayload($unreadyCompany, ['intent' => 'publish']))
        ->assertSessionHasErrors('company_id');
    expect($unreadyCompany->jobs()->count())->toBe(0);
});

test('employer can edit owned jobs without changing status or privileged fields and history stays attached', function () {
    [$employer, $company] = employerManagementCompany();
    $job = employerManagementJob($company, ['title' => 'Stable role']);
    $candidate = User::factory()->candidate()->create();
    $application = $candidate->jobApplications()->create(['job_id' => $job->id, 'status' => 'submitted']);
    $saved = $candidate->savedJobs()->create(['job_id' => $job->id]);

    $this->actingAs($employer)->patch(route('employer.jobs.update', $job), employerJobPayload($company, [
        'intent' => 'save',
        'title' => 'Stable role revised',
        'description' => 'A revised description that remains available to applicants.',
        'status' => 'draft',
        'is_featured' => '1',
        'is_urgent' => '1',
        'is_filled' => '1',
        'published_at' => now()->addYear()->toDateTimeString(),
        'salary_currency' => 'USD',
    ]))->assertRedirect();

    expect($job->fresh()->title)->toBe('Stable role revised')
        ->and($job->fresh()->status)->toBe('published')
        ->and($job->fresh()->is_featured)->toBeFalse()
        ->and($job->fresh()->is_urgent)->toBeFalse()
        ->and($job->fresh()->is_filled)->toBeFalse()
        ->and($job->fresh()->salary_currency)->toBe('INR')
        ->and($application->fresh()->job_id)->toBe($job->id)
        ->and($saved->fresh()->job_id)->toBe($job->id);
});

test('employers cannot edit, duplicate, or transition another employer job', function () {
    [$owner] = employerManagementCompany();
    [, $company] = employerManagementCompany();
    $job = employerManagementJob($company);
    $candidate = User::factory()->candidate()->create();

    $this->actingAs($owner)->get(route('employer.jobs.edit', $job))->assertNotFound();
    $this->actingAs($owner)->patch(route('employer.jobs.update', $job), employerJobPayload($company, ['intent' => 'save']))->assertNotFound();
    $this->actingAs($owner)->post(route('employer.jobs.duplicate', $job))->assertNotFound();
    $this->actingAs($owner)->patch(route('employer.jobs.lifecycle', $job), ['action' => 'close'])->assertNotFound();
    $this->actingAs($candidate)->post(route('employer.jobs.duplicate', $job))->assertForbidden();
});

test('duplicating a listing creates a new draft without applications or saved jobs', function () {
    [$employer, $company] = employerManagementCompany();
    $job = employerManagementJob($company, ['title' => 'Original role']);
    $skill = Skill::create(['name' => 'Duplicate skill']);
    $job->skills()->attach($skill);
    $candidate = User::factory()->candidate()->create();
    $candidate->jobApplications()->create(['job_id' => $job->id, 'status' => 'submitted']);
    $candidate->savedJobs()->create(['job_id' => $job->id]);

    $this->actingAs($employer)->post(route('employer.jobs.duplicate', $job))->assertRedirect();
    $copy = $company->jobs()->where('title', 'Original role (Copy)')->firstOrFail();

    expect($copy->status)->toBe('draft')
        ->and($copy->is_featured)->toBeFalse()
        ->and($copy->published_at)->toBeNull()
        ->and($copy->applications()->count())->toBe(0)
        ->and($copy->savedBy()->count())->toBe(0)
        ->and($copy->skills->modelKeys())->toBe([$skill->id]);
    expect($job->applications()->count())->toBe(1)->and($job->savedBy()->count())->toBe(1);
});

test('close, fill, and reopen transitions control public visibility safely', function () {
    [$employer, $company] = employerManagementCompany();
    $job = employerManagementJob($company, ['title' => 'Lifecycle role']);
    $candidate = User::factory()->candidate()->create();
    $application = $candidate->jobApplications()->create(['job_id' => $job->id, 'status' => 'submitted']);
    $saved = $candidate->savedJobs()->create(['job_id' => $job->id]);

    $this->get(route('jobs.show', $job))->assertOk();
    $this->actingAs($employer)->patch(route('employer.jobs.lifecycle', $job), ['action' => 'close'])->assertRedirect();
    expect($job->fresh()->status)->toBe('closed');
    $this->get(route('jobs.index'))->assertOk()->assertDontSee('Lifecycle role');
    $this->get(route('jobs.show', $job))->assertNotFound();

    $this->actingAs($employer)->patch(route('employer.jobs.lifecycle', $job), ['action' => 'reopen'])->assertRedirect();
    expect($job->fresh()->status)->toBe('published')->and($job->fresh()->is_filled)->toBeFalse();
    $this->actingAs($employer)->patch(route('employer.jobs.lifecycle', $job), ['action' => 'filled'])->assertRedirect();
    expect($job->fresh()->is_filled)->toBeTrue();
    $this->get(route('jobs.index'))->assertOk()->assertDontSee('Lifecycle role');
    $this->get(route('jobs.show', $job))->assertNotFound();

    $this->actingAs($employer)->patch(route('employer.jobs.lifecycle', $job), ['action' => 'reopen'])->assertRedirect();
    expect($job->fresh()->is_filled)->toBeFalse();
    expect($application->fresh()->job_id)->toBe($job->id)->and($saved->fresh()->job_id)->toBe($job->id);
});

test('expired or private company listings stay out of public search and cannot be reopened until eligible', function () {
    [$employer, $company] = employerManagementCompany();
    $expired = employerManagementJob($company, ['title' => 'Expired employer role', 'application_deadline' => today()->subDay()]);
    $closed = employerManagementJob($company, ['title' => 'Needs deadline update', 'status' => 'closed', 'application_deadline' => today()->subDay()]);

    $this->get(route('jobs.index'))->assertOk()->assertDontSee('Expired employer role')->assertDontSee('Needs deadline update');
    $this->actingAs($employer)->patch(route('employer.jobs.lifecycle', $closed), ['action' => 'reopen'])->assertSessionHasErrors('application_deadline');

    $company->update(['is_visible' => false]);
    $this->get(route('home'))->assertOk()->assertDontSee('Expired employer role');
    $this->get(route('jobs.show', $expired))->assertNotFound();
});

test('employer job directory supports reactive search, filters, sorting, and pagination', function () {
    [$employer, $company] = employerManagementCompany();
    employerManagementJob($company, ['title' => 'Searchable operations lead']);
    employerManagementJob($company, ['title' => 'Other draft role', 'status' => 'draft']);
    [, $otherCompany] = employerManagementCompany();
    employerManagementJob($otherCompany, ['title' => 'Private employer role']);

    $this->actingAs($employer)->get(route('employer.jobs.index'))->assertOk()->assertSee('Searchable operations lead')->assertDontSee('Private employer role');
    Livewire::test(Index::class)
        ->set('keyword', 'operations')
        ->assertSee('Searchable operations lead')
        ->assertDontSee('Other draft role')
        ->set('keyword', '')
        ->set('status', 'draft')
        ->assertSee('Other draft role')
        ->assertDontSee('Searchable operations lead');
});
