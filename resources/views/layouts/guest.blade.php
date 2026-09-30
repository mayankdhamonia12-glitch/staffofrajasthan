<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <meta name="description" content="Sign in or create a Staff of Rajasthan account to explore careers and hiring opportunities.">
        <meta name="theme-color" content="#1d4ed8">
        <title>{{ match (request()->route()?->getName()) {
            'login' => 'Log in | Staff of Rajasthan',
            'register' => 'Create an account | Staff of Rajasthan',
            'register.candidate' => 'Candidate registration | Staff of Rajasthan',
            'register.employer' => 'Employer registration | Staff of Rajasthan',
            'password.request' => 'Reset your password | Staff of Rajasthan',
            'password.reset' => 'Choose a new password | Staff of Rajasthan',
            'verification.notice' => 'Verify your email | Staff of Rajasthan',
            default => config('app.name', 'Staff of Rajasthan'),
        } }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-slate-50 font-sans text-slate-900 antialiased">
        <a href="#main-content" class="sr-only z-50 rounded-lg bg-white px-4 py-3 font-semibold text-brand-800 shadow focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Skip to content</a>
        <x-public-header />
        <main id="main-content" class="mx-auto flex min-h-[60vh] w-full max-w-7xl flex-col items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
            <div class="w-full max-w-md rounded-3xl border border-slate-200 bg-white px-6 py-8 shadow-xl shadow-slate-900/5 sm:px-9">
                {{ $slot }}
            </div>
        </main>
        <x-public-footer />
    </body>
</html>
