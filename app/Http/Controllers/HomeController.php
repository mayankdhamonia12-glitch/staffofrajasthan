<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Job;
use App\Models\JobCategory;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $publishedJobs = Job::query()->publiclyAvailable();

        return view('welcome', [
            'jobCount' => (clone $publishedJobs)->count(),
            'categoryCount' => JobCategory::query()->where('is_active', true)->count(),
            'featuredJobs' => (clone $publishedJobs)->where('is_featured', true)->with(['company.location', 'location', 'category'])->latest('published_at')->limit(3)->get(),
            'latestJobs' => (clone $publishedJobs)->with(['company.location', 'location', 'category'])->latest('published_at')->limit(4)->get(),
            'categories' => JobCategory::query()->where('is_active', true)->whereHas('jobs', fn ($query) => $query->publiclyAvailable())->withCount(['jobs' => fn ($query) => $query->publiclyAvailable()])->orderByDesc('jobs_count')->limit(8)->get(),
            'companies' => Company::query()->where('is_visible', true)->whereHas('jobs', fn ($query) => $query->publiclyAvailable())->with(['industry', 'location'])->withCount(['jobs' => fn ($query) => $query->publiclyAvailable()])->orderByDesc('jobs_count')->limit(4)->get(),
        ]);
    }
}
