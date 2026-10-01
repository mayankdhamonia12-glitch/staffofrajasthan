<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-xl font-bold text-slate-900">Saved jobs</h1>
            <span class="text-sm font-medium text-slate-600">{{ number_format($savedJobCount) }} {{ \Illuminate\Support\Str::plural('open job', $savedJobCount) }} saved</span>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        @if (session('status'))<div role="status" class="mb-5 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>@endif
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
                    <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
                        <x-job-card :job="$savedJob->job" layout="list" />
                        <form method="POST" action="{{ route('candidate.saved-jobs.destroy', $savedJob) }}">@csrf @method('DELETE')<x-ui.button type="submit" variant="secondary" size="sm" class="w-full sm:w-auto">Remove</x-ui.button></form>
                    </div>
                @endforeach
            </div>
            <div class="mt-6"><x-ui.pagination :paginator="$savedJobs" /></div>
        @endif
    </div>
</x-app-layout>
