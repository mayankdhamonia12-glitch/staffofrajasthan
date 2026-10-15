<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use App\Notifications\NewJobApplicationNotification;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        try {
            $application = DB::transaction(function () use ($request, $job, $data): JobApplication {
                $application = JobApplication::query()->firstOrCreate(
                    ['user_id' => $request->user()->getKey(), 'job_id' => $job->getKey()],
                    ['cover_letter' => $data['cover_letter'] ?? null, 'status' => 'applied'],
                );

                if ($application->wasRecentlyCreated) {
                    $employer = $job->company()->with('user')->firstOrFail()->user;
                    if ($employer instanceof User && $employer->isEmployer()) {
                        $application->setRelation('job', $job);
                        $employer->notify(new NewJobApplicationNotification($application));
                    }
                }

                return $application;
            });
        } catch (UniqueConstraintViolationException $exception) {
            $application = $request->user()->jobApplications()->where('job_id', $job->getKey())->first();
            if (! $application) {
                throw $exception;
            }
        }

        return redirect()->route('jobs.apply', $job)->with(
            'status',
            $application->wasRecentlyCreated
                ? 'Your application has been recorded. You can track its status from your Applied Jobs dashboard.'
                : 'You have already applied for this job.',
        );
    }
}
