<div>
    <section class="border-b border-slate-200 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
            <p class="text-sm font-semibold text-blue-700">Career opportunities across Rajasthan</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Find a job that fits your life</h1>
            <div class="mt-7 grid gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm md:grid-cols-[1.4fr_1fr_auto]">
                <label class="flex items-center gap-3 rounded-xl px-3 py-2"><span class="text-slate-400" aria-hidden="true">⌕</span><span class="sr-only">Keyword</span><input wire:model.live.debounce.350ms="keyword" class="w-full border-0 p-0 focus:ring-0" placeholder="Job title, company or skill"></label>
                <label class="flex items-center gap-3 rounded-xl border-slate-200 px-3 py-2 md:border-l"><span class="text-slate-400" aria-hidden="true">⌖</span><span class="sr-only">Location</span><input wire:model.live.debounce.350ms="location" class="w-full border-0 p-0 focus:ring-0" placeholder="City or location"></label>
                <button wire:click="applyFilters" class="rounded-xl bg-blue-700 px-6 py-3 font-semibold text-white transition hover:bg-blue-800">Search jobs</button>
            </div>
            <p class="mt-3 text-sm text-slate-500">Search by role, company, skill, or Rajasthan location.</p>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div><h2 class="text-2xl font-bold text-slate-950">Browse jobs</h2><p class="mt-1 text-sm text-slate-500">{{ number_format($resultCount) }} {{ \Illuminate\Support\Str::plural('opportunity', $resultCount) }} match your search</p></div>
            <div class="flex items-center gap-3">
                <label class="sr-only" for="job-sort">Sort jobs</label>
                <select id="job-sort" wire:model.live="sort" class="rounded-lg border-slate-300 bg-white text-sm"><option value="newest">Most recent</option><option value="salary_high">Salary: high to low</option><option value="salary_low">Salary: low to high</option></select>
                <div class="hidden rounded-lg border border-slate-200 bg-white p-1 sm:flex" aria-label="Listing layout">
                    <button wire:click="$set('view', 'list')" aria-label="List view" class="rounded-md px-3 py-2 text-sm {{ $view === 'list' ? 'bg-blue-50 text-blue-700' : 'text-slate-500' }}">☰</button>
                    <button wire:click="$set('view', 'grid')" aria-label="Grid view" class="rounded-md px-3 py-2 text-sm {{ $view === 'grid' ? 'bg-blue-50 text-blue-700' : 'text-slate-500' }}">▦</button>
                </div>
            </div>
        </div>

        <div class="mt-7 grid items-start gap-7 lg:grid-cols-[280px_1fr]">
            <div class="lg:hidden">
                <button wire:click="toggleFilters" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-left font-semibold">{{ $showFilters ? 'Hide filters' : 'Filter jobs' }}</button>
            </div>
            <div wire:click.self="toggleFilters" class="{{ $showFilters ? 'fixed' : 'hidden' }} inset-0 z-50 bg-slate-950/40 p-0 lg:static lg:z-auto lg:block lg:bg-transparent lg:p-0">
                <aside class="h-full w-full max-w-sm overflow-y-auto bg-white p-5 shadow-xl lg:sticky lg:top-5 lg:h-auto lg:w-auto lg:max-w-none lg:rounded-2xl lg:border lg:border-slate-200 lg:shadow-sm">
                <div class="flex items-center justify-between"><h2 class="font-bold text-slate-950">Filters</h2><div class="flex items-center gap-4"><button wire:click="clearFilters" class="text-sm font-semibold text-blue-700 hover:text-blue-900">Clear all</button><button wire:click="toggleFilters" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden" aria-label="Close filters">×</button></div></div>
                <div class="mt-5 space-y-5">
                    <label class="block text-sm font-semibold text-slate-700">Category<select wire:model.live="category" class="mt-2 w-full rounded-lg border-slate-300 text-sm"><option value="">All categories</option>@foreach ($categories as $item)<option value="{{ $item->slug }}">{{ $item->name }}</option>@endforeach</select></label>
                    <label class="block text-sm font-semibold text-slate-700">Industry<select wire:model.live="industry" class="mt-2 w-full rounded-lg border-slate-300 text-sm"><option value="">All industries</option>@foreach ($industries as $item)<option value="{{ $item->slug }}">{{ $item->name }}</option>@endforeach</select></label>
                    <label class="block text-sm font-semibold text-slate-700">Job type<select wire:model.live="employmentType" class="mt-2 w-full rounded-lg border-slate-300 text-sm"><option value="">All job types</option>@foreach ($employmentTypes as $item)<option value="{{ $item }}">{{ \Illuminate\Support\Str::headline($item) }}</option>@endforeach</select></label>
                    <label class="block text-sm font-semibold text-slate-700">Experience<select wire:model.live="experienceLevel" class="mt-2 w-full rounded-lg border-slate-300 text-sm"><option value="">All experience</option>@foreach ($experienceLevels as $item)<option value="{{ $item }}">{{ $item }}</option>@endforeach</select></label>
                    <label class="block text-sm font-semibold text-slate-700">Minimum salary (₹ / year)<input wire:model.live.debounce.400ms="minimumSalary" type="number" min="0" class="mt-2 w-full rounded-lg border-slate-300 text-sm" placeholder="e.g. 300000"></label>
                    <label class="block text-sm font-semibold text-slate-700">Date posted<select wire:model.live="postedWithin" class="mt-2 w-full rounded-lg border-slate-300 text-sm"><option value="">Any time</option><option value="1">Past 24 hours</option><option value="7">Past week</option><option value="30">Past month</option></select></label>
                    <label class="block text-sm font-semibold text-slate-700">Location<select wire:model.live="location" class="mt-2 w-full rounded-lg border-slate-300 text-sm"><option value="">All locations</option>@foreach ($locations as $item)<option value="{{ $item->name }}">{{ $item->name }}</option>@endforeach</select></label>
                </div>
                </aside>
            </div>

            <div>
                <x-ui.loading wire:loading.delay class="mb-4">Updating job results…</x-ui.loading>
                @if ($jobs->isEmpty())
                    <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center"><span class="mx-auto grid h-14 w-14 place-items-center rounded-full bg-slate-100 text-2xl text-slate-500">⌕</span><h2 class="mt-5 text-xl font-bold text-slate-900">No matching jobs yet</h2><p class="mt-2 text-slate-600">Try another keyword or clear some filters to see more opportunities.</p><button wire:click="clearFilters" class="mt-5 rounded-lg bg-blue-700 px-4 py-2.5 font-semibold text-white hover:bg-blue-800">Clear filters</button></div>
                @else
                    <div class="grid gap-4 {{ $view === 'grid' ? 'sm:grid-cols-2' : '' }}">
                        @foreach ($jobs as $job)<x-job-card :job="$job" :layout="$view" />@endforeach
                    </div>
                    <x-ui.pagination :paginator="$jobs" />
                @endif
            </div>
        </div>
    </section>
</div>
