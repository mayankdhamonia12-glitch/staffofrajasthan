<?php

namespace App\Http\Controllers;

use App\Models\Industry;
use App\Models\JobAlert;
use App\Models\JobCategory;
use App\Models\Location;
use App\Models\User;
use App\Services\JobAlertMatchingService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CandidateJobAlertController extends Controller
{
    public function __construct(private readonly JobAlertMatchingService $matching) {}

    public function index(Request $request): View
    {
        $alerts = $this->candidate($request)->jobAlerts()
            ->with(['category', 'industry', 'location'])
            ->withCount('matches')
            ->latest()
            ->paginate(10);

        return view('candidate.job-alerts.index', compact('alerts'));
    }

    public function create(Request $request): View
    {
        return view('candidate.job-alerts.form', [...$this->formData(), 'alert' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $alert = $this->candidate($request)->jobAlerts()->create($this->attributes($data, true));

        return redirect()->route('candidate.job-alerts.show', $alert)->with('status', 'Job alert created. It will notify you when a new public job matches these filters.');
    }

    public function show(Request $request, int $jobAlert): View
    {
        $alert = $this->ownedAlert($request, $jobAlert)->load(['category', 'industry', 'location']);
        $jobs = $this->matching->matchingJobs($alert)->paginate(10)->withQueryString();

        return view('candidate.job-alerts.show', compact('alert', 'jobs'));
    }

    public function edit(Request $request, int $jobAlert): View
    {
        $alert = $this->ownedAlert($request, $jobAlert);

        return view('candidate.job-alerts.form', [...$this->formData(), 'alert' => $alert]);
    }

    public function update(Request $request, int $jobAlert): RedirectResponse
    {
        $alert = $this->ownedAlert($request, $jobAlert);
        $data = $this->validated($request);
        $alert->update($this->attributes($data, false));

        return redirect()->route('candidate.job-alerts.show', $alert)->with('status', 'Job alert updated. New matching jobs will use your updated filters.');
    }

    public function setActive(Request $request, int $jobAlert): RedirectResponse
    {
        $alert = $this->ownedAlert($request, $jobAlert);
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $alert->update(['is_active' => (bool) $data['is_active']]);

        return back()->with('status', $alert->is_active ? 'Job alert resumed.' : 'Job alert paused.');
    }

    public function destroy(Request $request, int $jobAlert): RedirectResponse
    {
        $this->ownedAlert($request, $jobAlert)->delete();

        return redirect()->route('candidate.job-alerts.index')->with('status', 'Job alert deleted.');
    }

    /** @return array{categories: Collection<int, JobCategory>, industries: Collection<int, Industry>, locations: Collection<int, Location>} */
    private function formData(): array
    {
        return [
            'categories' => JobCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'industries' => Industry::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'locations' => Location::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'state']),
        ];
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'keyword' => ['nullable', 'string', 'max:120'],
            'job_category_id' => ['nullable', 'integer', Rule::exists('job_categories', 'id')->where('is_active', true)],
            'industry_id' => ['nullable', 'integer', Rule::exists('industries', 'id')->where('is_active', true)],
            'location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->where('is_active', true)],
            'employment_type' => ['nullable', Rule::in(['full_time', 'part_time', 'contract', 'temporary', 'internship'])],
            'experience_level' => ['nullable', Rule::in(['entry', 'junior', 'mid', 'senior', 'lead', 'executive'])],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data, bool $new): array
    {
        $data['name'] = trim((string) $data['name']);
        $data['keyword'] = isset($data['keyword']) && trim((string) $data['keyword']) !== '' ? trim((string) $data['keyword']) : null;
        $data['is_active'] = $new ? (bool) ($data['is_active'] ?? true) : (bool) ($data['is_active'] ?? false);

        return $data;
    }

    private function candidate(Request $request): User
    {
        $candidate = $request->user();
        abort_unless($candidate instanceof User && $candidate->isCandidate(), 403);

        return $candidate;
    }

    private function ownedAlert(Request $request, int $id): JobAlert
    {
        return $this->candidate($request)->jobAlerts()->whereKey($id)->firstOrFail();
    }
}
