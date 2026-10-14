<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CandidateAppliedJobsController extends Controller
{
    public function index(Request $request): View
    {
        $applications = $request->user()->jobApplications()
            ->with(['job.company.location', 'job.location'])
            ->latest()
            ->paginate(10)
            ->withQueryString();
        $availableJobIds = Job::query()->publiclyAvailable()
            ->whereIn('id', $applications->getCollection()->pluck('job_id'))
            ->pluck('id')
            ->mapWithKeys(static fn ($id): array => [(int) $id => true])
            ->all();

        return view('candidate.applied-jobs.index', [
            'applications' => $applications,
            'applicationCount' => $request->user()->jobApplications()->count(),
            'availableJobIds' => $availableJobIds,
        ]);
    }
}
