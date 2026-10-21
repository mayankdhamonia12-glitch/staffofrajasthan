<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 font-semibold text-sm text-slate-700 shadow-sm transition hover:border-brand-300 hover:bg-brand-50 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:opacity-50']) }}>
    {{ $slot }}
</button>
