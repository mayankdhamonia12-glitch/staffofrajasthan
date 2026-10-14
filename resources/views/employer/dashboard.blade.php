<x-employer-dashboard-layout title="Overview" active="dashboard" subtitle="A clear view of your jobs and hiring activity.">
    @if (session('status'))<x-ui.alert tone="success">{{ session('status') }}</x-ui.alert>@endif

    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-[#092b5b] via-[#104481] to-[#1762a9] p-6 text-white shadow-sm sm:p-8">
        <div class="absolute -right-10 -top-20 h-64 w-64 rounded-full border-[30px] border-white/5"></div>
        <div class="relative flex flex-col justify-between gap-6 md:flex-row md:items-end"><div class="max-w-2xl"><p class="text-sm font-semibold uppercase tracking-[.16em] text-blue-200">Employer workspace</p><h2 class="mt-2 text-3xl font-extrabold sm:text-4xl">Welcome back, {{ auth()->user()->name }}</h2><p class="mt-3 text-sm leading-6 text-blue-100 sm:text-base">Manage your company profile and keep every job listing up to date.</p></div><a href="{{ route('employer.jobs.create') }}" class="inline-flex shrink-0 items-center justify-center rounded-xl bg-white px-5 py-3 text-sm font-bold text-brand-800 shadow-sm transition hover:bg-blue-50">Post a new job <span class="ml-2" aria-hidden="true">→</span></a></div>
    </section>

    @if ($companies->isEmpty())
        <section class="flex flex-col justify-between gap-4 rounded-2xl border border-amber-200 bg-amber-50 p-5 sm:flex-row sm:items-center"><div><h2 class="font-bold text-amber-950">Set up your company profile first</h2><p class="mt-1 text-sm text-amber-900">Create a profile to attach listings to the right employer and publish them publicly.</p></div><a href="{{ route('employer.companies.create') }}" class="inline-flex shrink-0 rounded-xl bg-amber-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-amber-950">Create company profile</a></section>
    @endif

    <section aria-label="Employer job statistics" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ([['Total jobs', $statistics['total'], '▤', 'employer.jobs.index', null], ['Published now', $statistics['active'], '↗', 'employer.jobs.index', 'active'], ['Drafts', $statistics['drafts'], '✎', 'employer.jobs.index', 'draft'], ['Closed, filled or expired', $statistics['closed'], '◷', 'employer.jobs.index', 'inactive'], ['Applications received', $statistics['applications'], '✉', 'employer.applications.index', null]] as [$label, $value, $icon, $route, $filter])
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-center justify-between"><p class="text-sm font-semibold text-slate-500">{{ $label }}</p><span class="grid h-10 w-10 place-items-center rounded-xl bg-blue-50 text-lg text-brand-700" aria-hidden="true">{{ $icon }}</span></div><p class="mt-3 text-3xl font-extrabold text-slate-950">{{ number_format($value) }}</p><a href="{{ route($route, $filter ? ['status' => $filter] : []) }}" class="mt-3 inline-flex text-sm font-semibold text-brand-700 hover:underline">{{ $route === 'employer.applications.index' ? 'Review applicants' : 'View listings' }} →</a></article>
        @endforeach
    </section>

    <section class="grid items-start gap-6 xl:grid-cols-[minmax(0,1.35fr)_minmax(280px,.65fr)]">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-4"><div><p class="text-sm font-semibold text-brand-700">Recruitment listings</p><h2 class="mt-1 text-xl font-extrabold text-slate-950">Recently updated jobs</h2><p class="mt-1 text-sm text-slate-500">Your latest job changes and current listing status.</p></div><a href="{{ route('employer.jobs.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">Manage all jobs →</a></div>
            @if ($recentJobs->isEmpty())
                <x-ui.empty-state class="mt-5" title="Your job list is ready" description="Post your first role to start reaching candidates across Rajasthan." :action="route('employer.jobs.create')" action-text="Create a job listing" icon="▤" />
            @else
                <div class="mt-5 divide-y divide-slate-100">@foreach($recentJobs as $job)<div class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between"><div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><a href="{{ route('employer.jobs.edit', $job) }}" class="truncate font-bold text-slate-950 hover:text-brand-700">{{ $job->title }}</a><x-employer-job-status :job="$job" /></div><p class="mt-1 text-sm text-slate-500">{{ $job->company->name }} · {{ $job->location?->name ?? $job->company->location?->name ?? 'Location not set' }} · {{ \Illuminate\Support\Str::headline($job->employment_type) }}</p></div><div class="flex items-center justify-between gap-4 text-sm sm:justify-end"><span class="text-slate-500">{{ number_format($job->applications_count) }} applications</span><a href="{{ route('employer.jobs.edit', $job) }}" class="font-semibold text-brand-700 hover:underline">Edit</a></div></div>@endforeach</div>
            @endif
        </article>

        <aside class="space-y-4">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-center justify-between gap-3"><div><p class="text-sm font-semibold text-brand-700">Your companies</p><h2 class="mt-1 text-lg font-extrabold text-slate-950">Company profiles</h2></div><a href="{{ route('employer.companies.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">Manage</a></div>
                @forelse($companies as $company)<div class="mt-4 flex items-center gap-3 border-t border-slate-100 pt-4"><div class="grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-xl bg-brand-50 font-bold text-brand-700">@if($company->logo_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($company->logo_path) }}" alt="" class="h-full w-full object-cover">@else{{ mb_substr($company->name, 0, 1) }}@endif</div><div class="min-w-0 flex-1"><p class="truncate text-sm font-bold text-slate-900">{{ $company->name }}</p><p class="truncate text-xs text-slate-500">{{ $company->industry?->name ?? 'Industry not set' }} · {{ $company->jobs_count }} jobs</p></div><span class="h-2.5 w-2.5 rounded-full {{ $company->is_visible ? 'bg-emerald-500' : 'bg-slate-300' }}" title="{{ $company->is_visible ? 'Public profile' : 'Private profile' }}"></span></div>@empty<p class="mt-4 border-t border-slate-100 pt-4 text-sm leading-6 text-slate-500">Create a company profile to start posting jobs.</p>@endforelse
            </article>
            <article class="rounded-2xl border border-blue-100 bg-blue-50 p-5"><h2 class="font-bold text-slate-950">Need another listing?</h2><p class="mt-2 text-sm leading-6 text-slate-600">Create a draft first, then publish when the role and company profile are ready.</p><a href="{{ route('employer.jobs.create') }}" class="mt-4 inline-flex rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">Post a job</a></article>
        </aside>
    </section>
</x-employer-dashboard-layout>
