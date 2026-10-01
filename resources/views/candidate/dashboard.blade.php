<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-bold text-slate-900">Candidate dashboard</h1>
    </x-slot>

    <div class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6">
        <section class="rounded-2xl bg-white p-6 shadow-sm sm:p-8">
            <p class="text-sm font-semibold text-brand-700">Welcome back</p>
            <h2 class="mt-1 text-2xl font-extrabold text-slate-950">{{ auth()->user()->name }}</h2>
            <p class="mt-2 text-sm text-slate-600">Keep your search moving: review saved roles or explore current opportunities.</p>
            <div class="mt-5 flex flex-wrap gap-3">
                <x-ui.button :href="route('jobs.index')">Browse jobs</x-ui.button>
                <x-ui.button :href="route('candidate.profile.edit')" variant="secondary">Update profile</x-ui.button>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2">
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Saved open jobs</p>
                <p class="mt-2 text-3xl font-extrabold text-brand-700">{{ number_format($savedJobCount) }}</p>
                <a href="{{ route('candidate.saved-jobs.index') }}" class="mt-4 inline-flex font-semibold text-brand-700 hover:underline">View saved jobs <span class="ml-1" aria-hidden="true">→</span></a>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Applications recorded</p>
                <p class="mt-2 text-3xl font-extrabold text-brand-700">{{ number_format($applicationCount) }}</p>
                <p class="mt-4 text-sm text-slate-500">Employer review tools and notifications are not available yet.</p>
            </article>
        </section>

        <section>
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div><p class="text-sm font-bold uppercase tracking-wider text-brand-700">Your shortlist</p><h2 class="mt-1 text-xl font-bold text-slate-950">Recently saved jobs</h2></div>
                @if ($recentSavedJobs->isNotEmpty())<a href="{{ route('candidate.saved-jobs.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">See all</a>@endif
            </div>
            @if ($recentSavedJobs->isEmpty())
                <x-ui.empty-state class="mt-5" title="No saved jobs yet" description="Save opportunities that interest you and they’ll be collected here." :action="route('jobs.index')" action-text="Explore current jobs" />
            @else
                <div class="mt-5 grid gap-4 lg:grid-cols-3">
                    @foreach ($recentSavedJobs as $savedJob)
                        <div class="space-y-3">
                            <x-job-card :job="$savedJob->job" />
                            <form method="POST" action="{{ route('candidate.saved-jobs.destroy', $savedJob) }}">@csrf @method('DELETE')<button class="text-sm font-semibold text-rose-700 hover:underline">Remove from saved jobs</button></form>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
