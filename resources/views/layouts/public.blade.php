<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Explore verified career opportunities and companies across Rajasthan with Staff of Rajasthan.">
    <meta name="theme-color" content="#1967d2">
    <title>@yield('title', 'Jobs in Rajasthan | Staff of Rajasthan')</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-slate-50 font-sans text-slate-900 antialiased">
    <x-public-header />
    <main>@yield('content')</main>
    <x-public-footer />
    @livewireScripts
</body>
</html>
