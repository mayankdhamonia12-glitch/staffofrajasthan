@props(['status'])
@php
    $statusValue = $status instanceof \App\JobApplicationStatus ? $status->value : (string) $status;
    $tone = \App\JobApplicationStatus::toneFor($statusValue);
    $label = \App\JobApplicationStatus::labelFor($statusValue);
@endphp
<span {{ $attributes->class(['inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-xs font-bold ring-1 ring-inset', $tone]) }}>{{ $label }}</span>
