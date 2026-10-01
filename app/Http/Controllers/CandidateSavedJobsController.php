<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\SavedJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CandidateSavedJobsController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $savedJobs = $request->user()->savedJobs()
            ->whereHas('job', function ($query) use ($search): void {
                $query->publiclyAvailable();

                if ($search !== '') {
                    $query->where(function ($jobs) use ($search): void {
                        $jobs->where('title', 'like', '%'.$search.'%')
                            ->orWhereHas('company', fn ($companies) => $companies->where('name', 'like', '%'.$search.'%'))
                            ->orWhereHas('location', fn ($locations) => $locations->where('name', 'like', '%'.$search.'%'));
                    });
                }
            })
            ->with(['job.company.location', 'job.category', 'job.industry', 'job.location', 'job.skills'])
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('candidate.saved-jobs.index', [
            'savedJobs' => $savedJobs,
            'savedJobCount' => $request->user()->savedJobs()->whereHas('job', fn ($query) => $query->publiclyAvailable())->count(),
            'search' => $search,
        ]);
    }

    public function store(Request $request, Job $job): RedirectResponse
    {
        abort_unless(Job::query()->publiclyAvailable()->whereKey($job->getKey())->exists(), 404);

        $savedJob = SavedJob::query()->firstOrCreate([
            'user_id' => $request->user()->getKey(),
            'job_id' => $job->getKey(),
        ]);

        return back()->with('status', $savedJob->wasRecentlyCreated ? 'Job saved to your list.' : 'This job is already in your saved jobs.');
    }

    public function destroy(Request $request, SavedJob $savedJob): RedirectResponse
    {
        // Resolve through the authenticated candidate relation to prevent IDOR.
        $request->user()->savedJobs()->whereKey($savedJob->getKey())->firstOrFail()->delete();

        return back()->with('status', 'Job removed from your saved jobs.');
    }
}
