<?php

use App\Models\Industry;
use App\Models\Job;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutVite();
    Storage::fake('local');
});

function deletionTestJob(User $employer, string $suffix): Job
{
    $industry = Industry::query()->create(['name' => 'Deletion Industry '.$suffix, 'slug' => 'deletion-industry-'.$suffix]);
    $location = Location::query()->create(['name' => 'Deletion Location '.$suffix, 'slug' => 'deletion-location-'.$suffix]);
    $company = $employer->companies()->create([
        'name' => 'Deletion Company '.$suffix,
        'slug' => 'deletion-company-'.$suffix,
        'industry_id' => $industry->getKey(),
        'location_id' => $location->getKey(),
    ]);

    return $company->jobs()->create([
        'title' => 'Deletion Role '.$suffix,
        'slug' => 'deletion-role-'.$suffix,
        'description' => 'Recruitment history must remain available.',
        'employment_type' => 'full_time',
        'status' => 'published',
        'published_at' => now(),
        'industry_id' => $industry->getKey(),
        'location_id' => $location->getKey(),
    ]);
}

test('candidate account deletion is blocked without removing shared application history or private files', function () {
    $candidate = User::factory()->candidate()->create();
    $profile = $candidate->candidateProfile()->create([
        'resume_path' => 'candidate-private/resumes/'.$candidate->getKey().'/cv.pdf',
        'profile_photo_path' => 'candidate-private/photos/'.$candidate->getKey().'/photo.png',
    ]);
    Storage::disk('local')->put($profile->resume_path, 'resume');
    Storage::disk('local')->put($profile->profile_photo_path, 'photo');
    $job = deletionTestJob(User::factory()->employer()->create(), 'candidate');
    $application = $candidate->jobApplications()->create(['job_id' => $job->getKey(), 'status' => 'applied']);

    $this->actingAs($candidate)->from(route('profile.edit'))
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrorsIn('userDeletion', 'account');

    $this->assertDatabaseHas('users', ['id' => $candidate->getKey()]);
    $this->assertDatabaseHas('job_applications', ['id' => $application->getKey()]);
    Storage::disk('local')->assertExists($profile->resume_path);
    Storage::disk('local')->assertExists($profile->profile_photo_path);
});

test('employer account deletion is blocked while candidate applications depend on its company and job', function () {
    $employer = User::factory()->employer()->create();
    $job = deletionTestJob($employer, 'employer');
    $candidate = User::factory()->candidate()->create();
    $application = $candidate->jobApplications()->create(['job_id' => $job->getKey(), 'status' => 'applied']);

    $this->actingAs($employer)->from(route('profile.edit'))
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrorsIn('userDeletion', 'account');

    $this->assertDatabaseHas('users', ['id' => $employer->getKey()]);
    $this->assertDatabaseHas('companies', ['id' => $job->company_id]);
    $this->assertDatabaseHas('jobs', ['id' => $job->getKey()]);
    $this->assertDatabaseHas('job_applications', ['id' => $application->getKey()]);
});

test('owner cannot bypass last-owner protection through self-service account deletion', function () {
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)->from(route('profile.edit'))
        ->delete(route('profile.destroy'), ['password' => 'password'])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrorsIn('userDeletion', 'account');

    $this->assertDatabaseHas('users', ['id' => $owner->getKey(), 'role' => 'owner', 'is_suspended' => false]);
});
