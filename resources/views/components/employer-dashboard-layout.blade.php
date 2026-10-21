@props(['title', 'active' => 'dashboard', 'subtitle' => null])

@php
    $items = [
        ['id' => 'dashboard', 'label' => 'Overview', 'route' => 'employer.dashboard', 'icon' => '⌂'],
        ['id' => 'applications', 'label' => 'Applicants', 'route' => 'employer.applications.index', 'icon' => '✉'],
        ['id' => 'messages', 'label' => 'Messages', 'route' => 'employer.messages.index', 'icon' => '✉'],
        ['id' => 'meetings', 'label' => 'Meetings', 'route' => 'employer.meetings.index', 'icon' => '▦'],
        ['id' => 'notifications', 'label' => 'Notifications', 'route' => 'employer.notifications.index', 'icon' => '♧'],
        ['id' => 'jobs', 'label' => 'My Jobs', 'route' => 'employer.jobs.index', 'icon' => '▤'],
        ['id' => 'packages', 'label' => 'Packages & credits', 'route' => 'employer.packages.index', 'icon' => '₹'],
        ['id' => 'purchases', 'label' => 'Billing history', 'route' => 'employer.purchases.index', 'icon' => '▧'],
        ['id' => 'post', 'label' => 'Post a Job', 'route' => 'employer.jobs.create', 'icon' => '+'],
        ['id' => 'companies', 'label' => 'Company Profile', 'route' => 'employer.companies.index', 'icon' => '▣'],
        ['id' => 'settings', 'label' => 'Settings', 'route' => 'profile.edit', 'icon' => '⚙'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $subtitle ?: 'Manage your company and recruitment listings on Staff of Rajasthan.' }}">
    <title>{{ $title }} | Employer Workspace | Staff of Rajasthan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-slate-50 font-sans text-slate-900 antialiased">
    <a href="#employer-main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-lg focus:bg-white focus:px-4 focus:py-3 focus:shadow">Skip to employer dashboard</a>
    <div class="min-h-screen lg:grid lg:grid-cols-[260px_minmax(0,1fr)]">
        <aside class="hidden border-r border-slate-200 bg-white lg:fixed lg:inset-y-0 lg:flex lg:w-[260px] lg:flex-col">
            <a href="{{ route('home') }}" class="flex h-[76px] items-center gap-3 border-b border-slate-100 px-6"><span class="grid h-10 w-10 place-items-center rounded-xl bg-brand-700 text-sm font-black text-white">SR</span><span class="font-extrabold tracking-tight">Staff of <span class="text-brand-700">Rajasthan</span></span></a>
            <div class="border-b border-slate-100 p-5">
                <div class="flex items-center gap-3"><div class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-xl bg-brand-50 font-bold text-brand-700">@if($dashboardCompany?->logo_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($dashboardCompany->logo_path) }}" alt="" class="h-full w-full object-cover">@else{{ mb_substr($dashboardCompany?->name ?? 'SR', 0, 1) }}@endif</div><div class="min-w-0"><p class="truncate text-sm font-bold">{{ $dashboardCompany?->name ?? 'Employer account' }}</p><p class="truncate text-xs text-slate-500">{{ $dashboardCompany?->industry?->name ?? 'Company workspace' }}</p></div></div>
                @if($dashboardCompany)<a href="{{ route('employer.companies.edit', $dashboardCompany) }}" class="mt-3 inline-flex text-xs font-semibold text-brand-700 hover:underline">Edit company profile →</a>@else<a href="{{ route('employer.companies.create') }}" class="mt-3 inline-flex text-xs font-semibold text-brand-700 hover:underline">Create company profile →</a>@endif
            </div>
            <nav aria-label="Employer dashboard" class="flex-1 space-y-1 overflow-y-auto p-3">
                @foreach($items as $item)
                    <a href="{{ route($item['route']) }}" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition', 'bg-brand-50 text-brand-800' => $active === $item['id'], 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' => $active !== $item['id']]) @if($active === $item['id']) aria-current="page" @endif><span class="grid h-6 w-6 place-items-center text-base" aria-hidden="true">{{ $item['icon'] }}</span><span class="flex-1">{{ $item['label'] }}</span>@if($item['id'] === 'notifications' && $unreadNotificationCount > 0)<span class="min-w-5 rounded-full bg-rose-100 px-1.5 py-0.5 text-center text-[11px] font-bold text-rose-700">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>@elseif($item['id'] === 'messages' && $unreadMessageCount > 0)<span class="min-w-5 rounded-full bg-brand-100 px-1.5 py-0.5 text-center text-[11px] font-bold text-brand-800">{{ $unreadMessageCount > 99 ? '99+' : $unreadMessageCount }}</span>@endif</a>
                @endforeach
            </nav>
            <div class="border-t border-slate-100 p-4"><a href="{{ route('employer.jobs.create') }}" class="flex items-center justify-center rounded-xl bg-brand-700 px-4 py-3 text-sm font-bold text-white transition hover:bg-brand-800">Post a job <span class="ml-2" aria-hidden="true">→</span></a></div>
        </aside>

        <div class="min-w-0 lg:col-start-2">
            <header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 backdrop-blur">
                <div class="flex min-h-[76px] items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                    <div class="flex min-w-0 items-center gap-3"><a href="{{ route('home') }}" class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-700 text-xs font-black text-white lg:hidden">SR</a><div class="min-w-0"><h1 class="truncate text-lg font-extrabold text-slate-950 sm:text-xl">{{ $title }}</h1>@if($subtitle)<p class="hidden truncate text-sm text-slate-500 sm:block">{{ $subtitle }}</p>@endif</div></div>
                    <div class="flex shrink-0 items-center gap-2"><a href="{{ route('companies.index') }}" class="hidden rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 sm:inline-flex">Public directory</a><span class="hidden max-w-40 truncate text-sm font-semibold text-slate-700 md:block">{{ $employer?->name }}</span><a href="{{ route('profile.edit') }}" class="grid h-10 w-10 place-items-center rounded-full bg-slate-100 text-sm font-bold text-slate-600" aria-label="Account settings">{{ $employer?->initials() }}</a><form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Sign out</button></form></div>
                </div>
                <div class="border-t border-slate-100 px-4 py-2 lg:hidden"><details class="group"><summary class="flex cursor-pointer list-none items-center justify-between py-1 text-sm font-semibold text-slate-700"><span>Employer menu</span><span class="transition group-open:rotate-180" aria-hidden="true">⌄</span></summary><nav aria-label="Mobile employer dashboard" class="grid gap-1 pb-2 pt-3 sm:grid-cols-2">@foreach($items as $item)<a href="{{ route($item['route']) }}" @class(['flex items-center justify-between rounded-lg px-3 py-2 text-sm font-semibold', 'bg-brand-50 text-brand-800' => $active === $item['id'], 'text-slate-600 hover:bg-slate-50' => $active !== $item['id']]) @if($active === $item['id']) aria-current="page" @endif><span>{{ $item['label'] }}</span>@if($item['id'] === 'notifications' && $unreadNotificationCount > 0)<span class="rounded-full bg-rose-100 px-1.5 py-0.5 text-[11px] font-bold text-rose-700">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>@elseif($item['id'] === 'messages' && $unreadMessageCount > 0)<span class="rounded-full bg-brand-100 px-1.5 py-0.5 text-[11px] font-bold text-brand-800">{{ $unreadMessageCount > 99 ? '99+' : $unreadMessageCount }}</span>@endif</a>@endforeach</nav></details></div>
            </header>
            <main id="employer-main" class="mx-auto w-full max-w-[1440px] space-y-6 px-4 py-6 sm:px-6 sm:py-8 lg:px-8">{{ $slot }}</main>
        </div>
    </div>
    @livewireScripts
</body>
</html>
