<?php

namespace App\Livewire\Employer\Jobs;

use App\Models\Job;
use App\Models\User;
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
    public string $status = '';

    #[Url]
    public string $sort = 'updated';

    public function updated(string $property): void
    {
        if (in_array($property, ['keyword', 'status', 'sort'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['keyword', 'status', 'sort']);
        $this->sort = 'updated';
        $this->resetPage();
    }

    public function render(): View
    {
        $employer = auth()->user();
        abort_unless($employer instanceof User && $employer->isEmployer(), 403);

        $query = Job::query()
            ->ownedByEmployer($employer)
            ->with(['company.location', 'location'])
            ->withCount('applications');

        if (trim($this->keyword) !== '') {
            $keyword = '%'.trim($this->keyword).'%';
            $query->where(function (Builder $jobs) use ($keyword): void {
                $jobs->whereLike('title', $keyword)
                    ->orWhereHas('company', fn (Builder $companies) => $companies->whereLike('name', $keyword));
            });
        }

        $this->applyStatus($query);

        match ($this->sort) {
            'created' => $query->latest('created_at'),
            'published' => $query->orderByRaw('published_at IS NULL')->latest('published_at'),
            'title' => $query->orderBy('title'),
            default => $query->latest('updated_at'),
        };

        return view('livewire.employer.jobs.index', [
            'jobs' => $query->paginate(10),
        ]);
    }

    /** @param Builder<Job> $query */
    private function applyStatus(Builder $query): void
    {
        match ($this->status) {
            'active' => $query->publiclyAvailable(),
            'draft' => $query->where('status', 'draft'),
            'closed' => $query->where('status', 'closed')->where('is_filled', false),
            'filled' => $query->where('is_filled', true),
            'expired' => $query->where('status', 'published')->whereNotNull('application_deadline')->whereDate('application_deadline', '<', today())->where('is_filled', false),
            'unlisted' => $query->where('status', 'published')->whereDoesntHave('company', fn (Builder $companies) => $companies->where('is_visible', true)),
            'inactive' => $query->where(function (Builder $jobs): void {
                $jobs->where('status', 'closed')
                    ->orWhere('is_filled', true)
                    ->orWhere(function (Builder $expired): void {
                        $expired->where('status', 'published')->whereDate('application_deadline', '<', today());
                    });
            }),
            default => null,
        };
    }
}
