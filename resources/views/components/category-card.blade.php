@props(['category'])
<a href="{{ route('jobs.index', ['category' => $category->slug]) }}" class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:border-blue-300 hover:shadow-lg">
    <span class="grid h-10 w-10 place-items-center rounded-xl bg-blue-50 text-lg font-bold text-blue-700">{{ mb_substr($category->name, 0, 1) }}</span>
    <h3 class="mt-4 font-bold text-slate-900 group-hover:text-blue-700">{{ $category->name }}</h3>
    <p class="mt-1 text-sm leading-5 text-slate-500">{{ number_format($category->jobs_count) }} {{ \Illuminate\Support\Str::plural('open role', $category->jobs_count) }}</p>
</a>
