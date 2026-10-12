<?php

namespace App\Services;

use App\Models\CandidateProfile;

class CandidateProfileCompletionService
{
    /**
     * @return array{percentage: int, completed: int, total: int, sections: list<array{label: string, complete: bool, action: string, anchor: string}>, missing: list<array{label: string, complete: bool, action: string, anchor: string}>}
     */
    public function calculate(?CandidateProfile $profile, string $userName): array
    {
        $profile?->loadMissing(['skills', 'educations', 'experiences']);
        $base = route('candidate.profile.edit');
        $sections = [
            ['label' => 'Basic information and profile photo', 'complete' => filled($userName) && (bool) $profile?->profile_photo_path, 'action' => $base, 'anchor' => 'profile'],
            ['label' => 'Professional title', 'complete' => filled($profile?->headline), 'action' => $base, 'anchor' => 'profile'],
            ['label' => 'Professional description', 'complete' => filled($profile?->about), 'action' => $base, 'anchor' => 'profile'],
            ['label' => 'Location', 'complete' => filled($profile?->city) || filled($profile?->state), 'action' => $base, 'anchor' => 'profile'],
            ['label' => 'Skills', 'complete' => ($profile?->skills->isNotEmpty() ?? false), 'action' => $base, 'anchor' => 'skills'],
            ['label' => 'Education', 'complete' => ($profile?->educations->isNotEmpty() ?? false), 'action' => $base, 'anchor' => 'education'],
            ['label' => 'Work experience', 'complete' => ($profile?->experiences->isNotEmpty() ?? false), 'action' => $base, 'anchor' => 'experience'],
            ['label' => 'Resume', 'complete' => filled($profile?->resume_path), 'action' => $base, 'anchor' => 'resume'],
            ['label' => 'Professional links', 'complete' => collect([$profile?->linkedin_url, $profile?->website_url, $profile?->portfolio_url])->contains(fn (?string $url): bool => filled($url)), 'action' => $base, 'anchor' => 'profile'],
        ];
        $completed = count(array_filter($sections, fn (array $section): bool => $section['complete']));
        $missing = array_values(array_filter($sections, fn (array $section): bool => ! $section['complete']));

        return [
            'percentage' => (int) round($completed / count($sections) * 100),
            'completed' => $completed,
            'total' => count($sections),
            'sections' => $sections,
            'missing' => $missing,
        ];
    }
}
