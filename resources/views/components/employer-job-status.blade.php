@props(['job'])
@php
    [$label, $tone] = match (true) {
        $job->is_filled => ['Filled', 'slate'],
        $job->status === 'closed' => ['Closed', 'red'],
        $job->status === 'draft' => ['Draft', 'amber'],
        $job->application_deadline?->lt(today()) => ['Expired', 'red'],
        ! $job->company?->is_visible => ['Company private', 'slate'],
        $job->status === 'published' => ['Published', 'green'],
        default => [\Illuminate\Support\Str::headline($job->status), 'slate'],
    };
@endphp
<x-ui.badge :tone="$tone">{{ $label }}</x-ui.badge>
