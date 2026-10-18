<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;
use App\Models\Company;
use App\Models\Industry;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\JobCategory;
use App\Models\Skill;
use App\Models\User;
use App\Services\AccountManagementService;
use App\Services\AdminAuditLogger;
use App\Services\PlatformSettingsService;
use App\UserRole;
use DateTimeZone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ControlPanelController extends Controller
{
    use AuthorizesRequests;

    public function users(Request $request): View
    {
        $this->authorize('viewAny', User::class);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'role' => ['nullable', Rule::in(array_column(UserRole::cases(), 'value'))],
            'status' => ['nullable', Rule::in(['active', 'suspended'])],
        ]);
        $actor = $this->actor($request);
        $users = User::query()->when($actor->isAdmin() && ! $actor->isOwner(), fn (Builder $query) => $query->whereIn('role', [UserRole::Candidate->value, UserRole::Employer->value]))
            ->when(($filters['q'] ?? '') !== '', function (Builder $query) use ($filters): void {
                $term = '%'.trim((string) $filters['q']).'%';
                $query->where(fn (Builder $search) => $search->where('name', 'like', $term)->orWhere('email', 'like', $term));
            })
            ->when(($filters['role'] ?? '') !== '', fn (Builder $query) => $query->where('role', $filters['role']))
            ->when(($filters['status'] ?? '') !== '', fn (Builder $query) => $query->where('is_suspended', $filters['status'] === 'suspended'))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.users.index', compact('users', 'filters'));
    }

    public function showUser(User $user): View
    {
        $this->authorize('view', $user);
        $user->loadCount(['companies', 'jobApplications']);

        return view('admin.users.show', compact('user'));
    }

    public function suspendUser(Request $request, User $user, AccountManagementService $accounts): RedirectResponse
    {
        $this->authorize('suspend', $user);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);
        $accounts->suspend($this->actor($request), $user, $data['reason']);

        return back()->with('status', 'Account suspended. Affected public content is no longer visible.');
    }

    public function reactivateUser(Request $request, User $user, AccountManagementService $accounts): RedirectResponse
    {
        $this->authorize('reactivate', $user);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);
        $accounts->reactivate($this->actor($request), $user, $data['reason']);

        return back()->with('status', 'Account reactivated.');
    }

    public function changeUserRole(Request $request, User $user, AccountManagementService $accounts): RedirectResponse
    {
        $this->authorize('changeRole', $user);
        $data = $request->validate(['role' => ['required', Rule::in([UserRole::Admin->value, UserRole::Candidate->value, UserRole::Employer->value])]]);
        $accounts->changeRole($this->actor($request), $user, UserRole::from($data['role']));

        return back()->with('status', 'Account role updated.');
    }

    public function jobs(Request $request): View
    {
        $this->authorize('viewAny', Job::class);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120'], 'status' => ['nullable', Rule::in(['draft', 'published', 'closed'])]]);
        $jobs = Job::query()->with(['company:id,name,user_id', 'company.user:id,name,is_suspended'])
            ->when(($filters['q'] ?? '') !== '', function (Builder $query) use ($filters): void {
                $term = '%'.trim((string) $filters['q']).'%';
                $query->where(fn (Builder $search) => $search->where('title', 'like', $term)->orWhereHas('company', fn (Builder $companies) => $companies->where('name', 'like', $term)));
            })
            ->when(($filters['status'] ?? '') !== '', fn (Builder $query) => $query->where('status', $filters['status']))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.jobs.index', compact('jobs', 'filters'));
    }

    public function moderateJob(Request $request, Job $job, AdminAuditLogger $audit): RedirectResponse
    {
        $this->authorize('moderate', $job);
        $data = $request->validate(['status' => ['required', Rule::in(['draft', 'published', 'closed'])], 'reason' => ['required', 'string', 'min:5', 'max:500']]);
        DB::transaction(function () use ($request, $job, $data, $audit): void {
            $locked = Job::query()->with(['company.user'])->lockForUpdate()->whereKey($job->getKey())->firstOrFail();
            $before = ['status' => $locked->status];
            if ($data['status'] === 'published') {
                if (! $locked->company?->is_visible || $locked->company->user?->isSuspended() || ! $locked->location_id || ! $locked->description || $locked->is_filled || ($locked->application_deadline && $locked->application_deadline->lt(today()))) {
                    abort(422, 'This job cannot be published until the company and listing are eligible.');
                }
                $locked->published_at ??= now();
            }
            $locked->status = $data['status'];
            $locked->save();
            $audit->record($this->actor($request), 'job.moderated', $locked, 'Changed job '.$locked->getKey().' status to '.$locked->status.'.', $data['reason'], $before, ['status' => $locked->status]);
        });

        return back()->with('status', 'Job moderation saved. Existing applications and saved jobs were preserved.');
    }

    public function companies(Request $request): View
    {
        $this->authorize('viewAny', Company::class);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120'], 'visibility' => ['nullable', Rule::in(['visible', 'hidden'])]]);
        $companies = Company::query()->with(['user:id,name,email,is_suspended', 'industry:id,name', 'location:id,name'])
            ->when(($filters['q'] ?? '') !== '', function (Builder $query) use ($filters): void {
                $term = '%'.trim((string) $filters['q']).'%';
                $query->where(fn (Builder $search) => $search->where('name', 'like', $term)->orWhereHas('user', fn (Builder $users) => $users->where(fn (Builder $identity) => $identity->where('name', 'like', $term)->orWhere('email', 'like', $term))));
            })
            ->when(($filters['visibility'] ?? '') !== '', fn (Builder $query) => $query->where('is_visible', $filters['visibility'] === 'visible'))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.companies.index', compact('companies', 'filters'));
    }

    public function moderateCompany(Request $request, Company $company, AdminAuditLogger $audit): RedirectResponse
    {
        $this->authorize('moderate', $company);
        $data = $request->validate(['is_visible' => ['required', 'boolean'], 'reason' => ['required', 'string', 'min:5', 'max:500']]);
        DB::transaction(function () use ($request, $company, $data, $audit): void {
            $locked = Company::query()->lockForUpdate()->whereKey($company->getKey())->firstOrFail();
            $before = ['is_visible' => (bool) $locked->is_visible];
            $locked->forceFill([
                'is_visible' => (bool) $data['is_visible'],
                'moderated_by_user_id' => $this->actor($request)->getKey(),
                'moderated_at' => now(),
                'moderation_reason' => trim($data['reason']),
            ])->save();
            $audit->record($this->actor($request), 'company.visibility_changed', $locked, ($locked->is_visible ? 'Restored public visibility for company ' : 'Hid company ').$locked->getKey().'.', $data['reason'], $before, ['is_visible' => (bool) $locked->is_visible]);
        });

        return back()->with('status', 'Company visibility updated; existing jobs and applications were preserved.');
    }

    public function applications(Request $request): View
    {
        $this->authorize('viewAny', JobApplication::class);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120'], 'status' => ['nullable', 'string', 'max:40']]);
        $applications = JobApplication::query()->with(['job:id,company_id,title', 'job.company:id,name', 'user:id,name', 'statusHistory'])
            ->when(($filters['q'] ?? '') !== '', function (Builder $query) use ($filters): void {
                $term = '%'.trim((string) $filters['q']).'%';
                $query->where(fn (Builder $search) => $search->whereHas('job', fn (Builder $jobs) => $jobs->where('title', 'like', $term))->orWhereHas('user', fn (Builder $users) => $users->where('name', 'like', $term)));
            })
            ->when(($filters['status'] ?? '') !== '', fn (Builder $query) => $query->where('status', $filters['status']))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.applications.index', compact('applications', 'filters'));
    }

    public function application(Request $request, JobApplication $application): View
    {
        $this->authorize('view', $application);
        $application->load(['job:id,company_id,title,status', 'job.company:id,name', 'user:id,name', 'statusHistory']);

        return view('admin.applications.show', compact('application'));
    }

    public function referenceData(): View
    {
        abort_unless(auth()->user()->can('manage-reference-data'), 403);
        $categories = JobCategory::query()->orderBy('name')->get();
        $industries = Industry::query()->orderBy('name')->get();
        $skills = Skill::query()->orderBy('name')->paginate(50, ['*'], 'skills_page')->withQueryString();

        return view('admin.reference-data.index', compact('categories', 'industries', 'skills'));
    }

    public function storeReferenceData(Request $request, string $type): RedirectResponse
    {
        abort_unless(auth()->user()->can('manage-reference-data'), 403);
        abort_unless(in_array($type, ['categories', 'industries', 'skills'], true), 404);
        $data = $request->validate(['name' => ['required', 'string', 'max:120']]);
        $name = trim($data['name']);
        if ($type === 'categories') {
            JobCategory::query()->create(['name' => $name, 'slug' => $this->uniqueSlug(JobCategory::class, $name), 'is_active' => true]);
        } elseif ($type === 'industries') {
            Industry::query()->create(['name' => $name, 'slug' => $this->uniqueSlug(Industry::class, $name), 'is_active' => true]);
        } else {
            Skill::query()->firstOrCreate(['name' => $name], ['is_active' => true]);
        }
        app(AdminAuditLogger::class)->record($this->actor($request), 'reference.created', null, 'Created '.$type.' reference “'.$name.'”.', null, null, ['type' => $type, 'name' => $name]);

        return back()->with('status', 'Reference data added.');
    }

    public function toggleReferenceData(Request $request, string $type, int $id, AdminAuditLogger $audit): RedirectResponse
    {
        abort_unless(auth()->user()->can('manage-reference-data'), 403);
        $modelClass = match ($type) {
            'categories' => JobCategory::class,
            'industries' => Industry::class,
            'skills' => Skill::class,
            default => abort(404),
        };
        $record = $modelClass::query()->findOrFail($id);
        $before = ['is_active' => (bool) $record->is_active];
        $record->forceFill(['is_active' => ! $record->is_active])->save();
        $audit->record($this->actor($request), 'reference.status_changed', $record, 'Changed '.$type.' reference '.$record->getKey().' active status.', null, $before, ['is_active' => (bool) $record->is_active]);

        return back()->with('status', 'Reference status updated. Existing relationships were preserved.');
    }

    public function updateReferenceData(Request $request, string $type, int $id, AdminAuditLogger $audit): RedirectResponse
    {
        abort_unless(auth()->user()->can('manage-reference-data'), 403);
        $modelClass = match ($type) {
            'categories' => JobCategory::class,
            'industries' => Industry::class,
            'skills' => Skill::class,
            default => abort(404),
        };
        $record = $modelClass::query()->findOrFail($id);
        $table = match (true) {
            $record instanceof JobCategory => 'job_categories',
            $record instanceof Industry => 'industries',
            default => 'skills',
        };
        $data = $request->validate(['name' => ['required', 'string', 'max:120', Rule::unique($table, 'name')->ignore($id)]]);
        $before = ['name' => $record->name, 'slug' => $record->slug ?? null];
        $attributes = ['name' => trim($data['name'])];
        if ($record instanceof JobCategory) {
            $attributes['slug'] = $this->uniqueSlug(JobCategory::class, $attributes['name'], $id);
        } elseif ($record instanceof Industry) {
            $attributes['slug'] = $this->uniqueSlug(Industry::class, $attributes['name'], $id);
        }
        $record->forceFill($attributes)->save();
        $audit->record($this->actor($request), 'reference.updated', $record, 'Updated '.$type.' reference '.$record->getKey().'.', null, $before, ['name' => $record->name, 'slug' => $record->slug ?? null]);

        return back()->with('status', 'Reference name updated. Existing relationships were preserved.');
    }

    public function settings(PlatformSettingsService $settings): View
    {
        abort_unless(auth()->user()->can('manage-platform-settings'), 403);

        return view('admin.settings.edit', ['settings' => $settings->current(), 'timezones' => DateTimeZone::listIdentifiers()]);
    }

    public function updateSettings(Request $request, PlatformSettingsService $settings): RedirectResponse
    {
        abort_unless(auth()->user()->can('manage-platform-settings'), 403);
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:120'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'timezone' => ['required', Rule::in(DateTimeZone::listIdentifiers())],
            'locale' => ['required', Rule::in(['en', 'hi'])],
            'package_purchases_enabled' => ['sometimes', 'boolean'],
            'paid_job_posting_required' => ['sometimes', 'boolean'],
        ]);
        $current = $settings->current();
        $values = [
            'site_name' => (string) $data['site_name'],
            'contact_email' => is_string($data['contact_email'] ?? null) ? $data['contact_email'] : null,
            'contact_phone' => is_string($data['contact_phone'] ?? null) ? $data['contact_phone'] : null,
            'timezone' => (string) $data['timezone'],
            'locale' => (string) $data['locale'],
            'package_purchases_enabled' => array_key_exists('package_purchases_enabled', $data)
                ? (bool) $data['package_purchases_enabled']
                : $current->package_purchases_enabled,
            'paid_job_posting_required' => array_key_exists('paid_job_posting_required', $data)
                ? (bool) $data['paid_job_posting_required']
                : $current->paid_job_posting_required,
        ];
        $settings->update($this->actor($request), $values);

        return back()->with('status', 'Platform settings updated.');
    }

    public function audit(Request $request): View
    {
        abort_unless(auth()->user()->can('view-admin-audit-log'), 403);
        $filters = $request->validate(['action' => ['nullable', 'string', 'max:100'], 'q' => ['nullable', 'string', 'max:120']]);
        $logs = AdminAuditLog::query()->with('actor:id,name')
            ->when(($filters['action'] ?? '') !== '', fn (Builder $query) => $query->where('action', $filters['action']))
            ->when(($filters['q'] ?? '') !== '', function (Builder $query) use ($filters): void {
                $term = '%'.trim((string) $filters['q']).'%';
                $query->where(fn (Builder $search) => $search->where('summary', 'like', $term)->orWhere('reason', 'like', $term));
            })
            ->latest('created_at')->paginate(50)->withQueryString();

        return view('admin.audit.index', compact('logs', 'filters'));
    }

    private function actor(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && ($user->isAdmin() || $user->isOwner()) && ! $user->isSuspended(), 403);

        return $user;
    }

    /** @param class-string<JobCategory|Industry> $model */
    private function uniqueSlug(string $model, string $name, int|string|null $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'reference';
        $slug = $base;
        $suffix = 2;
        while ($model::query()->where('slug', $slug)->when($ignoreId !== null, fn (Builder $query) => $query->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
