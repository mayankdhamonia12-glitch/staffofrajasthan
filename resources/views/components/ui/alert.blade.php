@props(['tone' => 'info'])
@php($tones = ['info' => 'border-brand-200 bg-brand-50 text-brand-900', 'success' => 'border-emerald-200 bg-emerald-50 text-emerald-900', 'warning' => 'border-amber-200 bg-amber-50 text-amber-900', 'error' => 'border-rose-200 bg-rose-50 text-rose-900'])
<div role="status" {{ $attributes->class(['rounded-xl border px-4 py-3 text-sm leading-6', $tones[$tone] ?? $tones['info']]) }}>{{ $slot }}</div>
