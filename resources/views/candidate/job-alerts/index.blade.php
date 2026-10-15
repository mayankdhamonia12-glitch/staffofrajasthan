<x-candidate-dashboard-layout title="Job Alerts" active="alerts" subtitle="Save searches and get notified about new matching public roles.">
    @if (session('status'))<x-ui.alert tone="success">{{ session('status') }}</x-ui.alert>@endif
    <div class="flex flex-wrap items-end justify-between gap-4"><div><p class="text-sm font-semibold text-brand-700">Saved searches</p><h2 class="mt-1 text-2xl font-extrabold">Your job alerts</h2><p class="mt-1 text-sm text-slate-500">Alerts notify you when a newly published public role matches your filters.</p></div><a href="{{ route('candidate.job-alerts.create') }}" class="rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-bold text-white hover:bg-brand-800">Create an alert <span aria-hidden="true">+</span></a></div>
    @if ($alerts->isEmpty())
        <x-ui.empty-state title="No job alerts yet" description="Save a search with a keyword, category, industry, location, or role type to hear about new matching jobs." :action="route('candidate.job-alerts.create')" action-text="Create your first alert" icon="⌕" />
    @else
        <div class="space-y-3">
            @foreach ($alerts as $alert)
                <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:flex sm:items-center sm:justify-between sm:gap-5">
                    <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><h3 class="text-lg font-bold"><a href="{{ route('candidate.job-alerts.show', $alert) }}" class="hover:text-brand-700">{{ $alert->name }}</a></h3><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $alert->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $alert->is_active ? 'Active' : 'Paused' }}</span></div>
                        <p class="mt-2 text-sm text-slate-600">{{ collect([$alert->keyword, $alert->category?->name, $alert->industry?->name, $alert->location?->name, $alert->employment_type ? str($alert->employment_type)->replace('_', ' ')->title()->toString() : null, $alert->experience_level ? str($alert->experience_level)->title()->toString() : null])->filter()->join(' · ') ?: 'All public jobs' }}</p>
                        <p class="mt-2 text-xs text-slate-500">{{ number_format($alert->matches_count) }} {{ \Illuminate\Support\Str::plural('new match recorded', $alert->matches_count) }}</p>
                    </div>
                    <div class="mt-4 flex shrink-0 flex-wrap gap-2 sm:mt-0">
                        <a href="{{ route('candidate.job-alerts.show', $alert) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Matching jobs</a>
                        <a href="{{ route('candidate.job-alerts.edit', $alert) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Edit</a>
                        <form method="POST" action="{{ route('candidate.job-alerts.active', $alert) }}">@csrf @method('PATCH')<input type="hidden" name="is_active" value="{{ $alert->is_active ? '0' : '1' }}"><button class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ $alert->is_active ? 'Pause' : 'Resume' }}</button></form>
                        <form method="POST" action="{{ route('candidate.job-alerts.destroy', $alert) }}" onsubmit="return confirm('Delete this job alert?')">@csrf @method('DELETE')<button class="rounded-lg px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">Delete</button></form>
                    </div>
                </article>
            @endforeach
        </div>
        <x-ui.pagination :paginator="$alerts" />
    @endif
</x-candidate-dashboard-layout>
