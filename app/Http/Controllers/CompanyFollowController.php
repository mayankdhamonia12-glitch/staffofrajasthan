<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyFollowController extends Controller
{
    public function index(Request $request): View
    {
        $candidate = $this->candidate($request);
        $companies = $candidate->followedCompanies()
            ->where('companies.is_visible', true)
            ->with(['industry', 'location'])
            ->withCount(['jobs as open_jobs_count' => fn ($jobs) => $jobs->publiclyAvailable()])
            ->orderBy('companies.name')
            ->paginate(12);

        return view('candidate.following.index', compact('companies'));
    }

    public function store(Request $request, Company $company): RedirectResponse
    {
        abort_unless($company->is_visible, 404);
        $candidate = $this->candidate($request);
        $candidate->followedCompanies()->syncWithoutDetaching([$company->getKey()]);

        return back()->with('status', 'You are now following '.$company->name.'. New public roles from this company will appear in your notifications.');
    }

    public function destroy(Request $request, Company $company): RedirectResponse
    {
        $this->candidate($request)->followedCompanies()->detach($company->getKey());

        return back()->with('status', 'Company removed from your followed employers.');
    }

    private function candidate(Request $request): User
    {
        $candidate = $request->user();
        abort_unless($candidate instanceof User && $candidate->isCandidate(), 403);

        return $candidate;
    }
}
