<?php

namespace App\Http\Controllers;

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

        return view('candidate.applied-jobs.index', [
            'applications' => $applications,
            'applicationCount' => $request->user()->jobApplications()->count(),
        ]);
    }
}
