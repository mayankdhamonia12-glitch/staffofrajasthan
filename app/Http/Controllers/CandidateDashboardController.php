<?php

namespace App\Http\Controllers;

use App\Services\CandidateDashboardDataService;
use App\Services\CandidateProfileCompletionService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CandidateDashboardController extends Controller
{
    public function __invoke(
        Request $request,
        CandidateDashboardDataService $dashboardData,
        CandidateProfileCompletionService $completion,
    ): View {
        $user = $request->user();
        $candidateProfile = $user->candidateProfile()->firstOrCreate();
        $candidateProfile->load(['skills', 'educations', 'experiences', 'portfolios']);
        $progress = $completion->calculate($candidateProfile, $user->name);
        $data = $dashboardData->overview($user, $candidateProfile);

        return view('candidate.dashboard', [
            ...$data,
            'candidateProfile' => $candidateProfile,
            'profileProgress' => $progress,
            'unreadNotifications' => $user->unreadNotifications()->count(),
        ]);
    }
}
