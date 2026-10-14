<?php

namespace App\Services;

use App\Models\CandidateProfile;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\SavedJob;
use App\Models\User;
use Illuminate\Support\Collection;

class CandidateDashboardDataService
{
    public function __construct(private readonly CandidateProfileViewsService $profileViews) {}

    /**
     * @return array{applications: int, savedJobs: int, profileViews: int, recentApplications: Collection<int, JobApplication>, recentSavedJobs: Collection<int, SavedJob>, availableApplicationJobIds: array<int, bool>, profileViewTrend: list<array{date: string, label: string, views: int}>}
     */
    public function overview(User $user, CandidateProfile $profile): array
    {
        $views = $this->profileViews->summary($profile);

        $recentApplications = $user->jobApplications()->with(['job.company.location', 'job.location'])->latest()->limit(5)->get();
        $availableApplicationJobIds = Job::query()->publiclyAvailable()
            ->whereIn('id', $recentApplications->pluck('job_id'))
            ->pluck('id')
            ->mapWithKeys(static fn ($id): array => [(int) $id => true])
            ->all();

        return [
            'applications' => $user->jobApplications()->count(),
            'savedJobs' => $user->savedJobs()->whereHas('job', fn ($jobs) => $jobs->publiclyAvailable())->count(),
            'profileViews' => $views['total'],
            'recentApplications' => $recentApplications,
            'availableApplicationJobIds' => $availableApplicationJobIds,
            'recentSavedJobs' => $user->savedJobs()->whereHas('job', fn ($jobs) => $jobs->publiclyAvailable())
                ->with(['job.company.location', 'job.category', 'job.location', 'job.skills'])
                ->latest()->limit(5)->get(),
            'profileViewTrend' => $views['trend'],
        ];
    }
}
