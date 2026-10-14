<?php

namespace App\Http\Controllers;

use App\JobApplicationStatus;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmployerApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $employer = $this->employer($request);
        $applications = JobApplication::query()
            ->whereHas('job', fn (Builder $jobs) => $jobs->ownedByEmployer($employer))
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $counts = [];
        foreach (JobApplicationStatus::cases() as $status) {
            $counts[$status->value] = (int) ($applications[$status->value] ?? 0);
        }

        $recentApplications = $this->ownedApplicationsFor($employer)
            ->with(['job.company', 'user.candidateProfile' => $this->publicProfileProjection()])
            ->latest('created_at')
            ->limit(8)
            ->get();

        $jobs = Job::query()->ownedByEmployer($employer)
            ->with('company')
            ->withCount('applications')
            ->orderByDesc('applications_count')
            ->orderBy('title')
            ->limit(8)
            ->get();

        return view('employer.applications.index', [
            'counts' => $counts,
            'totalApplications' => $this->ownedApplicationsFor($employer)->count(),
            'recentApplications' => $recentApplications,
            'jobs' => $jobs,
        ]);
    }

    public function forJob(Request $request, Job $job): View
    {
        $employer = $this->employer($request);
        $job = Job::query()->ownedByEmployer($employer)->with('company')->whereKey($job->getKey())->firstOrFail();
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(array_keys(JobApplicationStatus::options()))],
            'sort' => ['nullable', Rule::in(['newest', 'oldest'])],
        ]);

        $query = $job->applications()->with([
            'user.candidateProfile' => $this->publicProfileProjection(),
            'user.candidateProfile.skills',
        ]);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $like = '%'.$search.'%';
            $query->whereHas('user', function (Builder $users) use ($like): void {
                $users->where('name', 'like', $like)
                    ->orWhereHas('candidateProfile', function (Builder $profiles) use ($like): void {
                        $profiles->where('is_public', true)
                            ->where(function (Builder $publicFields) use ($like): void {
                                $publicFields->where('headline', 'like', $like)
                                    ->orWhere('city', 'like', $like)
                                    ->orWhere('state', 'like', $like)
                                    ->orWhereHas('skills', fn (Builder $skills) => $skills->where('name', 'like', $like));
                            });
                    });
            });
        }

        $sort = $filters['sort'] ?? 'newest';
        $applications = $query->orderBy('created_at', $sort === 'oldest' ? 'asc' : 'desc')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('employer.applications.job', [
            'job' => $job,
            'applications' => $applications,
            'filters' => $filters,
            'counts' => $job->applications()
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status'),
        ]);
    }

    public function show(Request $request, JobApplication $jobApplication): View
    {
        $employer = $this->employer($request);
        $application = $this->ownedApplicationsFor($employer)
            ->with([
                'job.company.location', 'job.location', 'job.category',
                'user.candidateProfile' => $this->publicProfileProjection(),
            ])
            ->whereKey($jobApplication->getKey())
            ->firstOrFail();

        $profile = $application->user->candidateProfile;
        if ($profile?->is_public) {
            $profile->load(['skills', 'categories', 'educations', 'experiences', 'languages', 'portfolios', 'awards']);
        }

        $application->setRelation('notes', $application->notes()->where('employer_user_id', $employer->getKey())->with('employer')->get());
        $application->load('statusHistory.changedBy');

        return view('employer.applications.show', compact('application', 'profile'));
    }

    public function updateStatus(Request $request, JobApplication $jobApplication): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::enum(JobApplicationStatus::class)]]);
        $employer = $this->employer($request);

        DB::transaction(function () use ($data, $employer, $jobApplication): void {
            $application = $this->ownedApplicationsFor($employer)
                ->whereKey($jobApplication->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $newStatus = $data['status'];
            if ($application->status === $newStatus) {
                return;
            }

            $application->statusHistory()->create([
                'changed_by_user_id' => $employer->getKey(),
                'from_status' => $application->status,
                'to_status' => $newStatus,
                'created_at' => now(),
            ]);
            $application->update(['status' => $newStatus]);
        });

        return back()->with('status', 'Application status updated.');
    }

    public function storeNote(Request $request, JobApplication $jobApplication): RedirectResponse
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $employer = $this->employer($request);

        DB::transaction(function () use ($data, $employer, $jobApplication): void {
            $application = $this->ownedApplicationsFor($employer)
                ->whereKey($jobApplication->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $application->notes()->create([
                'employer_user_id' => $employer->getKey(),
                'body' => trim($data['body']),
            ]);
        });

        return back()->with('status', 'Private review note added.');
    }

    private function employer(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isEmployer(), 403);

        return $user;
    }

    /** @return Builder<JobApplication> */
    private function ownedApplicationsFor(User $employer): Builder
    {
        return JobApplication::query()
            ->whereHas('job', fn (Builder $jobs) => $jobs->ownedByEmployer($employer));
    }

    /**
     * Select only fields intentionally made public by the candidate directory.
     * Private contact details, exact address and resume paths are never loaded here.
     */
    /** @return \Closure(Relation<*, *, *>): mixed */
    private function publicProfileProjection(): \Closure
    {
        /** @param Relation<*, *, *> $profiles */
        return function (Relation $profiles): void {
            $profiles->select([
                'candidate_profiles.id', 'candidate_profiles.user_id', 'candidate_profiles.slug',
                'candidate_profiles.headline', 'candidate_profiles.about', 'candidate_profiles.city',
                'candidate_profiles.state', 'candidate_profiles.experience_level',
                'candidate_profiles.years_experience', 'candidate_profiles.availability_status',
                'candidate_profiles.is_public', 'candidate_profiles.profile_photo_path',
                'candidate_profiles.show_expected_salary', 'candidate_profiles.expected_salary',
                'candidate_profiles.salary_type', 'candidate_profiles.preferred_employment_types',
            ])->where('candidate_profiles.is_public', true);
        };
    }
}
