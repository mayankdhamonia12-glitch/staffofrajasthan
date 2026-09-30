@extends('layouts.public')

@section('title', 'About Staff of Rajasthan')
@section('description', 'Learn about Staff of Rajasthan, a recruitment platform focused on local careers and employers.')

@section('content')
    <section class="bg-[#f3f7ff]">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
            <x-ui.breadcrumbs :items="[['label' => 'About']]" />
            <div class="mt-8 max-w-3xl"><x-ui.badge>About the platform</x-ui.badge><h1 class="mt-5 text-4xl font-extrabold tracking-tight text-slate-950 sm:text-5xl">A more local way to move work forward.</h1><p class="mt-5 text-lg leading-8 text-slate-600">Staff of Rajasthan is being built to bring job seekers and employers across the state together in one focused place.</p></div>
        </div>
    </section>
    <section class="mx-auto grid max-w-7xl gap-6 px-4 py-12 sm:px-6 sm:py-16 lg:grid-cols-3 lg:px-8">
        <x-ui.card><span class="text-sm font-bold uppercase tracking-wider text-brand-700">For candidates</span><h2 class="mt-3 text-xl font-bold text-slate-950">Make your next move</h2><p class="mt-2 text-sm leading-6 text-slate-600">Explore current opportunities, build a profile, and keep your experience in one place.</p></x-ui.card>
        <x-ui.card><span class="text-sm font-bold uppercase tracking-wider text-brand-700">For employers</span><h2 class="mt-3 text-xl font-bold text-slate-950">Meet local talent</h2><p class="mt-2 text-sm leading-6 text-slate-600">Create an employer account and prepare to reach people building their careers in Rajasthan.</p></x-ui.card>
        <x-ui.card><span class="text-sm font-bold uppercase tracking-wider text-brand-700">Our focus</span><h2 class="mt-3 text-xl font-bold text-slate-950">Built for Rajasthan</h2><p class="mt-2 text-sm leading-6 text-slate-600">A clear, accessible recruitment experience shaped around the state’s workforce and employers.</p></x-ui.card>
    </section>
    <section class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8"><div class="flex flex-wrap gap-3"><x-ui.button :href="route('jobs.index')">Browse jobs</x-ui.button><x-ui.button :href="route('register')" variant="secondary">Create an account</x-ui.button></div></section>
@endsection
