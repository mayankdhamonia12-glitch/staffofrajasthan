<div>
    @if (session('status'))<x-ui.alert tone="success">{{ session('status') }}</x-ui.alert>@endif
    @if ($errors->any())<x-ui.alert tone="error">{{ $errors->first() }}</x-ui.alert>@endif

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><p class="text-sm font-semibold text-brand-700">Recruitment listings</p><h2 class="mt-1 text-xl font-extrabold text-slate-950">Manage your jobs</h2><p class="mt-1 text-sm text-slate-500">Only jobs attached to your employer company profiles appear here.</p></div><a href="{{ route('employer.jobs.create') }}" class="inline-flex items-center justify-center rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">Post a job <span class="ml-2" aria-hidden="true">+</span></a></div>
        <div class="mt-6 grid gap-3 md:grid-cols-[minmax(220px,1fr)_220px_200px_auto]">
            <label><span class="sr-only">Search jobs</span><input wire:model.live.debounce.350ms="keyword" placeholder="Search job title or company" class="w-full rounded-xl border-slate-300 text-sm"></label>
            <label><span class="sr-only">Filter by status</span><select wire:model.live="status" class="w-full rounded-xl border-slate-300 text-sm"><option value="">All statuses</option><option value="active">Published and active</option><option value="draft">Draft</option><option value="closed">Closed</option><option value="filled">Filled</option><option value="expired">Expired</option><option value="unlisted">Company profile private</option><option value="inactive">Closed, filled or expired</option></select></label>
            <label><span class="sr-only">Sort jobs</span><select wire:model.live="sort" class="w-full rounded-xl border-slate-300 text-sm"><option value="updated">Recently updated</option><option value="created">Recently created</option><option value="published">Publication date</option><option value="title">Job title</option></select></label>
            <button wire:click="clearFilters" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Clear</button>
        </div>
    </section>

    <x-ui.loading wire:loading.delay>Updating your job list…</x-ui.loading>
    @if ($jobs->isEmpty())
        <x-ui.empty-state title="No jobs match this view" description="Try another search or create a listing to begin reaching candidates." :action="route('employer.jobs.create')" action-text="Create a job listing" icon="▤" />
    @else
        <div class="space-y-4">
            @foreach ($jobs as $job)
                <article wire:key="employer-job-{{ $job->id }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex flex-col justify-between gap-5 xl:flex-row xl:items-start">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2"><h3 class="text-lg font-extrabold text-slate-950">{{ $job->title }}</h3><x-employer-job-status :job="$job" /></div>
                            <p class="mt-1 text-sm font-medium text-slate-600">{{ $job->company->name }}</p>
                            <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-sm text-slate-500"><span>{{ $job->location?->name ?? $job->company->location?->name ?? 'Location not set' }}</span><span>{{ \Illuminate\Support\Str::headline($job->employment_type) }}</span><span>Created {{ $job->created_at?->toFormattedDateString() }}</span>@if($job->published_at)<span>Published {{ $job->published_at->toFormattedDateString() }}</span>@endif @if($job->application_deadline)<span>Deadline {{ $job->application_deadline->toFormattedDateString() }}</span>@endif</div>
                            <div class="mt-4 flex flex-wrap gap-4 border-t border-slate-100 pt-4 text-sm"><span class="text-slate-500"><strong class="text-slate-900">{{ number_format($job->applications_count) }}</strong> applications</span>@if($job->salary_min || $job->salary_max)<span class="text-slate-500">Salary <strong class="text-slate-900">₹{{ number_format($job->salary_min ?? $job->salary_max) }}@if($job->salary_min && $job->salary_max) – ₹{{ number_format($job->salary_max) }}@endif @if($job->salary_period)/ {{ \Illuminate\Support\Str::headline($job->salary_period) }}@endif</strong></span>@endif</div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 xl:max-w-[380px] xl:justify-end">
                            <a href="{{ route('employer.jobs.edit', $job) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:border-brand-300 hover:text-brand-700">Edit</a>
                            @if($job->status === 'published' && ! $job->is_filled && $job->application_deadline?->gte(today()) !== false && $job->company->is_visible)<a href="{{ route('jobs.show', $job) }}" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:border-brand-300 hover:text-brand-700">View public</a>@endif
                            <form method="POST" action="{{ route('employer.jobs.duplicate', $job) }}">@csrf<button class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:border-brand-300 hover:text-brand-700">Duplicate</button></form>
                            @if($job->status === 'published' && ! $job->is_filled)
                                <form method="POST" action="{{ route('employer.jobs.lifecycle', $job) }}" onsubmit="return confirm('Close this listing? It will no longer appear in public job search.');">@csrf @method('PATCH')<input type="hidden" name="action" value="close"><button class="rounded-lg border border-rose-200 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-50">Close listing</button></form>
                                <form method="POST" action="{{ route('employer.jobs.lifecycle', $job) }}" onsubmit="return confirm('Mark this position as filled? This hides it from public job search.');">@csrf @method('PATCH')<input type="hidden" name="action" value="filled"><button class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">Mark filled</button></form>
                            @elseif($job->status === 'closed' || $job->is_filled)
                                <form method="POST" action="{{ route('employer.jobs.lifecycle', $job) }}" onsubmit="return confirm('Reopen this position for candidates?');">@csrf @method('PATCH')<input type="hidden" name="action" value="reopen"><button class="rounded-lg bg-brand-700 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-800">Reopen</button></form>
                            @elseif($job->status === 'draft')
                                <a href="{{ route('employer.jobs.edit', $job) }}#job-publish" class="rounded-lg bg-brand-700 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-800">Review and publish</a>
                            @elseif($job->application_deadline?->lt(today()))
                                <a href="{{ route('employer.jobs.edit', $job) }}" class="rounded-lg bg-brand-700 px-3 py-2 text-sm font-semibold text-white hover:bg-brand-800">Update deadline</a>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
        <x-ui.pagination :paginator="$jobs" />
    @endif
</div>
