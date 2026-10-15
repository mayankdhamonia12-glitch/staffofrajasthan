<x-candidate-dashboard-layout title="{{ $alert->name }}" active="alerts" subtitle="Jobs currently matching this saved search.">
    @if (session('status'))<x-ui.alert tone="success">{{ session('status') }}</x-ui.alert>@endif
    <div class="flex flex-wrap items-end justify-between gap-4"><div><a href="{{ route('candidate.job-alerts.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">← All job alerts</a><div class="mt-3 flex flex-wrap items-center gap-3"><h2 class="text-2xl font-extrabold">{{ $alert->name }}</h2><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $alert->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $alert->is_active ? 'Active' : 'Paused' }}</span></div><p class="mt-2 text-sm text-slate-500">Current public matches. New roles will notify you only while the alert is active.</p></div><a href="{{ route('candidate.job-alerts.edit', $alert) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Edit filters</a></div>
    @if ($jobs->isEmpty())
        <x-ui.empty-state title="No current matching jobs" description="This alert is ready. When a new public job matches these filters, it will be added to your notifications." :action="route('jobs.index')" action-text="Browse all jobs" icon="⌕" />
    @else
        <div class="space-y-4">@foreach($jobs as $job)<x-job-card :job="$job" layout="list" />@endforeach</div>
        <x-ui.pagination :paginator="$jobs" />
    @endif
</x-candidate-dashboard-layout>
