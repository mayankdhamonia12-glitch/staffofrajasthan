<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JobApplicationController extends Controller
{
    public function create(Request $request, Job $job): View
    {
        abort_unless(Job::query()->publiclyAvailable()->whereKey($job->getKey())->exists(), 404);

        $application = $request->user()->jobApplications()->where('job_id', $job->getKey())->first();
        $job->load(['company.location', 'category']);

        return view('jobs.apply', compact('job', 'application'));
    }

    public function store(Request $request, Job $job): RedirectResponse
    {
        abort_unless(Job::query()->publiclyAvailable()->whereKey($job->getKey())->exists(), 404);

        $data = $request->validate([
            'cover_letter' => ['nullable', 'string', 'max:5000'],
        ]);
        $application = JobApplication::query()->firstOrCreate(
            ['user_id' => $request->user()->getKey(), 'job_id' => $job->getKey()],
            ['cover_letter' => $data['cover_letter'] ?? null, 'status' => 'submitted'],
        );

        return redirect()->route('jobs.apply', $job)->with(
            'status',
            $application->wasRecentlyCreated
                ? 'Your application has been recorded. Employer review tools will be available in a later milestone.'
                : 'You have already applied for this job.',
        );
    }
}
