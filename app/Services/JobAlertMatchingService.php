<?php

namespace App\Services;

use App\Models\Job;
use App\Models\JobAlert;
use App\Models\User;
use App\Notifications\JobOpportunityNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JobAlertMatchingService
{
    /** @return Builder<Job> */
    public function matchingJobs(JobAlert $alert): Builder
    {
        $query = Job::query()
            ->publiclyAvailable()
            ->with(['company.industry', 'company.location', 'category', 'industry', 'location']);

        if ($keyword = trim((string) $alert->keyword)) {
            $like = "%{$keyword}%";
            $query->where(function (Builder $jobs) use ($like): void {
                $jobs->whereLike('title', $like)
                    ->orWhereLike('description', $like)
                    ->orWhereHas('company', fn (Builder $company) => $company->whereLike('name', $like))
                    ->orWhereHas('skills', fn (Builder $skills) => $skills->whereLike('name', $like));
            });
        }

        if ($alert->job_category_id !== null) {
            $query->where('job_category_id', $alert->job_category_id);
        }
        if ($alert->industry_id !== null) {
            $query->where('industry_id', $alert->industry_id);
        }
        if ($alert->location_id !== null) {
            $query->where('location_id', $alert->location_id);
        }
        if ($alert->employment_type !== null) {
            $query->where('employment_type', $alert->employment_type);
        }
        if ($alert->experience_level !== null) {
            $query->where('experience_level', $alert->experience_level);
        }

        return $query->latest('published_at');
    }

    /**
     * Evaluate an eligible job after it is created, published, reopened, or
     * otherwise changed in a way that can affect the public search criteria.
     */
    public function evaluatePublicJob(Job $job): void
    {
        $job = Job::query()->publiclyAvailable()->with('company')->whereKey($job->getKey())->first();
        if (! $job instanceof Job) {
            return;
        }

        DB::transaction(function () use ($job): void {
            $candidateIds = [];
            JobAlert::query()->active()->orderBy('id')->chunkById(100, function ($alerts) use ($job, &$candidateIds): void {
                foreach ($alerts as $alert) {
                    if (! $this->matchingJobs($alert)->whereKey($job->getKey())->exists()) {
                        continue;
                    }

                    DB::table('job_alert_matches')->insertOrIgnore([
                        'job_alert_id' => $alert->getKey(),
                        'job_id' => $job->getKey(),
                        'matched_at' => now(),
                    ]);
                    $candidateIds[(int) $alert->user_id] = true;
                }
            });

            foreach ($job->company->followers()->pluck('users.id') as $followerId) {
                $candidateIds[(int) $followerId] = true;
            }

            foreach (array_keys($candidateIds) as $candidateId) {
                $candidate = User::query()->whereKey($candidateId)->where('role', 'candidate')->first();
                if (! $candidate instanceof User) {
                    continue;
                }

                $notification = JobOpportunityNotification::forJob($job);
                $notification->id = (string) Str::uuid();
                $now = now();
                $inserted = DB::table('job_notification_deliveries')->insertOrIgnore([
                    'user_id' => $candidate->getKey(),
                    'job_id' => $job->getKey(),
                    'notification_id' => $notification->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                if ($inserted === 1) {
                    $candidate->notify($notification);
                }
            }
        });
    }
}
