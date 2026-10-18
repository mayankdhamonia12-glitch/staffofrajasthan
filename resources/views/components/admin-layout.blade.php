@props(['title' => 'Admin control panel', 'active' => 'dashboard'])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · {{ config('app.name', 'Staff of Rajasthan') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
    <div class="min-h-screen lg:flex">
        <aside class="hidden w-64 shrink-0 border-r border-slate-200 bg-white lg:flex lg:flex-col">
            <a href="{{ route('admin.dashboard') }}" class="flex h-16 items-center gap-3 border-b border-slate-100 px-6 font-extrabold text-slate-950"><span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-700 text-white">S</span><span>Staff of Rajasthan <small class="block text-[10px] font-semibold uppercase tracking-widest text-slate-400">Control panel</small></span></a>
            @php($links = ['dashboard' => ['Dashboard', 'admin.dashboard'], 'users' => ['Users', 'admin.users.index'], 'jobs' => ['Jobs', 'admin.jobs.index'], 'companies' => ['Companies', 'admin.companies.index'], 'applications' => ['Applications', 'admin.applications.index'], 'reviews' => ['Company reviews', 'admin.company-reviews.index'], 'blog' => ['Blog articles', 'admin.blog.articles.index'], 'reference-data' => ['Reference data', 'admin.reference-data.index'], 'packages' => ['Packages', 'admin.packages.index'], 'package-purchases' => ['Package purchases', 'admin.package-purchases.index']])
            <nav class="flex-1 space-y-1 p-4 text-sm font-semibold">
                @foreach($links as $key => [$label, $routeName])<a href="{{ route($routeName) }}" class="block rounded-xl px-3 py-2.5 {{ $active === $key ? 'bg-brand-50 text-brand-800' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950' }}">{{ $label }}</a>@endforeach
                @if(auth()->user()?->isOwner())<div class="my-3 border-t border-slate-100"></div><a href="{{ route('admin.settings.edit') }}" class="block rounded-xl px-3 py-2.5 {{ $active === 'settings' ? 'bg-brand-50 text-brand-800' : 'text-slate-600 hover:bg-slate-50' }}">Platform settings</a><a href="{{ route('admin.audit.index') }}" class="block rounded-xl px-3 py-2.5 {{ $active === 'audit' ? 'bg-brand-50 text-brand-800' : 'text-slate-600 hover:bg-slate-50' }}">Audit log</a>@endif
            </nav>
            <div class="border-t border-slate-100 p-4 text-xs text-slate-500">Signed in as <strong class="text-slate-800">{{ auth()->user()->name }}</strong><br>{{ ucfirst(auth()->user()->role->value) }}</div>
        </aside>
        <div class="min-w-0 flex-1">
            <header class="sticky top-0 z-10 border-b border-slate-200 bg-white/95 backdrop-blur"><div class="flex min-h-16 items-center justify-between gap-4 px-4 sm:px-6 lg:px-8"><div class="flex items-center gap-3"><details class="relative lg:hidden"><summary class="list-none cursor-pointer rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold">Menu</summary><nav class="absolute left-0 top-12 z-20 w-64 rounded-xl border border-slate-200 bg-white p-2 shadow-xl">@foreach($links as $key => [$label, $routeName])<a class="block rounded-lg px-3 py-2 text-sm {{ $active === $key ? 'bg-brand-50 text-brand-800' : 'text-slate-700' }}" href="{{ route($routeName) }}">{{ $label }}</a>@endforeach @if(auth()->user()?->isOwner())<a class="block rounded-lg px-3 py-2 text-sm" href="{{ route('admin.settings.edit') }}">Platform settings</a><a class="block rounded-lg px-3 py-2 text-sm" href="{{ route('admin.audit.index') }}">Audit log</a>@endif</nav></details><h1 class="text-lg font-bold sm:text-xl">{{ $title }}</h1></div><div class="flex items-center gap-3"><a href="{{ route('home') }}" class="hidden text-sm font-semibold text-slate-600 hover:text-brand-700 sm:inline">View website</a><form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:border-slate-300">Log out</button></form></div></div></header>
            <main class="mx-auto max-w-7xl space-y-6 p-4 sm:p-6 lg:p-8">
                @if(session('status'))<div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>@endif
                @if($errors->any())<div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800"><p class="font-semibold">Please review the submitted information.</p><ul class="mt-2 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
