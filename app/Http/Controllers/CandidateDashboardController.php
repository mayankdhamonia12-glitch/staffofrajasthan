<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class CandidateDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $recentSavedJobs = $user->savedJobs()
            ->whereHas('job', fn ($query) => $query->publiclyAvailable())
            ->with(['job.company.location', 'job.category', 'job.location', 'job.skills'])
            ->latest()
            ->limit(3)
            ->get();

        return view('candidate.dashboard', [
            'savedJobCount' => $user->savedJobs()->whereHas('job', fn ($query) => $query->publiclyAvailable())->count(),
            'applicationCount' => $user->jobApplications()->count(),
            'recentSavedJobs' => $recentSavedJobs,
        ]);
    }
}
