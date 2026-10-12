@extends('layouts.public')

@section('title', 'Companies Hiring in Rajasthan | Staff of Rajasthan')
@section('description', 'Explore employers with current job openings across Rajasthan.')

@section('content')
    <section class="bg-[#f3f7ff]">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
            <x-ui.breadcrumbs :items="[['label' => 'Companies']]" />
            <x-ui.section-heading class="mt-6" level="1" eyebrow="Find your next team" title="Discover companies in Rajasthan" description="Explore public employer profiles, learn about their teams, and find the roles they are hiring for." />
        </div>
    </section>
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
        <form method="GET" action="{{ route('companies.index') }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="grid gap-4 lg:grid-cols-[1.5fr_1fr_1fr_1fr_auto] lg:items-end">
                <label><span class="text-sm font-semibold text-slate-700">Search companies</span><input name="keyword" value="{{ request('keyword') }}" placeholder="Company name or industry" class="mt-2 w-full rounded-xl border-slate-300"></label>
                <label><span class="text-sm font-semibold text-slate-700">Industry</span><select name="industry" class="mt-2 w-full rounded-xl border-slate-300"><option value="">All industries</option>@foreach($industries as $industry)<option value="{{ $industry->slug }}" @selected(request('industry') === $industry->slug)>{{ $industry->name }}</option>@endforeach</select></label>
                <label><span class="text-sm font-semibold text-slate-700">Location</span><select name="location" class="mt-2 w-full rounded-xl border-slate-300"><option value="">All locations</option>@foreach($locations as $location)<option value="{{ $location->slug }}" @selected(request('location') === $location->slug)>{{ $location->name }}{{ $location->state ? ', '.$location->state : '' }}</option>@endforeach</select></label>
                <label><span class="text-sm font-semibold text-slate-700">Company size</span><select name="company_size" class="mt-2 w-full rounded-xl border-slate-300"><option value="">Any size</option>@foreach($companySizes as $size)<option value="{{ $size }}" @selected(request('company_size') === $size)>{{ $size }} employees</option>@endforeach</select></label>
                <button class="rounded-xl bg-brand-700 px-5 py-3 text-sm font-semibold text-white hover:bg-brand-800">Search</button>
            </div>
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4"><p class="text-sm text-slate-500">{{ number_format($companies->total()) }} public {{ \Illuminate\Support\Str::plural('company', $companies->total()) }}</p><label class="flex items-center gap-2 text-sm text-slate-600">Sort <select name="sort" onchange="this.form.submit()" class="rounded-lg border-slate-300 py-1.5 text-sm"><option value="jobs" @selected(request('sort', 'jobs') === 'jobs')>Most hiring</option><option value="name" @selected(request('sort') === 'name')>Name</option><option value="recent" @selected(request('sort') === 'recent')>Recently updated</option></select></label></div>
        </form>
        @if ($companies->isEmpty())
            <x-ui.empty-state class="mt-8" title="No companies match those filters" description="Try a different search or browse all public employers." :action="route('companies.index')" action-text="Clear filters" icon="⌂" />
        @else
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">@foreach ($companies as $company)<x-company-card :company="$company" />@endforeach</div>
            <x-ui.pagination :paginator="$companies" />
        @endif
    </section>
@endsection
