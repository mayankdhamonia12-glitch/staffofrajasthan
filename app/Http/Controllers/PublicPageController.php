<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Industry;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicPageController extends Controller
{
    public function companies(Request $request): View
    {
        $companies = Company::query()
            ->publiclyVisible()
            ->with(['industry', 'location'])
            ->withCount(['jobs' => fn ($query) => $query->publiclyAvailable()])
            ->when($request->filled('keyword'), function ($query) use ($request): void {
                $keyword = trim((string) $request->string('keyword'));
                $query->where(function ($companyQuery) use ($keyword): void {
                    $companyQuery
                        ->where('name', 'like', '%'.$keyword.'%')
                        ->orWhere('description', 'like', '%'.$keyword.'%')
                        ->orWhereHas('industry', fn ($industryQuery) => $industryQuery->where('name', 'like', '%'.$keyword.'%'));
                });
            })
            ->when($request->filled('industry'), fn ($query) => $query->whereHas('industry', fn ($industryQuery) => $industryQuery->where('slug', $request->string('industry')->toString())))
            ->when($request->filled('location'), fn ($query) => $query->whereHas('location', fn ($locationQuery) => $locationQuery->where('slug', $request->string('location')->toString())))
            ->when($request->filled('company_size'), fn ($query) => $query->where('company_size', $request->string('company_size')->toString()))
            ->when($request->string('sort')->toString() === 'name', fn ($query) => $query->orderBy('name'))
            ->when($request->string('sort')->toString() === 'recent', fn ($query) => $query->latest())
            ->when(! in_array($request->string('sort')->toString(), ['name', 'recent'], true), fn ($query) => $query->orderByDesc('jobs_count')->orderBy('name'))
            ->paginate(12)
            ->withQueryString();

        $industries = Industry::query()->where('is_active', true)->orderBy('name')->get(['name', 'slug']);
        $locations = Location::query()->where('is_active', true)->orderBy('name')->get(['name', 'slug', 'state']);
        $companySizes = ['1-10', '11-50', '51-200', '201-500', '501-1000', '1001-5000', '5001+'];

        return view('public.companies', compact('companies', 'industries', 'locations', 'companySizes'));
    }

    public function showCompany(Request $request, Company $company): View
    {
        abort_unless($company->is_visible, 404);

        $company->load(['industry', 'location'])->loadCount(['jobs' => fn ($query) => $query->publiclyAvailable()]);
        $jobs = $company->jobs()
            ->publiclyAvailable()
            ->with(['company.location', 'location', 'category', 'skills'])
            ->latest('published_at')
            ->paginate(10);

        $user = $request->user();
        $isFollowing = $user instanceof User && $user->isCandidate()
            ? $company->followers()->whereKey($user->getKey())->exists()
            : false;

        return view('public.company', compact('company', 'jobs', 'isFollowing'));
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
