<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="@yield('description', 'Explore career opportunities and employers across Rajasthan with Staff of Rajasthan.')">
    <meta name="theme-color" content="#1967d2">
    <title>@yield('title', 'Jobs in Rajasthan | Staff of Rajasthan')</title>
    <meta property="og:site_name" content="Staff of Rajasthan">
    <meta property="og:title" content="@yield('title', 'Jobs in Rajasthan | Staff of Rajasthan')">
    <meta property="og:description" content="@yield('description', 'Explore career opportunities and employers across Rajasthan with Staff of Rajasthan.')">
    <meta property="og:url" content="@yield('canonical', url()->current())">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <link rel="icon" href="/favicon.ico" sizes="any">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @yield('structured_data')
</head>
<body class="bg-slate-50 font-sans text-slate-900 antialiased">
    <a href="#main-content" class="sr-only z-50 rounded-lg bg-white px-4 py-3 font-semibold text-brand-800 shadow focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Skip to content</a>
    <x-public-header />
    <main id="main-content">@yield('content')</main>
    <x-public-footer />
    @livewireScripts
</body>
</html>
