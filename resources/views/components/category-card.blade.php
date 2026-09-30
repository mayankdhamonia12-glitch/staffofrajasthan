@props(['name', 'description'])
<a href="#jobs" class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:border-orange-300 hover:shadow-lg">
    <span class="grid h-10 w-10 place-items-center rounded-xl bg-orange-50 text-lg font-bold text-orange-700">{{ mb_substr($name, 0, 1) }}</span>
    <h3 class="mt-4 font-bold text-slate-900 group-hover:text-orange-700">{{ $name }}</h3>
    <p class="mt-1 text-sm leading-5 text-slate-500">{{ $description }}</p>
</a>
