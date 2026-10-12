<?php

namespace App\Services;

use App\Models\CandidateProfile;
use Carbon\CarbonImmutable;

class CandidateProfileViewsService
{
    /**
     * @return array{total: int, trend: list<array{date: string, label: string, views: int}>}
     */
    public function summary(CandidateProfile $profile, int $days = 14): array
    {
        $start = CarbonImmutable::today()->subDays($days - 1);
        $countsByDate = $profile->profileViews()
            ->whereBetween('viewed_at', [$start->startOfDay(), now()])
            ->selectRaw('DATE(viewed_at) as view_date, COUNT(*) as view_count')
            ->groupBy('view_date')
            ->pluck('view_count', 'view_date');

        $trend = array_values(collect(range(0, $days - 1))->map(function (int $offset) use ($start, $countsByDate): array {
            $date = $start->addDays($offset);
            $key = $date->toDateString();

            return ['date' => $key, 'label' => $date->format('D j'), 'views' => (int) ($countsByDate[$key] ?? 0)];
        })->all());

        return ['total' => $profile->profileViews()->count(), 'trend' => $trend];
    }
}
