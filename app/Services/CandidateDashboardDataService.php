<?php

namespace App\Services;

use App\Models\CandidateProfile;
use App\Models\JobApplication;
use App\Models\SavedJob;
use App\Models\User;
use Illuminate\Support\Collection;

class CandidateDashboardDataService
{
    public function __construct(private readonly CandidateProfileViewsService $profileViews) {}

    /**
     * @return array{applications: int, savedJobs: int, profileViews: int, recentApplications: Collection<int, JobApplication>, recentSavedJobs: Collection<int, SavedJob>, profileViewTrend: list<array{date: string, label: string, views: int}>}
     */
    public function overview(User $user, CandidateProfile $profile): array
    {
        $views = $this->profileViews->summary($profile);

        return [
            'applications' => $user->jobApplications()->count(),
            'savedJobs' => $user->savedJobs()->whereHas('job', fn ($jobs) => $jobs->publiclyAvailable())->count(),
            'profileViews' => $views['total'],
            'recentApplications' => $user->jobApplications()->with(['job.company.location', 'job.location'])->latest()->limit(5)->get(),
            'recentSavedJobs' => $user->savedJobs()->whereHas('job', fn ($jobs) => $jobs->publiclyAvailable())
                ->with(['job.company.location', 'job.category', 'job.location', 'job.skills'])
                ->latest()->limit(5)->get(),
            'profileViewTrend' => $views['trend'],
        ];
    }
}
