<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\View\View;

class PublicPageController extends Controller
{
    public function candidates(): View
    {
        return view('public.candidates');
    }

    public function companies(): View
    {
        $companies = Company::query()
            ->where('is_visible', true)
            ->whereHas('jobs', fn ($query) => $query->publiclyAvailable())
            ->with(['industry', 'location'])
            ->withCount(['jobs' => fn ($query) => $query->publiclyAvailable()])
            ->orderByDesc('jobs_count')
            ->paginate(12);

        return view('public.companies', compact('companies'));
    }

    public function showCompany(Company $company): View
    {
        abort_unless($company->is_visible && $company->jobs()->publiclyAvailable()->exists(), 404);

        $company->load(['industry', 'location']);
        $jobs = $company->jobs()
            ->publiclyAvailable()
            ->with(['company.location', 'location', 'category', 'skills'])
            ->latest('published_at')
            ->paginate(10);

        return view('public.company', compact('company', 'jobs'));
    }

    public function blog(): View
    {
        return view('public.blog');
    }

    public function about(): View
    {
        return view('public.about');
    }

    public function contact(): View
    {
        return view('public.contact');
    }
}
