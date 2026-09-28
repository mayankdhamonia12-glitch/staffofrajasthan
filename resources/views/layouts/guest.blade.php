<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Staff of Rajasthan') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-slate-900 antialiased">
        <div class="min-h-screen bg-slate-50 px-4 py-10 sm:flex sm:flex-col sm:items-center sm:justify-center">
            <a href="{{ route('home') }}" class="mb-6 text-center text-xl font-bold tracking-tight text-slate-900">Staff of <span class="text-orange-700">Rajasthan</span></a>
            <div class="w-full sm:max-w-md rounded-2xl bg-white px-6 py-7 shadow-xl shadow-slate-200/60 sm:px-8">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
