@props(['name', 'label', 'placeholder', 'icon', 'value' => null])
<label class="flex min-w-0 items-center gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3.5 transition focus-within:border-brand-400 focus-within:ring-2 focus-within:ring-brand-100">
    <span aria-hidden="true" class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-brand-50 text-brand-700">{{ $icon }}</span>
    <span class="min-w-0 flex-1">
        <span class="block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</span>
        <input type="{{ $name === 'keyword' ? 'search' : 'text' }}" name="{{ $name }}" value="{{ $value }}" placeholder="{{ $placeholder }}" class="mt-0.5 w-full border-0 p-0 text-sm text-slate-900 placeholder:text-slate-400 focus:ring-0" />
    </span>
</label>
