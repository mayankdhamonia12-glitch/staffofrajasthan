@props(['status'])
@php
    $tone = match ($status) {
        'submitted' => 'bg-blue-50 text-blue-800 ring-blue-600/15',
        'reviewing' => 'bg-amber-50 text-amber-800 ring-amber-600/15',
        'interview' => 'bg-violet-50 text-violet-800 ring-violet-600/15',
        'accepted' => 'bg-emerald-50 text-emerald-800 ring-emerald-600/15',
        'rejected' => 'bg-rose-50 text-rose-800 ring-rose-600/15',
        default => 'bg-slate-100 text-slate-700 ring-slate-500/15',
    };
    $label = match ($status) {
        'submitted' => 'Submitted',
        'reviewing' => 'Under review',
        'interview' => 'Interview',
        'accepted' => 'Accepted',
        'rejected' => 'Not selected',
        default => \Illuminate\Support\Str::headline($status),
    };
@endphp
<span {{ $attributes->class(['inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-xs font-bold ring-1 ring-inset', $tone]) }}>{{ $label }}</span>
