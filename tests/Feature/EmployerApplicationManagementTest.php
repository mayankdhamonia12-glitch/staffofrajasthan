<?php

use App\Models\CandidateProfile;
use App\Models\Industry;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Location;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->withoutVite());

function applicantManagementJob(User $employer, string $title = 'Applicant Test Role', array $attributes = []): Job
{
    static $sequence = 0;
    $sequence++;
    $company = $employer->companies()->create([
        'name' => 'Applicant Company '.$sequence,
        'slug' => 'applicant-company-'.$sequence,
    ]);
    $category = JobCategory::firstOrCreate(['name' => 'Applicant Category'], ['slug' => 'applicant-category']);
    $industry = Industry::firstOrCreate(['name' => 'Applicant Industry'], ['slug' => 'applicant-industry']);
    $location = Location::firstOrCreate(['name' => 'Jaipur', 'state' => 'Rajasthan'], ['slug' => 'applicant-jaipur']);

    return $company->jobs()->create(array_merge([
        'title' => $title,
        'slug' => 'applicant-role-'.$sequence,
        'description' => 'A role used by application-management feature tests.',
        'responsibilities' => 'Work with the hiring team.',
        'requirements' => 'Relevant experience.',
        'employment_type' => 'full_time',
        'experience_level' => 'Mid level',
        'salary_currency' => 'INR',
        'salary_period' => 'yearly',
        'status' => 'published',
        'published_at' => now()->subDay(),
        'job_category_id' => $category->id,
        'industry_id' => $industry->id,
        'location_id' => $location->id,
    ], $attributes));
}

function applicantProfile(User $candidate, array $attributes = []): CandidateProfile
{
    return $candidate->candidateProfile()->create(array_merge([
        'slug' => 'applicant-'.$candidate->id,
        'headline' => 'Registered nurse',
        'about' => 'Experienced in patient care.',
        'city' => 'Jaipur',
        'state' => 'Rajasthan',
        'is_public' => true,
    ], $attributes));
}

test('employer application overview and job applicants include only applications for owned jobs', function () {
    $employer = User::factory()->employer()->create();
    $otherEmployer = User::factory()->employer()->create();
    $job = applicantManagementJob($employer, 'Owned support role');
    $otherJob = applicantManagementJob($otherEmployer, 'Unrelated support role');
    $candidate = User::factory()->candidate()->create(['name' => 'Mira Candidate']);
    $shortlistedCandidate = User::factory()->candidate()->create(['name' => 'Shortlisted Candidate']);
    $otherCandidate = User::factory()->candidate()->create(['name' => 'Other Candidate']);
    $application = $candidate->jobApplications()->create(['job_id' => $job->id, 'status' => 'applied']);
    $shortlistedCandidate->jobApplications()->create(['job_id' => $job->id, 'status' => 'shortlisted']);
    $otherCandidate->jobApplications()->create(['job_id' => $otherJob->id, 'status' => 'applied']);

    $this->actingAs($employer)->get(route('employer.applications.index'))
        ->assertOk()->assertSee('2 total')->assertSee('Mira Candidate')->assertSee('Owned support role')->assertDontSee('Other Candidate')->assertDontSee('Unrelated support role');
    $this->get(route('employer.jobs.applications.index', $job))->assertOk()->assertSee('2 applications')->assertSee('Mira Candidate')->assertSee('Shortlisted Candidate')->assertDontSee('Other Candidate');
    $this->get(route('employer.jobs.applications.index', $otherJob))->assertNotFound();
    $this->get(route('employer.applications.show', $application))->assertOk()->assertSee('Candidate introduction');
});

test('candidate and unrelated employers cannot read or mutate application review data', function () {
    $employer = User::factory()->employer()->create();
    $unrelatedEmployer = User::factory()->employer()->create();
    $job = applicantManagementJob($employer);
    $candidate = User::factory()->candidate()->create();
    $application = $candidate->jobApplications()->create(['job_id' => $job->id, 'status' => 'applied']);

    $this->actingAs($candidate)->get(route('employer.applications.index'))->assertForbidden();
    $this->get(route('employer.applications.show', $application))->assertForbidden();
    $this->patch(route('employer.applications.status', $application), ['status' => 'selected'])->assertForbidden();
    $this->post(route('employer.applications.notes.store', $application), ['body' => 'Unauthorized'])->assertForbidden();

    $this->actingAs($unrelatedEmployer)->get(route('employer.applications.show', $application))->assertNotFound();
    $this->patch(route('employer.applications.status', $application), ['status' => 'selected'])->assertNotFound();
    $this->post(route('employer.applications.notes.store', $application), ['body' => 'Unauthorized'])->assertNotFound();
    expect($application->fresh()->status)->toBe('applied')->and($application->notes()->count())->toBe(0);
});

test('employer can update application status and candidate sees the same status with an audit record', function () {
    $employer = User::factory()->employer()->create();
    $job = applicantManagementJob($employer);
    $candidate = User::factory()->candidate()->create();
    $application = $candidate->jobApplications()->create(['job_id' => $job->id, 'status' => 'applied']);

    $this->actingAs($employer)->patch(route('employer.applications.status', $application), ['status' => 'interview'])
        ->assertRedirect();
    expect($application->fresh()->status)->toBe('interview')
        ->and($application->fresh()->created_at->equalTo($application->created_at))->toBeTrue();
    $this->assertDatabaseHas('job_application_status_history', [
        'job_application_id' => $application->id,
        'changed_by_user_id' => $employer->id,
        'from_status' => 'applied',
        'to_status' => 'interview',
    ]);
    $this->actingAs($candidate)->get(route('candidate.applied-jobs.index'))->assertOk()->assertSee('Interview');

    $this->actingAs($employer)->patch(route('employer.applications.status', $application), ['status' => 'not-a-status'])->assertSessionHasErrors('status');
    expect($application->fresh()->status)->toBe('interview')->and($application->statusHistory()->count())->toBe(1);
});

test('employer notes remain private to the owning employer and are not shown to candidates', function () {
    $employer = User::factory()->employer()->create();
    $otherEmployer = User::factory()->employer()->create();
    $job = applicantManagementJob($employer);
    $candidate = User::factory()->candidate()->create();
    $application = $candidate->jobApplications()->create(['job_id' => $job->id, 'status' => 'applied']);

    $this->actingAs($employer)->post(route('employer.applications.notes.store', $application), ['body' => 'Follow up about portfolio review.'])->assertRedirect();
    $this->assertDatabaseHas('job_application_notes', [
        'job_application_id' => $application->id,
        'employer_user_id' => $employer->id,
        'body' => 'Follow up about portfolio review.',
    ]);
    $this->get(route('employer.applications.show', $application))->assertSee('Follow up about portfolio review.');
    $this->actingAs($otherEmployer)->get(route('employer.applications.show', $application))->assertNotFound();
    $this->actingAs($candidate)->get(route('candidate.applied-jobs.index'))->assertOk()->assertDontSee('Follow up about portfolio review.');
});

test('employer applicant search filters public profile fields and status then paginates results', function () {
    $employer = User::factory()->employer()->create();
    $job = applicantManagementJob($employer, 'Searchable role');
    $matchedCandidate = User::factory()->candidate()->create(['name' => 'Asha Searchable']);
    $matchedProfile = applicantProfile($matchedCandidate, ['headline' => 'Accessible design specialist']);
    $skill = Skill::create(['name' => 'Accessibility testing']);
    $matchedProfile->skills()->attach($skill);
    $otherCandidate = User::factory()->candidate()->create(['name' => 'Zara Another']);
    applicantProfile($otherCandidate, ['headline' => 'Operations specialist']);
    $matchedCandidate->jobApplications()->create(['job_id' => $job->id, 'status' => 'shortlisted']);
    $otherCandidate->jobApplications()->create(['job_id' => $job->id, 'status' => 'applied']);

    $this->actingAs($employer)->get(route('employer.jobs.applications.index', ['job' => $job->id, 'search' => 'accessibility']))
        ->assertOk()->assertSee('Asha Searchable')->assertDontSee('Zara Another');
    $this->get(route('employer.jobs.applications.index', ['job' => $job->id, 'status' => 'shortlisted']))
        ->assertOk()->assertSee('Asha Searchable')->assertDontSee('Zara Another');

    for ($index = 0; $index < 16; $index++) {
        $candidate = User::factory()->candidate()->create(['name' => 'Page Candidate '.$index]);
        $candidate->jobApplications()->create(['job_id' => $job->id, 'status' => 'applied']);
    }
    $this->get(route('employer.jobs.applications.index', $job))->assertOk()->assertSee('Page Candidate 15')->assertDontSee('Page Candidate 0');
    $this->get(route('employer.jobs.applications.index', ['job' => $job->id, 'page' => 2]))->assertOk()->assertSee('Page Candidate 0');
});

test('private candidate profile fields and private resume remain unavailable to employers', function () {
    Storage::fake('local');
    $employer = User::factory()->employer()->create();
    $job = applicantManagementJob($employer);
    $candidate = User::factory()->candidate()->create(['name' => 'Private Applicant', 'email' => 'private-applicant@example.test']);
    $profile = applicantProfile($candidate, [
        'is_public' => false,
        'phone' => '9999999999',
        'address' => '12 Private Lane, Jaipur',
        'resume_path' => 'candidate-private/resumes/private.pdf',
    ]);
    Storage::disk('local')->put($profile->resume_path, 'private cv');
    $application = $candidate->jobApplications()->create(['job_id' => $job->id, 'status' => 'applied']);

    $this->actingAs($employer)->get(route('employer.applications.show', $application))->assertOk()
        ->assertSee('Private candidate profile')
        ->assertDontSee($candidate->email)
        ->assertDontSee('9999999999')
        ->assertDontSee('12 Private Lane')
        ->assertDontSee('candidate-private/resumes/private.pdf');
    $this->get(route('candidate.resume.download'))->assertForbidden();
    Storage::disk('local')->assertExists($profile->resume_path);
});

test('public candidate profile details are shown without contact fields or resume files', function () {
    $employer = User::factory()->employer()->create();
    $job = applicantManagementJob($employer);
    $candidate = User::factory()->candidate()->create(['name' => 'Public Applicant', 'email' => 'public-applicant@example.test']);
    $profile = applicantProfile($candidate, [
        'phone' => '8888888888',
        'address' => '21 Public Street, Jaipur',
        'resume_path' => 'candidate-private/resumes/public.pdf',
        'headline' => 'Registered nurse',
    ]);
    $profile->educations()->create(['qualification' => 'B.Sc.', 'field_of_study' => 'Nursing', 'institution' => 'Rajasthan University']);
    $application = $candidate->jobApplications()->create(['job_id' => $job->id, 'status' => 'applied']);

    $this->actingAs($employer)->get(route('employer.applications.show', $application))->assertOk()
        ->assertSee('Registered nurse')->assertSee('Rajasthan University')
        ->assertDontSee($candidate->email)->assertDontSee('8888888888')->assertDontSee('21 Public Street')->assertDontSee('public.pdf');
});

test('new applications remain unique and use the normalized applied status', function () {
    $employer = User::factory()->employer()->create();
    $job = applicantManagementJob($employer, 'Apply once role');
    $candidate = User::factory()->candidate()->create();

    $this->actingAs($candidate)->post(route('jobs.apply.store', $job), ['cover_letter' => 'My application.'])->assertRedirect();
    $this->post(route('jobs.apply.store', $job), ['cover_letter' => 'A duplicate.'])->assertRedirect();

    $this->assertDatabaseCount('job_applications', 1);
    $this->assertDatabaseHas('job_applications', ['user_id' => $candidate->id, 'job_id' => $job->id, 'status' => 'applied']);
    $this->actingAs($employer)->get(route('employer.applications.index'))->assertSee('1 total')->assertSee('Applied');
});
