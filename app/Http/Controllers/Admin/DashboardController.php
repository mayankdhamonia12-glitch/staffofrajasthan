<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Company;
use App\Models\CompanyReview;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use App\ReviewStatus;
use App\UserRole;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;

class DashboardController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(): View
    {
        $this->authorize('viewAny', User::class);

        $counts = [
            'users' => User::query()->count(),
            'candidates' => User::query()->where('role', UserRole::Candidate->value)->count(),
            'employers' => User::query()->where('role', UserRole::Employer->value)->count(),
            'admins' => User::query()->where('role', UserRole::Admin->value)->count(),
            'suspended' => User::query()->where('is_suspended', true)->count(),
            'jobs' => Job::query()->count(),
            'published_jobs' => Job::query()->publiclyAvailable()->count(),
            'applications' => JobApplication::query()->count(),
            'companies' => Company::query()->count(),
            'visible_companies' => Company::query()->publiclyVisible()->count(),
            'articles' => Article::query()->count(),
            'pending_reviews' => CompanyReview::query()->where('status', ReviewStatus::Pending->value)->count(),
        ];
        $recentUsers = User::query()->latest()->limit(8)->get(['id', 'name', 'email', 'role', 'is_suspended', 'created_at']);
        $recentJobs = Job::query()->with('company:id,name')->latest()->limit(8)->get(['id', 'company_id', 'title', 'status', 'created_at']);

        return view('admin.dashboard', compact('counts', 'recentUsers', 'recentJobs'));
    }
}
