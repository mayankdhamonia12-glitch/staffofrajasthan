@extends('layouts.public')

@section('title', 'Candidates | Staff of Rajasthan')
@section('description', 'Meet the candidate community building careers across Rajasthan.')

@section('content')
    <section class="bg-[#f3f7ff]">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
            <x-ui.breadcrumbs :items="[['label' => 'Candidates']]" />
            <x-ui.section-heading class="mt-6" level="1" eyebrow="Rajasthan’s workforce" title="Candidate directory" description="A place to discover professionals and the experience they bring." />
        </div>
    </section>
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
        <x-ui.empty-state title="Candidate discovery is not available yet" description="Public candidate profiles and search are planned for a later platform milestone. You can create an account and prepare your profile in the meantime." :action="route('register.candidate')" action-text="Create candidate account" icon="◎" />
    </section>
@endsection
