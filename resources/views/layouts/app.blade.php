<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <meta name="description" content="Manage your Staff of Rajasthan account and platform workspace.">
        <meta name="theme-color" content="#1d4ed8">
        <title>{{ isset($title) ? $title.' · ' : '' }}{{ config('app.name', 'Staff of Rajasthan') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased">
        <a href="#app-main" class="sr-only z-50 rounded-lg bg-white px-4 py-3 font-semibold text-brand-800 shadow focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Skip to content</a>
        <div class="flex min-h-screen flex-col">
            @include('layouts.navigation')

            @isset($header)
                <div class="border-b border-slate-100 bg-white">
                    <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </div>
            @endisset

            <main id="app-main" class="flex-1">
                {{ $slot }}
            </main>
            <x-public-footer />
        </div>
    </body>
</html>
