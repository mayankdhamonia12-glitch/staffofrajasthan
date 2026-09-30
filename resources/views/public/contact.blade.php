@extends('layouts.public')

@section('title', 'Contact Staff of Rajasthan')
@section('description', 'Contact information for Staff of Rajasthan.')

@section('content')
    <section class="bg-[#f3f7ff]">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
            <x-ui.breadcrumbs :items="[['label' => 'Contact']]" />
            <x-ui.section-heading class="mt-6" level="1" eyebrow="We’re here to help" title="Contact Staff of Rajasthan" description="We’re preparing the support channels for the public launch." />
        </div>
    </section>
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
        <div class="grid gap-6 lg:grid-cols-[1fr_.8fr]">
            <x-ui.card padding="p-7 sm:p-9">
                <x-ui.badge tone="amber">Support details coming soon</x-ui.badge>
                <h2 class="mt-4 text-2xl font-bold text-slate-950">No contact form is active yet</h2>
                <p class="mt-3 max-w-xl leading-7 text-slate-600">We don’t want to collect a message we can’t yet route to a support team. Official contact details will be published here when support is ready.</p>
            </x-ui.card>
            <x-ui.card padding="p-7 sm:p-9" class="bg-brand-800 text-white">
                <h2 class="text-xl font-bold">Looking for your next step?</h2>
                <p class="mt-2 text-sm leading-6 text-blue-100">You can already browse published roles or create an account to get started.</p>
                <div class="mt-5 flex flex-wrap gap-3"><x-ui.button :href="route('jobs.index')" variant="secondary">Browse jobs</x-ui.button><x-ui.button :href="route('register')" variant="soft">Register</x-ui.button></div>
            </x-ui.card>
        </div>
    </section>
@endsection
