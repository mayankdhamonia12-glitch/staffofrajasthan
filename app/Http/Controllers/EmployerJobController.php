<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Industry;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Location;
use App\Models\Skill;
use App\Models\User;
use App\Services\JobAlertMatchingService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EmployerJobController extends Controller
{
    public function __construct(private readonly JobAlertMatchingService $matching) {}

    public function create(Request $request): View
    {
        return view('employer.jobs.form', [...$this->formData($request->user()), 'job' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $intent = $this->intent($request);
        $data = $this->validated($request, $intent);
        $company = $this->ownedCompany($request->user(), $data['company_id']);
        $status = $intent === 'publish' ? 'published' : 'draft';
        if ($intent === 'publish') {
            $this->ensurePublishable($company, $data);
        }

        $job = DB::transaction(function () use ($company, $data, $status): Job {
            $attributes = $this->jobAttributes($data);
            $attributes['slug'] = $this->uniqueSlug((string) $data['title']);
            $attributes['status'] = $status;
            $attributes['published_at'] = $status === 'published' ? now() : null;
            $attributes['salary_currency'] = 'INR';
            $attributes['is_featured'] = false;
            $attributes['is_urgent'] = false;
            $attributes['is_filled'] = false;
            $job = $company->jobs()->create($attributes);
            $job->skills()->sync($data['skills'] ?? []);
            if ($status === 'published') {
                $this->matching->evaluatePublicJob($job);
            }

            return $job;
        });

        return redirect()->route('employer.jobs.edit', $job)->with('status', $status === 'published' ? 'Job published.' : 'Draft saved.');
    }

    public function edit(Request $request, Job $job): View
    {
        $job = $this->ownedJob($request->user(), $job)->load(['company', 'skills']);

        return view('employer.jobs.form', [...$this->formData($request->user()), 'job' => $job]);
    }

    public function update(Request $request, Job $job): RedirectResponse
    {
        $job = $this->ownedJob($request->user(), $job);
        $intent = $this->intent($request, 'save');
        $data = $this->validated($request, $intent);
        $company = $this->ownedCompany($request->user(), $data['company_id']);

        if ($job->is_filled && in_array($intent, ['draft', 'publish'], true)) {
            throw ValidationException::withMessages(['intent' => 'Reopen this filled position from My Jobs before changing its status.']);
        }

        $status = match ($intent) {
            'draft' => 'draft',
            'publish' => 'published',
            default => $job->status,
        };
        if ($intent === 'publish') {
            $this->ensurePublishable($company, $data);
        } elseif ($intent === 'save' && $status === 'published') {
            $this->ensureJobDetailsComplete($data);
        }

        DB::transaction(function () use ($job, $company, $data, $status, $intent): void {
            $job->company()->associate($company);
            $attributes = $this->jobAttributes($data);
            if ($intent === 'draft') {
                $attributes['published_at'] = null;
            } elseif ($status === 'published') {
                $attributes['published_at'] = $job->published_at ?? now();
            }
            $attributes['status'] = $status;
            $job->fill($attributes)->save();
            $job->skills()->sync($data['skills'] ?? []);
            if ($status === 'published') {
                $this->matching->evaluatePublicJob($job);
            }
        });

        return back()->with('status', match ($intent) {
            'draft' => 'Job saved as a draft.',
            'publish' => 'Job published.',
            default => 'Job updated.',
        });
    }

    public function duplicate(Request $request, Job $job): RedirectResponse
    {
        $job = $this->ownedJob($request->user(), $job)->load('skills');
        $copy = DB::transaction(function () use ($job): Job {
            $company = $job->company;
            $copy = $company->jobs()->create([
                'job_category_id' => $job->job_category_id,
                'industry_id' => $job->industry_id,
                'location_id' => $job->location_id,
                'title' => $job->title.' (Copy)',
                'slug' => $this->uniqueSlug($job->title.'-copy'),
                'description' => $job->description,
                'responsibilities' => $job->responsibilities,
                'requirements' => $job->requirements,
                'employment_type' => $job->employment_type,
                'experience_level' => $job->experience_level,
                'salary_min' => $job->salary_min,
                'salary_max' => $job->salary_max,
                'salary_currency' => $job->salary_currency,
                'salary_period' => $job->salary_period,
                'vacancies' => $job->vacancies,
                'application_deadline' => null,
                'status' => 'draft',
                'is_featured' => false,
                'is_urgent' => false,
                'is_filled' => false,
                'published_at' => null,
            ]);
            $copy->skills()->sync($job->skills->modelKeys());

            return $copy;
        });

        return redirect()->route('employer.jobs.edit', $copy)->with('status', 'A new draft was created from this job. Applications and saved jobs were not copied.');
    }

    public function lifecycle(Request $request, Job $job): RedirectResponse
    {
        $job = $this->ownedJob($request->user(), $job);
        $data = $request->validate(['action' => ['required', Rule::in(['close', 'filled', 'reopen'])]]);

        if ($data['action'] === 'close') {
            if ($job->status !== 'published' || $job->is_filled) {
                throw ValidationException::withMessages(['action' => 'Only an unfilled published listing can be closed.']);
            }
            $job->update(['status' => 'closed']);

            return back()->with('status', 'Job closed. It is no longer visible in public listings.');
        }

        if ($data['action'] === 'filled') {
            if ($job->status !== 'published' || $job->is_filled) {
                throw ValidationException::withMessages(['action' => 'Only an unfilled published listing can be marked as filled.']);
            }
            $job->update(['is_filled' => true]);

            return back()->with('status', 'Position marked as filled.');
        }

        if ($job->status !== 'closed' && ! $job->is_filled) {
            throw ValidationException::withMessages(['action' => 'Only a closed or filled job can be reopened.']);
        }

        $company = $job->company;
        $this->ensurePublishable($company, [
            'title' => $job->title,
            'description' => $job->description,
            'location_id' => $job->location_id,
            'employment_type' => $job->employment_type,
            'application_deadline' => $job->application_deadline?->toDateString(),
        ]);
        $job->update(['status' => 'published', 'is_filled' => false, 'published_at' => $job->published_at ?? now()]);

        return back()->with('status', 'Job reopened and published.');
    }

    /** @return array{companies: Collection<int, Company>, categories: Collection<int, JobCategory>, industries: Collection<int, Industry>, locations: Collection<int, Location>, skills: Collection<int, Skill>} */
    private function formData(User $employer): array
    {
        return [
            'companies' => $employer->companies()->with(['industry', 'location'])->orderBy('name')->get(),
            'categories' => JobCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'industries' => Industry::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'locations' => Location::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'state']),
            'skills' => Skill::query()->orderBy('name')->get(['id', 'name']),
        ];
    }

    private function intent(Request $request, string $default = 'draft'): string
    {
        $intent = (string) $request->input('intent', $default);
        if (! in_array($intent, ['draft', 'publish', 'save'], true)) {
            throw ValidationException::withMessages(['intent' => 'Choose whether to save a draft or publish the listing.']);
        }

        return $intent;
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, string $intent): array
    {
        $data = $request->validate([
            'company_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:180'],
            'description' => [Rule::requiredIf($intent === 'publish'), 'nullable', 'string', 'max:30000'],
            'responsibilities' => ['nullable', 'string', 'max:30000'],
            'requirements' => ['nullable', 'string', 'max:30000'],
            'job_category_id' => ['nullable', 'integer', 'exists:job_categories,id,is_active,1'],
            'industry_id' => ['nullable', 'integer', 'exists:industries,id,is_active,1'],
            'location_id' => [Rule::requiredIf($intent === 'publish'), 'nullable', 'integer', 'exists:locations,id,is_active,1'],
            'employment_type' => [Rule::requiredIf($intent === 'publish'), 'nullable', Rule::in(['full_time', 'part_time', 'contract', 'temporary', 'internship'])],
            'experience_level' => ['nullable', Rule::in(['entry', 'junior', 'mid', 'senior', 'lead', 'executive'])],
            'salary_min' => ['nullable', 'integer', 'between:0,2000000000'],
            'salary_max' => ['nullable', 'integer', 'between:0,2000000000'],
            'salary_period' => ['nullable', Rule::in(['hourly', 'weekly', 'monthly', 'yearly'])],
            'vacancies' => ['nullable', 'integer', 'between:1,10000'],
            'application_deadline' => ['nullable', 'date'],
            'skills' => ['nullable', 'array', 'max:20'],
            'skills.*' => ['integer', 'distinct', 'exists:skills,id'],
        ]);

        if (isset($data['salary_min'], $data['salary_max']) && $data['salary_min'] > $data['salary_max']) {
            throw ValidationException::withMessages(['salary_max' => 'The maximum salary must be at least the minimum salary.']);
        }

        return $data;
    }

    private function ownedCompany(User $employer, int|string $companyId): Company
    {
        $company = $employer->companies()->whereKey($companyId)->first();
        if (! $company instanceof Company) {
            throw ValidationException::withMessages(['company_id' => 'Choose a company profile owned by your employer account.']);
        }

        return $company;
    }

    private function ownedJob(User $employer, Job $job): Job
    {
        return Job::query()->ownedByEmployer($employer)->whereKey($job->getKey())->with('company')->firstOrFail();
    }

    /** @param array<string, mixed> $data */
    private function ensurePublishable(Company $company, array $data): void
    {
        if (! $company->is_visible || ! $company->industry_id || ! $company->location_id) {
            throw ValidationException::withMessages([
                'company_id' => 'Complete your public company profile with an industry and location before publishing.',
            ]);
        }

        foreach (['title', 'description', 'location_id', 'employment_type'] as $field) {
            if (empty($data[$field])) {
                throw ValidationException::withMessages([$field => 'This field is required to publish a job.']);
            }
        }

        if (! empty($data['application_deadline']) && Carbon::parse((string) $data['application_deadline'])->startOfDay()->lt(today())) {
            throw ValidationException::withMessages(['application_deadline' => 'The application deadline must be today or in the future to publish.']);
        }
    }

    /** @param array<string, mixed> $data */
    private function ensureJobDetailsComplete(array $data): void
    {
        foreach (['title', 'description', 'location_id', 'employment_type'] as $field) {
            if (empty($data[$field])) {
                throw ValidationException::withMessages([$field => 'This field is required for a published listing.']);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function jobAttributes(array $data): array
    {
        unset($data['company_id'], $data['skills']);

        return $data;
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'job';
        $slug = $base;
        $suffix = 2;
        while (Job::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
