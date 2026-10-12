<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployerDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $employer = $request->user();
        $jobs = Job::query()->ownedByEmployer($employer);

        $statistics = [
            'total' => (clone $jobs)->count(),
            'active' => (clone $jobs)->publiclyAvailable()->count(),
            'drafts' => (clone $jobs)->where('status', 'draft')->count(),
            'closed' => (clone $jobs)->where(function ($query): void {
                $query->where('status', 'closed')
                    ->orWhere('is_filled', true)
                    ->orWhere(function ($expired): void {
                        $expired->where('status', 'published')->whereDate('application_deadline', '<', today());
                    });
            })->count(),
            'applications' => JobApplication::query()
                ->whereHas('job', fn ($query) => $query->ownedByEmployer($employer))
                ->count(),
        ];

        $recentJobs = (clone $jobs)
            ->with(['company.location', 'location'])
            ->withCount('applications')
            ->latest('updated_at')
            ->limit(6)
            ->get();

        $companies = $employer->companies()
            ->with(['industry', 'location'])
            ->withCount(['jobs' => fn ($query) => $query->ownedByEmployer($employer)])
            ->orderBy('name')
            ->get();

        return view('employer.dashboard', compact('statistics', 'recentJobs', 'companies'));
    }
}
