@props(['category'])
<a href="{{ route('jobs.index', ['category' => $category->slug]) }}" class="group flex h-full items-start gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:border-brand-300 hover:shadow-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
    <span aria-hidden="true" class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700 transition group-hover:bg-brand-700 group-hover:text-white">
        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5h18v12H3zM8 7.5V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2.5M3 12h18M10 12v2h4v-2" /></svg>
    </span>
    <span class="min-w-0"><span class="block font-bold leading-6 text-slate-900 transition group-hover:text-brand-700">{{ $category->name }}</span><span class="mt-1 block text-sm leading-5 text-slate-500">{{ number_format($category->jobs_count) }} {{ \Illuminate\Support\Str::plural('open role', $category->jobs_count) }}</span></span>
</a>
