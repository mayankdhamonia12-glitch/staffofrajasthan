<?php

namespace App\View\Components;

use App\Models\CandidateProfile;
use App\Models\Message;
use App\Models\User;
use App\Services\CandidateProfileCompletionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use Illuminate\View\View;

class CandidateDashboardLayout extends Component
{
    public ?User $dashboardCandidate;

    public ?CandidateProfile $dashboardCandidateProfile;

    /** @var array{percentage: int, completed: int, total: int, sections: list<array{label: string, complete: bool, action: string, anchor: string}>, missing: list<array{label: string, complete: bool, action: string, anchor: string}>} */
    public array $dashboardProfileProgress;

    public int $dashboardUnreadNotificationCount;

    public int $dashboardUnreadMessageCount;

    public function __construct(
        public string $title,
        public string $active = 'dashboard',
        public ?string $subtitle = null,
    ) {
        $authenticatedUser = Auth::user();
        $this->dashboardCandidate = $authenticatedUser instanceof User ? $authenticatedUser : null;
        $this->dashboardCandidateProfile = $this->dashboardCandidate?->candidateProfile;
        $this->dashboardUnreadNotificationCount = $this->dashboardCandidate?->unreadNotifications()->count() ?? 0;
        $this->dashboardUnreadMessageCount = $this->dashboardCandidate instanceof User
            ? Message::query()->whereNull('read_at')->where('sender_user_id', '!=', $this->dashboardCandidate->getKey())
                ->whereHas('conversation', fn ($query) => $query->where('candidate_user_id', $this->dashboardCandidate->getKey()))->count()
            : 0;
        $this->dashboardProfileProgress = app(CandidateProfileCompletionService::class)->calculate(
            $this->dashboardCandidateProfile,
            $this->dashboardCandidate ? $this->dashboardCandidate->name : '',
        );
    }

    public function render(): View
    {
        return view('components.candidate-dashboard-layout');
    }
}
