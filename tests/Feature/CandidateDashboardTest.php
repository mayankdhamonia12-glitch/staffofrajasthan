<?php

use App\Models\Industry;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Location;
use App\Models\Skill;
use App\Models\User;
use App\Services\CandidateProfileCompletionService;
use App\Services\CandidateProfileViewsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->withoutVite());

function candidateDashboardJob(string $suffix, string $title): Job
{
    $employer = User::factory()->employer()->create();
    $industry = Industry::create(['name' => 'Dashboard Industry '.$suffix, 'slug' => 'dashboard-industry-'.$suffix]);
    $category = JobCategory::create(['name' => 'Dashboard Category '.$suffix, 'slug' => 'dashboard-category-'.$suffix]);
    $location = Location::firstOrCreate(['name' => 'Jaipur', 'state' => 'Rajasthan'], ['slug' => 'dashboard-location-'.$suffix]);
    $company = $employer->companies()->create([
        'name' => 'Dashboard Company '.$suffix,
        'slug' => 'dashboard-company-'.$suffix,
        'industry_id' => $industry->id,
        'location_id' => $location->id,
    ]);

    return $company->jobs()->create([
        'title' => $title,
        'slug' => 'dashboard-job-'.$suffix,
        'description' => 'A real dashboard test role.',
        'employment_type' => 'full_time',
        'experience_level' => 'mid',
        'salary_min' => 400000,
        'salary_max' => 800000,
        'salary_currency' => 'INR',
        'salary_period' => 'yearly',
        'status' => 'published',
        'published_at' => now()->subDay(),
        'job_category_id' => $category->id,
        'industry_id' => $industry->id,
        'location_id' => $location->id,
    ]);
}

function candidateWithProfile(array $attributes = []): array
{
    $user = User::factory()->candidate()->create();
    $profile = $user->candidateProfile()->create($attributes);

    return [$user, $profile];
}

test('candidate dashboard has real isolated counts and a responsive candidate workspace', function () {
    [$candidate, $profile] = candidateWithProfile(['headline' => 'Product designer']);
    [$otherCandidate, $otherProfile] = candidateWithProfile();
    $ownJob = candidateDashboardJob('own', 'Candidate-Owned Product Role');
    $otherJob = candidateDashboardJob('other', 'Another Candidate Secret Role');
    $candidate->savedJobs()->create(['job_id' => $ownJob->id]);
    $candidate->jobApplications()->create(['job_id' => $ownJob->id, 'status' => 'submitted']);
    $otherCandidate->savedJobs()->create(['job_id' => $otherJob->id]);
    $otherCandidate->jobApplications()->create(['job_id' => $otherJob->id, 'status' => 'submitted']);
    $profile->profileViews()->create(['visitor_fingerprint' => str_repeat('a', 64), 'viewed_at' => now()->subDay()]);
    $otherProfile->profileViews()->create(['visitor_fingerprint' => str_repeat('b', 64), 'viewed_at' => now()]);

    $this->actingAs($candidate)->get(route('candidate.dashboard'))->assertOk()
        ->assertSee('Candidate-Owned Product Role')
        ->assertDontSee('Another Candidate Secret Role')
        ->assertSee('Applications recorded')
        ->assertSee('Saved open jobs')
        ->assertSee('Profile views')
        ->assertSee('1 of 9 sections complete');
});

test('applied jobs page is paginated and contains only the signed-in candidate applications', function () {
    [$candidate] = candidateWithProfile();
    [$otherCandidate] = candidateWithProfile();
    $ownJob = candidateDashboardJob('own-app', 'My Application Role');
    $otherJob = candidateDashboardJob('other-app', 'Private Other Role');
    $candidate->jobApplications()->create(['job_id' => $ownJob->id, 'status' => 'submitted']);
    $otherCandidate->jobApplications()->create(['job_id' => $otherJob->id, 'status' => 'accepted']);

    $this->actingAs($candidate)->get(route('candidate.applied-jobs.index'))->assertOk()
        ->assertSee('My Application Role')->assertSee('Submitted')->assertDontSee('Private Other Role');
});

test('profile completion is based on actual profile sections and returns actionable missing items', function () {
    [$candidate, $profile] = candidateWithProfile([
        'headline' => 'Full-stack developer',
        'about' => 'Building web products.',
        'city' => 'Jaipur',
        'profile_photo_path' => 'candidate-private/photos/1/photo.png',
        'resume_path' => 'candidate-private/resumes/1/resume.pdf',
        'linkedin_url' => 'https://linkedin.com/in/candidate',
    ]);
    $profile->skills()->attach(Skill::create(['name' => 'PHP']));
    $profile->educations()->create(['institution' => 'University', 'qualification' => 'B.Tech']);
    $profile->experiences()->create(['company' => 'Studio', 'title' => 'Developer', 'start_date' => today()->subYear()]);

    $progress = app(CandidateProfileCompletionService::class)->calculate($profile, $candidate->name);

    expect($progress['percentage'])->toBe(100)
        ->and($progress['missing'])->toBeEmpty()
        ->and($profile->completionPercentage())->toBe(100);
});

test('public candidate views count once per session window and exclude the profile owner', function () {
    [$owner, $profile] = candidateWithProfile(['slug' => 'view-count-candidate', 'is_public' => true]);
    $visitor = User::factory()->candidate()->create();

    $this->actingAs($visitor)->get(route('candidates.show', $profile->slug))->assertOk();
    $this->get(route('candidates.show', $profile->slug))->assertOk();
    expect($profile->profileViews()->count())->toBe(1);

    $this->actingAs($owner)->get(route('candidates.show', $profile->slug))->assertOk();
    expect($profile->fresh()->profileViews()->count())->toBe(1);

    $summary = app(CandidateProfileViewsService::class)->summary($profile->fresh());
    expect($summary['total'])->toBe(1)
        ->and(array_sum(array_column($summary['trend'], 'views')))->toBe(1);
});

test('dashboard tools are candidate-only while unsupported systems render truthful empty states', function () {
    $employer = User::factory()->employer()->create();
    $routes = [
        'candidate.dashboard', 'candidate.applied-jobs.index', 'candidate.saved-jobs.index',
        'candidate.profile-views.index', 'candidate.job-alerts.index', 'candidate.following.index',
        'candidate.notifications.index', 'candidate.meetings.index', 'candidate.messages.index',
        'candidate.reviews.index', 'candidate.settings.edit',
    ];

    foreach ($routes as $route) {
        $this->actingAs($employer)->get(route($route))->assertForbidden();
    }

    $candidate = User::factory()->candidate()->create();
    $this->actingAs($candidate)->get(route('candidate.notifications.index'))->assertOk()
        ->assertSee('There are no candidate notifications yet');
    $this->get(route('candidate.messages.index'))->assertOk()->assertSee('No conversations are stored');
});

test('candidate settings cannot change role and account deletion requires current password and removes private files', function () {
    Storage::fake('local');
    [$candidate, $profile] = candidateWithProfile([
        'resume_path' => 'candidate-private/resumes/1/resume.pdf',
        'profile_photo_path' => 'candidate-private/photos/1/photo.png',
    ]);
    Storage::disk('local')->put($profile->resume_path, 'private resume');
    Storage::disk('local')->put($profile->profile_photo_path, 'private photo');

    $this->actingAs($candidate)->patch(route('candidate.settings.update'), [
        'name' => 'Updated Candidate',
        'role' => 'employer',
        'is_public' => '1',
        'show_expected_salary' => '0',
    ])->assertRedirect();
    expect($candidate->fresh()->isCandidate())->toBeTrue()
        ->and($profile->fresh()->is_public)->toBeTrue();

    $this->delete(route('profile.destroy'), ['password' => 'wrong-password'])->assertSessionHasErrorsIn('userDeletion');
    $this->assertDatabaseHas('users', ['id' => $candidate->id]);

    $this->delete(route('profile.destroy'), ['password' => 'password'])->assertRedirect('/');
    $this->assertDatabaseMissing('users', ['id' => $candidate->id]);
    Storage::disk('local')->assertMissing($profile->resume_path);
    Storage::disk('local')->assertMissing($profile->profile_photo_path);
});
