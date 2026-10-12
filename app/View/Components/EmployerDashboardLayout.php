<?php

namespace App\View\Components;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;
use Illuminate\View\View;

class EmployerDashboardLayout extends Component
{
    public ?User $employer;

    public ?Company $dashboardCompany;

    public function __construct(
        public string $title,
        public string $active = 'dashboard',
        public ?string $subtitle = null,
    ) {
        $authenticatedUser = Auth::user();
        $this->employer = $authenticatedUser instanceof User ? $authenticatedUser : null;
        $this->dashboardCompany = $this->employer?->companies()->with('industry')->orderBy('name')->first();
    }

    public function render(): View
    {
        return view('components.employer-dashboard-layout');
    }
}
