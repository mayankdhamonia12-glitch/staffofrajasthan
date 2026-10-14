<x-employer-dashboard-layout title="Applicants" active="applications" subtitle="Review applications for jobs owned by your company.">
    @if (session('status'))<x-ui.alert tone="success">{{ session('status') }}</x-ui.alert>@endif

    <section>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div><p class="text-sm font-semibold text-brand-700">Recruitment activity</p><h2 class="mt-1 text-2xl font-extrabold">Applications overview</h2><p class="mt-1 text-sm text-slate-500">Counts reflect applications submitted to jobs owned by your account.</p></div>
            <span class="rounded-full bg-blue-50 px-4 py-2 text-sm font-bold text-brand-800">{{ number_format($totalApplications) }} total</span>
        </div>
        <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ([['Applied', 'applied', 'bg-blue-50 text-blue-800'], ['Shortlisted', 'shortlisted', 'bg-amber-50 text-amber-800'], ['Interviews', 'interview', 'bg-violet-50 text-violet-800'], ['Selected', 'selected', 'bg-emerald-50 text-emerald-800'], ['Rejected', 'rejected', 'bg-rose-50 text-rose-800']] as [$label, $key, $tone])
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-semibold text-slate-500">{{ $label }}</p><p class="mt-2 text-3xl font-extrabold text-slate-950">{{ number_format($counts[$key]) }}</p><span class="mt-3 inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $tone }}">{{ $label }}</span></article>
            @endforeach
        </div>
    </section>

    <section class="grid items-start gap-6 xl:grid-cols-[minmax(0,1.3fr)_minmax(300px,.7fr)]">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3"><div><p class="text-sm font-semibold text-brand-700">Latest activity</p><h2 class="mt-1 text-xl font-extrabold">Recent applications</h2></div><a href="#jobs" class="text-sm font-semibold text-brand-700 hover:underline">Browse job applicants ↓</a></div>
            @if ($recentApplications->isEmpty())
                <x-ui.empty-state class="mt-5" title="No applications yet" description="Applications will appear here when candidates apply to one of your job listings." :action="route('employer.jobs.index')" action-text="View your jobs" icon="✉" />
            @else
                <div class="mt-4 divide-y divide-slate-100">
                    @foreach ($recentApplications as $application)
                        @php($candidateProfile = $application->user->candidateProfile)
                        <a href="{{ route('employer.applications.show', $application) }}" class="flex items-center gap-3 py-4 hover:bg-slate-50 sm:px-2">
                            <span class="grid h-11 w-11 shrink-0 place-items-center overflow-hidden rounded-full bg-blue-50 font-bold text-brand-700">@if($candidateProfile?->is_public && $candidateProfile->slug && $candidateProfile->profile_photo_path)<img src="{{ route('candidates.photo', $candidateProfile->slug) }}" alt="" class="h-full w-full object-cover">@else{{ mb_substr($application->user->name, 0, 1) }}@endif</span>
                            <span class="min-w-0 flex-1"><span class="block truncate font-bold text-slate-950">{{ $application->user->name }}</span><span class="mt-0.5 block truncate text-sm text-slate-500">{{ $application->job->title }} · {{ $application->job->company->name }}</span><span class="mt-1 block text-xs text-slate-400">Received {{ $application->created_at->toFormattedDateString() }}</span></span>
                            <x-job-application-status :status="$application->status" />
                        </a>
                    @endforeach
                </div>
            @endif
        </article>

        <article id="jobs" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <p class="text-sm font-semibold text-brand-700">By listing</p><h2 class="mt-1 text-xl font-extrabold">Applicants by job</h2>
            @if ($jobs->isEmpty())
                <x-ui.empty-state class="mt-5" title="No job listings" description="Create a job to start receiving applications." :action="route('employer.jobs.create')" action-text="Create a job" icon="▤" />
            @else
                <div class="mt-4 divide-y divide-slate-100">
                    @foreach ($jobs as $job)
                        <a href="{{ route('employer.jobs.applications.index', $job) }}" class="flex items-center justify-between gap-3 py-4 hover:text-brand-700"><span class="min-w-0"><span class="block truncate font-bold text-slate-900">{{ $job->title }}</span><span class="mt-1 block truncate text-sm text-slate-500">{{ $job->company->name }}</span></span><span class="shrink-0 rounded-full bg-slate-100 px-3 py-1.5 text-sm font-bold text-slate-700">{{ number_format($job->applications_count) }}</span></a>
                    @endforeach
                </div>
            @endif
        </article>
    </section>
</x-employer-dashboard-layout>
