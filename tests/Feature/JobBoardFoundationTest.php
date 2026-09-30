<?php

use App\Models\Industry;
use App\Models\JobCategory;
use App\Models\Location;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('the job board domain relates an employer company to its jobs and skills', function () {
    $employer = User::factory()->employer()->create();
    $industry = Industry::create(['name' => 'Information Technology', 'slug' => 'information-technology']);
    $location = Location::create(['name' => 'Jaipur', 'slug' => 'jaipur']);
    $category = JobCategory::create(['name' => 'IT & Software', 'slug' => 'it-software']);
    $company = $employer->companies()->create(['name' => 'Acme Technologies', 'slug' => 'acme-technologies', 'industry_id' => $industry->id, 'location_id' => $location->id]);
    $job = $company->jobs()->create(['title' => 'Laravel Developer', 'slug' => 'laravel-developer', 'description' => 'Build reliable Laravel applications.', 'employment_type' => 'full_time', 'status' => 'draft', 'job_category_id' => $category->id, 'industry_id' => $industry->id, 'location_id' => $location->id]);
    $job->skills()->attach(Skill::create(['name' => 'Laravel']));

    expect($job->company->is($company))->toBeTrue()
        ->and($job->category->is($category))->toBeTrue()
        ->and($job->skills->pluck('name')->all())->toBe(['Laravel'])
        ->and(config('queue.connections.database.table'))->toBe('queue_jobs');
});

test('the queue migration no longer competes with the recruitment jobs table', function () {
    expect(
        Schema::hasTable('queue_jobs')
            && Schema::hasTable('jobs')
    )->toBeTrue();
});
