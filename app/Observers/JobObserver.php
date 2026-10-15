<?php

namespace App\Observers;

use App\Models\Job;
use App\Services\JobAlertMatchingService;

class JobObserver
{
    public function created(Job $job): void
    {
        app(JobAlertMatchingService::class)->evaluatePublicJob($job);
    }

    public function updated(Job $job): void
    {
        if ($job->wasChanged([
            'company_id', 'job_category_id', 'industry_id', 'location_id', 'title',
            'description', 'requirements', 'employment_type', 'experience_level',
            'application_deadline', 'status', 'is_filled', 'published_at',
        ])) {
            app(JobAlertMatchingService::class)->evaluatePublicJob($job);
        }
    }
}
