<?php

namespace App\Services;

use App\Models\Job;
use App\Models\JobPostingUse;
use App\Models\PackageEntitlement;
use App\Models\User;
use App\PackageType;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JobPostingEntitlementService
{
    public function __construct(private readonly PlatformSettingsService $settings) {}

    public function authorizePublication(User $employer, Job $job): void
    {
        DB::transaction(function () use ($employer, $job): void {
            $lockedJob = Job::query()->whereKey($job->getKey())->lockForUpdate()->firstOrFail();
            $use = JobPostingUse::query()->where('job_id', $lockedJob->getKey())->lockForUpdate()->first();

            if ($use instanceof JobPostingUse) {
                if ($lockedJob->package_expires_at !== null && $lockedJob->package_expires_at->isPast()) {
                    throw ValidationException::withMessages(['intent' => 'This paid job listing has expired and cannot be reopened.']);
                }

                return;
            }

            if (! $this->settings->current()->paid_job_posting_required) {
                return;
            }

            $entitlement = PackageEntitlement::query()
                ->where('user_id', $employer->getKey())
                ->where('type', PackageType::JobPosting)
                ->where('credits_remaining', '>', 0)
                ->whereNull('valid_until')
                ->orderBy('created_at')
                ->lockForUpdate()
                ->first();

            if (! $entitlement instanceof PackageEntitlement) {
                throw ValidationException::withMessages([
                    'intent' => 'A valid job-posting credit is required. Choose a package before publishing.',
                ]);
            }

            $validityDays = max(1, (int) ($entitlement->package_snapshot['listing_validity_days'] ?? 1));
            $entitlement->decrement('credits_remaining');
            $lockedJob->update(['package_expires_at' => now()->addDays($validityDays)]);
            $entitlement->jobUses()->create([
                'job_id' => $lockedJob->getKey(),
                'employer_user_id' => $employer->getKey(),
                'consumed_at' => now(),
            ]);
        });
    }
}
