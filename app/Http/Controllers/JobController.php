<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\View\View;

class JobController extends Controller
{
    public function show(Job $job): View
    {
        abort_unless(Job::query()->publiclyAvailable()->whereKey($job->getKey())->exists(), 404);

        $job->load(['company.industry', 'company.location', 'category', 'industry', 'location', 'skills']);

        return view('jobs.show', compact('job'));
    }
}
