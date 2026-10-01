<?php

use App\Models\Industry;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\QueryException;

beforeEach(fn () => $this->withoutVite());

function savedJobsFixture(array $attributes = []): Job
{
    static $sequence = 0;
    $sequence++;

    $employer = User::factory()->employer()->create();
    $location = Location::firstOrCreate(['name' => $attributes['location'] ?? 'Jaipur', 'state' => 'Rajasthan'], ['slug' => 'saved-location-'.$sequence]);
    $industry = Industry::firstOrCreate(['name' => $attributes['industry'] ?? 'Technology'], ['slug' => 'saved-industry-'.$sequence]);
    $category = JobCategory::firstOrCreate(['name' => $attributes['category'] ?? 'IT & Software'], ['slug' => 'saved-category-'.$sequence]);
    $company = $employer->companies()->create([
        'name' => $attributes['company'] ?? 'Saved Fixture Company '.$sequence,
        'slug' => 'saved-company-'.$sequence,
        'industry_id' => $industry->id,
        'location_id' => $location->id,
    ]);

    return $company->jobs()->create(array_merge([
        'title' => $attributes['title'] ?? 'Saved Fixture Job '.$sequence,
        'slug' => 'saved-job-'.$sequence,
        'description' => 'A real fixture description for the job detail page.',
        'responsibilities' => 'Build and maintain useful products.',
        'requirements' => 'Relevant experience and communication skills.',
        'employment_type' => 'full_time',
        'experience_level' => 'Mid level',
        'salary_min' => 400000,
        'salary_max' => 800000,
        'salary_currency' => 'INR',
        'salary_period' => 'yearly',
        'status' => 'published',
        'published_at' => now()->subDay(),
        'job_category_id' => $category->id,
        'industry_id' => $industry->id,
        'location_id' => $location->id,
    ], $attributes['job'] ?? []));
}

test('candidate can save a current job and view it in saved jobs and dashboard counts', function () {
    $candidate = User::factory()->candidate()->create();
    $job = savedJobsFixture();

    $this->actingAs($candidate)
        ->post(route('candidate.jobs.save', $job))
        ->assertRedirect();

    $this->assertDatabaseHas('saved_jobs', ['user_id' => $candidate->id, 'job_id' => $job->id]);

    $this->get(route('candidate.saved-jobs.index'))
        ->assertOk()
        ->assertSee($job->title)
        ->assertSee('1 open job saved');

    $this->get(route('jobs.show', $job))->assertSee('Saved · Remove');

    $this->get(route('candidate.dashboard'))
        ->assertOk()
        ->assertSee('Saved open jobs')
        ->assertSee('Recently saved jobs')
        ->assertSee('>1</p>', false);
});

test('duplicate saves are idempotent and also prevented by the database unique key', function () {
    $candidate = User::factory()->candidate()->create();
    $job = savedJobsFixture();

    $this->actingAs($candidate)->post(route('candidate.jobs.save', $job))->assertRedirect();
    $this->post(route('candidate.jobs.save', $job))->assertRedirect();

    $this->assertDatabaseCount('saved_jobs', 1);
    expect($candidate->savedJobs()->where('job_id', $job->id)->count())->toBe(1);
    expect(fn () => $candidate->savedJobs()->create(['job_id' => $job->id]))->toThrow(QueryException::class);
});

test('guest and non-candidate users cannot save or browse candidate saved jobs', function () {
    $job = savedJobsFixture();

    $this->post(route('candidate.jobs.save', $job))->assertRedirect(route('login'));
    $this->get(route('candidate.saved-jobs.index'))->assertRedirect(route('login'));

    $this->actingAs(User::factory()->employer()->create())
        ->post(route('candidate.jobs.save', $job))
        ->assertForbidden();

    $this->get(route('candidate.saved-jobs.index'))->assertForbidden();
});

test('candidate cannot save jobs that are not publicly available', function () {
    $candidate = User::factory()->candidate()->create();
    $draft = savedJobsFixture(['job' => ['status' => 'draft']]);
    $future = savedJobsFixture(['job' => ['published_at' => now()->addDay()]]);
    $filled = savedJobsFixture(['job' => ['is_filled' => true]]);

    $this->actingAs($candidate)->post(route('candidate.jobs.save', $draft))->assertNotFound();
    $this->post(route('candidate.jobs.save', $future))->assertNotFound();
    $this->post(route('candidate.jobs.save', $filled))->assertNotFound();
    $this->assertDatabaseCount('saved_jobs', 0);
});

test('candidate can remove their own saved job but cannot remove another candidates record', function () {
    $candidate = User::factory()->candidate()->create();
    $otherCandidate = User::factory()->candidate()->create();
    $job = savedJobsFixture();
    $otherJob = savedJobsFixture();
    $ownSavedJob = $candidate->savedJobs()->create(['job_id' => $job->id]);
    $otherSavedJob = $otherCandidate->savedJobs()->create(['job_id' => $otherJob->id]);

    $this->actingAs($candidate)
        ->from(route('candidate.saved-jobs.index'))
        ->delete(route('candidate.saved-jobs.destroy', $ownSavedJob))
        ->assertRedirect(route('candidate.saved-jobs.index'));
    $this->assertDatabaseMissing('saved_jobs', ['id' => $ownSavedJob->id]);

    $this->delete(route('candidate.saved-jobs.destroy', $otherSavedJob))->assertNotFound();
    $this->assertDatabaseHas('saved_jobs', ['id' => $otherSavedJob->id, 'user_id' => $otherCandidate->id]);
});

test('saved jobs page searches and paginates current listings', function () {
    $candidate = User::factory()->candidate()->create();
    $matching = savedJobsFixture(['title' => 'Jaipur Laravel Engineer']);
    $notMatching = savedJobsFixture(['title' => 'Udaipur Accountant']);
    $candidate->savedJobs()->createMany([['job_id' => $matching->id], ['job_id' => $notMatching->id]]);

    $this->actingAs($candidate)
        ->get(route('candidate.saved-jobs.index', ['search' => 'Laravel']))
        ->assertOk()
        ->assertSee('Jaipur Laravel Engineer')
        ->assertDontSee('Udaipur Accountant');
});

test('job applications send guests to login and let candidates apply once', function () {
    $job = savedJobsFixture(['title' => 'Apply for this role']);

    $this->get(route('jobs.apply', $job))->assertRedirect(route('login'));

    $candidate = User::factory()->candidate()->create();
    $this->actingAs($candidate)
        ->get(route('jobs.apply', $job))
        ->assertOk()
        ->assertSee('Cover letter');

    $this->post(route('jobs.apply.store', $job), ['cover_letter' => 'I would be a strong fit for this opportunity.'])
        ->assertRedirect(route('jobs.apply', $job));
    $this->assertDatabaseHas('job_applications', [
        'user_id' => $candidate->id,
        'job_id' => $job->id,
        'status' => 'submitted',
    ]);

    $this->post(route('jobs.apply.store', $job), ['cover_letter' => 'Duplicate application'])
        ->assertRedirect(route('jobs.apply', $job));
    $this->assertDatabaseCount('job_applications', 1);

    $this->get(route('jobs.show', $job))->assertSee('Application recorded');
    $this->get(route('candidate.dashboard'))->assertSee('Applications recorded')->assertSee('>1</p>', false);
});

test('employers cannot access candidate job application flow', function () {
    $job = savedJobsFixture();

    $this->actingAs(User::factory()->employer()->create())
        ->get(route('jobs.apply', $job))
        ->assertForbidden();
});

test('job detail page has SEO metadata, real structured data, company details, and related public jobs', function () {
    $job = savedJobsFixture(['title' => 'SEO Verified Laravel Engineer', 'company' => 'Jaipur Software Works']);
    $related = savedJobsFixture(['title' => 'Related Software Role']);
    savedJobsFixture(['title' => 'Unpublished Related Role', 'job' => ['status' => 'draft']]);

    $response = $this->get(route('jobs.show', $job))
        ->assertOk()
        ->assertSee('<title>SEO Verified Laravel Engineer | Staff of Rajasthan</title>', false)
        ->assertSee('<link rel="canonical" href="'.route('jobs.show', $job).'">', false)
        ->assertSee('application/ld+json', false)
        ->assertSee('JobPosting')
        ->assertSee('Jaipur Software Works')
        ->assertSee($job->company->industry->name)
        ->assertSee($job->company->location->name)
        ->assertSee('Related opportunities')
        ->assertSee($related->title)
        ->assertDontSee('Unpublished Related Role');

    $relatedSection = substr($response->getContent(), (int) strpos($response->getContent(), 'Related opportunities'));
    expect($relatedSection)->not->toContain('SEO Verified Laravel Engineer');
});

test('future, filled, and expired jobs are not exposed on detail routes', function () {
    $future = savedJobsFixture(['job' => ['published_at' => now()->addDay()]]);
    $filled = savedJobsFixture(['job' => ['is_filled' => true]]);
    $expired = savedJobsFixture(['job' => ['application_deadline' => today()->subDay()]]);

    $this->get(route('jobs.show', $future))->assertNotFound();
    $this->get(route('jobs.show', $filled))->assertNotFound();
    $this->get(route('jobs.show', $expired))->assertNotFound();
});
