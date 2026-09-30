@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full rounded-xl border-slate-200 bg-white shadow-sm placeholder:text-slate-400 focus:border-brand-500 focus:ring-brand-500 disabled:bg-slate-50']) }}>
