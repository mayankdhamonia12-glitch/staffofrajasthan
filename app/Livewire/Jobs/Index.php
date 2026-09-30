<?php

namespace App\Livewire\Jobs;

use App\Models\Industry;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Location;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $keyword = '';

    #[Url]
    public string $location = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $industry = '';

    #[Url]
    public string $employmentType = '';

    #[Url]
    public string $experienceLevel = '';

    #[Url]
    public string $minimumSalary = '';

    #[Url]
    public string $postedWithin = '';

    #[Url]
    public string $sort = 'newest';

    #[Url]
    public string $view = 'list';

    public bool $showFilters = false;

    public function updated(string $property): void
    {
        if ($property !== 'sort' && $property !== 'view' && $property !== 'showFilters') {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['keyword', 'location', 'category', 'industry', 'employmentType', 'experienceLevel', 'minimumSalary', 'postedWithin']);
        $this->resetPage();
    }

    public function toggleFilters(): void
    {
        $this->showFilters = ! $this->showFilters;
    }

    public function applyFilters(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $query = $this->publishedJobs();

        return view('livewire.jobs.index', [
            'jobs' => $query->paginate(12),
            'resultCount' => (clone $query)->count(),
            'categories' => JobCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']),
            'industries' => Industry::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']),
            'locations' => Location::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']),
            'employmentTypes' => Job::query()->publiclyAvailable()->distinct()->orderBy('employment_type')->pluck('employment_type'),
            'experienceLevels' => Job::query()->publiclyAvailable()->whereNotNull('experience_level')->distinct()->orderBy('experience_level')->pluck('experience_level'),
        ]);
    }

    /** @return Builder<Job> */
    private function publishedJobs(): Builder
    {
        $query = Job::query()
            ->with(['company.industry', 'company.location', 'category', 'industry', 'location', 'skills'])
            ->publiclyAvailable();

        $keyword = trim($this->keyword);
        if ($keyword !== '') {
            $query->where(function (Builder $jobs) use ($keyword) {
                $jobs->whereLike('title', "%{$keyword}%")
                    ->orWhereLike('description', "%{$keyword}%")
                    ->orWhereHas('company', fn (Builder $company) => $company->whereLike('name', "%{$keyword}%"))
                    ->orWhereHas('skills', fn (Builder $skills) => $skills->whereLike('name', "%{$keyword}%"));
            });
        }

        if (trim($this->location) !== '') {
            $location = trim($this->location);
            $query->whereHas('location', fn (Builder $locations) => $locations->whereLike('name', "%{$location}%")->orWhereLike('state', "%{$location}%"));
        }

        if ($this->category !== '') {
            $query->whereHas('category', fn (Builder $categories) => $categories->where('slug', $this->category));
        }

        if ($this->industry !== '') {
            $query->whereHas('industry', fn (Builder $industries) => $industries->where('slug', $this->industry));
        }

        if ($this->employmentType !== '') {
            $query->where('employment_type', $this->employmentType);
        }

        if ($this->experienceLevel !== '') {
            $query->where('experience_level', $this->experienceLevel);
        }

        if (ctype_digit($this->minimumSalary)) {
            $query->where(fn (Builder $jobs) => $jobs->where('salary_max', '>=', (int) $this->minimumSalary)->orWhereNull('salary_max'));
        }

        if (in_array($this->postedWithin, ['1', '7', '30'], true)) {
            $query->where('published_at', '>=', now()->subDays((int) $this->postedWithin));
        }

        match ($this->sort) {
            'salary_high' => $query->orderByRaw('salary_max IS NULL')->orderByDesc('salary_max'),
            'salary_low' => $query->orderByRaw('salary_min IS NULL')->orderBy('salary_min'),
            default => $query->latest('published_at'),
        };

        return $query;
    }
}
