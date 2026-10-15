<?php

namespace App\Observers;

use App\Models\Company;
use App\Services\JobAlertMatchingService;

class CompanyObserver
{
    public function updated(Company $company): void
    {
        if (! $company->wasChanged(['is_visible', 'name']) || ! $company->is_visible) {
            return;
        }

        $service = app(JobAlertMatchingService::class);
        $company->jobs()->publiclyAvailable()->orderBy('id')->chunkById(100, function ($jobs) use ($service): void {
            foreach ($jobs as $job) {
                $service->evaluatePublicJob($job);
            }
        });
    }
}
