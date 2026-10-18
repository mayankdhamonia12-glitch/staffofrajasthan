<?php

namespace App\Providers;

use App\Models\Article;
use App\Models\Company;
use App\Models\CompanyReview;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use App\Observers\CompanyObserver;
use App\Observers\JobObserver;
use App\Policies\ArticlePolicy;
use App\Policies\CompanyPolicy;
use App\Policies\CompanyReviewPolicy;
use App\Policies\JobApplicationPolicy;
use App\Policies\JobPolicy;
use App\Policies\UserPolicy;
use App\Services\PlatformSettingsService;
use App\UserRole;
use App\View\Components\CandidateDashboardLayout;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PlatformSettingsService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerGates();
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Job::class, JobPolicy::class);
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(JobApplication::class, JobApplicationPolicy::class);
        Gate::policy(CompanyReview::class, CompanyReviewPolicy::class);
        Gate::policy(Article::class, ArticlePolicy::class);
        Gate::define('manage-reference-data', fn ($user): bool => ! $user->isSuspended() && ($user->isAdmin() || $user->isOwner()));
        Gate::define('manage-platform-settings', fn ($user): bool => ! $user->isSuspended() && $user->isOwner());
        Gate::define('view-admin-audit-log', fn ($user): bool => ! $user->isSuspended() && $user->isOwner());
        Blade::component(CandidateDashboardLayout::class);
        Company::observe(CompanyObserver::class);
        Job::observe(JobObserver::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Register role-based authorization gates.
     *
     * These gates allow views and controllers to do:
     *
     *   Blade's `can` directive with `access-candidate-area`, or `Gate::allows('access-employer-area')` in PHP.
     *
     * Gates are defined here rather than in policies because they are
     * cross-cutting role checks, not model-specific policies.
     */
    protected function registerGates(): void
    {
        Gate::define('access-candidate-area', fn ($user): bool => $user->isCandidate());
        Gate::define('access-employer-area', fn ($user): bool => $user->isEmployer());
        Gate::define('access-admin-area', fn ($user): bool => $user->hasAnyRole(UserRole::Admin, UserRole::Owner));
        Gate::define('access-owner-area', fn ($user): bool => $user->isOwner());
    }
}
