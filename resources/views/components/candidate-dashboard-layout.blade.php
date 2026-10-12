@props(['title', 'active' => 'dashboard', 'subtitle' => null])

@php
    $items = [
        ['id' => 'dashboard', 'label' => 'Dashboard', 'route' => 'candidate.dashboard', 'icon' => '⌂'],
        ['id' => 'profile', 'label' => 'My Profile', 'route' => 'candidate.profile.edit', 'icon' => '◉'],
        ['id' => 'applied', 'label' => 'Applied Jobs', 'route' => 'candidate.applied-jobs.index', 'icon' => '▤'],
        ['id' => 'saved', 'label' => 'Saved Jobs', 'route' => 'candidate.saved-jobs.index', 'icon' => '♡'],
        ['id' => 'views', 'label' => 'Profile Views', 'route' => 'candidate.profile-views.index', 'icon' => '◷'],
        ['id' => 'alerts', 'label' => 'Job Alerts', 'route' => 'candidate.job-alerts.index', 'icon' => '⌕'],
        ['id' => 'following', 'label' => 'Following Employers', 'route' => 'candidate.following.index', 'icon' => '♧'],
        ['id' => 'notifications', 'label' => 'Notifications', 'route' => 'candidate.notifications.index', 'icon' => '♧'],
        ['id' => 'meetings', 'label' => 'Meetings', 'route' => 'candidate.meetings.index', 'icon' => '▦'],
        ['id' => 'messages', 'label' => 'Messages', 'route' => 'candidate.messages.index', 'icon' => '✉'],
        ['id' => 'reviews', 'label' => 'Reviews', 'route' => 'candidate.reviews.index', 'icon' => '☆'],
        ['id' => 'settings', 'label' => 'Settings', 'route' => 'candidate.settings.edit', 'icon' => '⚙'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $subtitle ?: 'Manage your candidate profile, applications, and saved opportunities on Staff of Rajasthan.' }}">
    <title>{{ $title }} | Candidate Dashboard | Staff of Rajasthan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 font-sans text-slate-900 antialiased">
    <a href="#candidate-main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-3 focus:shadow">Skip to dashboard content</a>
    <div class="min-h-screen lg:grid lg:grid-cols-[260px_minmax(0,1fr)]">
        <aside class="hidden border-r border-slate-200 bg-white lg:fixed lg:inset-y-0 lg:flex lg:w-[260px] lg:flex-col">
            <a href="{{ route('home') }}" class="flex h-[76px] items-center gap-3 border-b border-slate-100 px-6"><span class="grid h-10 w-10 place-items-center rounded-xl bg-brand-700 text-sm font-black text-white">SR</span><span class="font-extrabold tracking-tight">Staff of <span class="text-brand-700">Rajasthan</span></span></a>
            <div class="border-b border-slate-100 p-5">
                <div class="flex items-center gap-3"><div class="h-12 w-12 shrink-0 overflow-hidden rounded-full bg-slate-100">@if($dashboardCandidateProfile?->profile_photo_path)<img src="{{ route('candidate.profile.photo') }}" alt="" class="h-full w-full object-cover">@else<span class="grid h-full place-items-center font-bold text-slate-500">{{ $dashboardCandidate?->initials() }}</span>@endif</div><div class="min-w-0"><p class="truncate text-sm font-bold">{{ $dashboardCandidate?->name }}</p><p class="truncate text-xs text-slate-500">{{ $dashboardCandidateProfile?->headline ?: 'Candidate account' }}</p></div></div>
                <div class="mt-4 flex items-center justify-between text-xs"><span class="font-semibold text-slate-600">Profile completeness</span><span class="font-bold text-brand-700">{{ $dashboardProfileProgress['percentage'] }}%</span></div><div class="mt-2 h-1.5 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-brand-700" style="width: {{ $dashboardProfileProgress['percentage'] }}%"></div></div>
            </div>
            <nav aria-label="Candidate dashboard" class="flex-1 space-y-1 overflow-y-auto p-3">@foreach($items as $item)<a href="{{ route($item['route']) }}" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition', 'bg-blue-50 text-brand-800' => $active === $item['id'], 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' => $active !== $item['id']]) @if($active === $item['id']) aria-current="page" @endif><span class="grid h-6 w-6 place-items-center text-base" aria-hidden="true">{{ $item['icon'] }}</span>{{ $item['label'] }}</a>@endforeach</nav>
            <div class="border-t border-slate-100 p-4"><a href="{{ route('jobs.index') }}" class="flex items-center justify-center rounded-xl bg-brand-700 px-4 py-3 text-sm font-bold text-white transition hover:bg-brand-800">Find a job <span class="ml-2" aria-hidden="true">→</span></a></div>
        </aside>

        <div class="min-w-0 lg:col-start-2">
            <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
                <div class="flex min-h-[76px] items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3"><a href="{{ route('home') }}" class="grid h-10 w-10 place-items-center rounded-xl bg-brand-700 text-xs font-black text-white lg:hidden">SR</a><div><h1 class="text-lg font-extrabold text-slate-950 sm:text-xl">{{ $title }}</h1>@if($subtitle)<p class="hidden text-sm text-slate-500 sm:block">{{ $subtitle }}</p>@endif</div></div>
                    <div class="flex items-center gap-2"><a href="{{ route('jobs.index') }}" class="hidden rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 sm:inline-flex">Browse jobs</a><div class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full bg-slate-100 text-sm font-bold text-slate-600">@if($dashboardCandidateProfile?->profile_photo_path)<img src="{{ route('candidate.profile.photo') }}" alt="" class="h-full w-full object-cover">@else{{ $dashboardCandidate?->initials() }}@endif</div><span class="hidden max-w-36 truncate text-sm font-semibold sm:block">{{ $dashboardCandidate?->name }}</span></div>
                </div>
                <div class="border-t border-slate-100 px-4 py-2 lg:hidden"><details class="group"><summary class="flex cursor-pointer list-none items-center justify-between py-1 text-sm font-semibold text-slate-700"><span>Dashboard menu</span><span class="transition group-open:rotate-180" aria-hidden="true">⌄</span></summary><nav aria-label="Mobile candidate dashboard" class="grid gap-1 pb-2 pt-3 sm:grid-cols-2">@foreach($items as $item)<a href="{{ route($item['route']) }}" @class(['rounded-lg px-3 py-2 text-sm font-semibold', 'bg-blue-50 text-brand-800' => $active === $item['id'], 'text-slate-600 hover:bg-slate-50' => $active !== $item['id']])>{{ $item['label'] }}</a>@endforeach</nav></details></div>
            </header>
            <main id="candidate-main" class="mx-auto w-full max-w-[1440px] space-y-6 px-4 py-6 sm:px-6 sm:py-8 lg:px-8">{{ $slot }}</main>
        </div>
    </div>
</body>
</html>
