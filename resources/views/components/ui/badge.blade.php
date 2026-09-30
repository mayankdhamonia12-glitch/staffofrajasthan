@props(['tone' => 'blue'])
@php
    $tones = ['blue' => 'bg-brand-50 text-brand-800', 'green' => 'bg-emerald-50 text-emerald-800', 'amber' => 'bg-amber-50 text-amber-800', 'red' => 'bg-rose-50 text-rose-800', 'slate' => 'bg-slate-100 text-slate-700'];
@endphp
<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold', $tones[$tone] ?? $tones['blue']]) }}>{{ $slot }}</span>
