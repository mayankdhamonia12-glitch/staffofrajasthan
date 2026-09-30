@props(['action', 'keyword' => '', 'location' => ''])
<form action="{{ $action }}" method="GET" role="search" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-xl shadow-slate-900/5 lg:grid-cols-[1.2fr_1fr_auto]">
    <x-search-input name="keyword" label="What are you looking for?" placeholder="Job title, skill or company" icon="⌕" :value="$keyword" />
    <x-search-input name="location" label="Where?" placeholder="City or region" icon="⌖" :value="$location" />
    <x-ui.button type="submit" size="lg" class="min-h-14">Search jobs <span aria-hidden="true">→</span></x-ui.button>
</form>
