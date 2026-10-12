<x-candidate-dashboard-layout title="Saved Jobs" active="saved" subtitle="Keep interesting opportunities close at hand.">
    @if (session('status'))<div role="status" class="rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>@endif
    <div class="flex flex-wrap items-end justify-between gap-3"><div><p class="text-sm font-semibold text-brand-700">Your shortlist</p><h2 class="mt-1 text-2xl font-extrabold">Saved jobs</h2></div><span class="text-sm font-medium text-slate-600">{{ number_format($savedJobCount) }} {{ \Illuminate\Support\Str::plural('open job', $savedJobCount) }} saved</span></div>
        <form method="GET" action="{{ route('candidate.saved-jobs.index') }}" class="mb-6 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:flex-row">
            <label class="sr-only" for="saved-job-search">Search saved jobs</label>
            <input id="saved-job-search" name="search" value="{{ $search }}" type="search" placeholder="Search title, company, or location" class="min-h-11 flex-1 rounded-xl border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500">
            <x-ui.button type="submit">Search saved jobs</x-ui.button>
        </form>

        @if ($savedJobs->isEmpty())
            <x-ui.empty-state title="No saved jobs found" description="Save a current opportunity from its job page, or adjust your search." :action="route('jobs.index')" action-text="Browse current jobs" />
        @else
            <div class="space-y-4">
                @foreach ($savedJobs as $savedJob)
                    <div class="space-y-2"><x-job-card :job="$savedJob->job" layout="list" /><div class="flex flex-wrap items-center justify-between gap-2 px-2"><p class="text-xs text-slate-500">Saved {{ $savedJob->created_at->toFormattedDateString() }}</p><form method="POST" action="{{ route('candidate.saved-jobs.destroy', $savedJob) }}">@csrf @method('DELETE')<button type="submit" class="text-sm font-semibold text-rose-700 hover:underline">Remove from saved jobs</button></form></div>
                    </div>
                @endforeach
            </div>
            <div class="mt-6"><x-ui.pagination :paginator="$savedJobs" /></div>
        @endif
</x-candidate-dashboard-layout>
