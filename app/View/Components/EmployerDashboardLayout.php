<?php

namespace App\View\Components;

use App\Models\Company;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use Illuminate\View\View;

class EmployerDashboardLayout extends Component
{
    public ?User $employer;

    public ?Company $dashboardCompany;

    public int $unreadNotificationCount;

    public int $unreadMessageCount;

    public function __construct(
        public string $title,
        public string $active = 'dashboard',
        public ?string $subtitle = null,
    ) {
        $authenticatedUser = Auth::user();
        $this->employer = $authenticatedUser instanceof User ? $authenticatedUser : null;
        $this->dashboardCompany = $this->employer?->companies()->with('industry')->orderBy('name')->first();
        $this->unreadNotificationCount = $this->employer?->unreadNotifications()->count() ?? 0;
        $this->unreadMessageCount = $this->employer instanceof User
            ? Message::query()->whereNull('read_at')->where('sender_user_id', '!=', $this->employer->getKey())
                ->whereHas('conversation', fn ($query) => $query->where('employer_user_id', $this->employer->getKey()))->count()
            : 0;
    }

    public function render(): View
    {
        return view('components.employer-dashboard-layout');
    }
}
