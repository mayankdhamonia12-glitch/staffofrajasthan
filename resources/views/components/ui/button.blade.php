@props(['href' => null, 'variant' => 'primary', 'size' => 'md', 'type' => 'button'])
@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-xl font-semibold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50';
    $variants = [
        'primary' => 'bg-brand-700 text-white shadow-sm hover:bg-brand-800',
        'secondary' => 'border border-slate-200 bg-white text-slate-800 hover:border-brand-300 hover:bg-brand-50',
        'soft' => 'bg-brand-50 text-brand-800 hover:bg-brand-100',
        'ghost' => 'text-slate-700 hover:bg-slate-100',
    ];
    $sizes = ['sm' => 'px-3 py-2 text-sm', 'md' => 'px-5 py-3 text-sm', 'lg' => 'px-6 py-3.5 text-base'];
    $classes = $base.' '.($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']);
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class([$classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class([$classes]) }}>{{ $slot }}</button>
@endif
