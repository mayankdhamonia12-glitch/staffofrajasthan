<x-employer-dashboard-layout :title="$job->title.' applicants'" active="applications" subtitle="Applications for this job listing.">
    <x-ui.breadcrumbs :items="[['label' => 'Applicants', 'url' => route('employer.applications.index')], ['label' => $job->title]]" />
    @if (session('status'))<x-ui.alert tone="success">{{ session('status') }}</x-ui.alert>@endif

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3"><div><p class="text-sm font-semibold text-brand-700">{{ $job->company->name }}</p><h2 class="mt-1 text-2xl font-extrabold">{{ $job->title }}</h2><p class="mt-1 text-sm text-slate-500">{{ number_format($applications->total()) }} {{ \Illuminate\Support\Str::plural('application', $applications->total()) }}</p></div><a href="{{ route('employer.jobs.edit', $job) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Manage job</a></div>
        <div class="mt-5 flex flex-wrap gap-2">@foreach (\App\JobApplicationStatus::cases() as $status)<span class="rounded-full bg-slate-50 px-3 py-1.5 text-xs font-semibold text-slate-600">{{ $status->label() }}: {{ number_format((int) ($counts[$status->value] ?? 0)) }}</span>@endforeach</div>
    </section>

    <form method="GET" action="{{ route('employer.jobs.applications.index', $job) }}" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-[minmax(0,1fr)_200px_180px_auto_auto] md:items-end">
        <label class="block text-sm font-semibold text-slate-700">Search candidates<input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, title, city or skill" class="mt-1.5 block w-full rounded-xl border-slate-300 text-sm focus:border-brand-600 focus:ring-brand-600"></label>
        <label class="block text-sm font-semibold text-slate-700">Status<select name="status" class="mt-1.5 block w-full rounded-xl border-slate-300 text-sm focus:border-brand-600 focus:ring-brand-600"><option value="">All statuses</option>@foreach (\App\JobApplicationStatus::options() as $value => $label)<option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>@endforeach</select></label>
        <label class="block text-sm font-semibold text-slate-700">Date order<select name="sort" class="mt-1.5 block w-full rounded-xl border-slate-300 text-sm focus:border-brand-600 focus:ring-brand-600"><option value="newest" @selected(($filters['sort'] ?? 'newest') === 'newest')>Newest first</option><option value="oldest" @selected(($filters['sort'] ?? '') === 'oldest')>Oldest first</option></select></label>
        <button class="rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-brand-800">Apply filters</button><a href="{{ route('employer.jobs.applications.index', $job) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">Clear</a>
    </form>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @if ($applications->isEmpty())
            <div class="p-6"><x-ui.empty-state title="No matching applicants" description="Try changing your search or status filter. New applications to this job will appear here." /></div>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach ($applications as $application)
                    @php($candidateProfile = $application->user->candidateProfile)
                    <li class="p-4 sm:p-5"><div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                        <span class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-full bg-blue-50 text-lg font-bold text-brand-700">@if($candidateProfile?->is_public && $candidateProfile->slug && $candidateProfile->profile_photo_path)<img src="{{ route('candidates.photo', $candidateProfile->slug) }}" alt="" class="h-full w-full object-cover">@else{{ mb_substr($application->user->name, 0, 1) }}@endif</span>
                        <div class="min-w-0 flex-1"><div class="flex flex-wrap items-center gap-2"><h3 class="font-bold text-slate-950">{{ $application->user->name }}</h3><x-job-application-status :status="$application->status" /></div><p class="mt-1 text-sm text-slate-600">{{ $candidateProfile?->is_public ? ($candidateProfile->headline ?: 'Candidate') : 'Private candidate profile' }} · Applied {{ $application->created_at->toFormattedDateString() }}</p>
                            @if($candidateProfile?->is_public && $candidateProfile->skills->isNotEmpty())<div class="mt-2 flex flex-wrap gap-1.5">@foreach($candidateProfile->skills->take(5) as $skill)<span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs text-slate-600">{{ $skill->name }}</span>@endforeach</div>@endif
                        </div>
                        <a href="{{ route('employer.applications.show', $application) }}" class="inline-flex shrink-0 items-center justify-center rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-brand-800">Review application <span class="ml-2" aria-hidden="true">→</span></a>
                    </div></li>
                @endforeach
            </ul>
            <div class="border-t border-slate-100 p-4">{{ $applications->links() }}</div>
        @endif
    </section>
</x-employer-dashboard-layout>
