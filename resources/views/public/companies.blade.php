@extends('layouts.public')

@section('title', 'Companies Hiring in Rajasthan | Staff of Rajasthan')
@section('description', 'Explore employers with current job openings across Rajasthan.')

@section('content')
    <section class="bg-[#f3f7ff]">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
            <x-ui.breadcrumbs :items="[['label' => 'Companies']]" />
            <x-ui.section-heading class="mt-6" level="1" eyebrow="Find your next team" title="Companies hiring in Rajasthan" description="Explore visible company profiles with current published opportunities." />
        </div>
    </section>
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
        @if ($companies->isEmpty())
            <x-ui.empty-state title="No companies with open roles yet" description="Company profiles will appear here when employers have current published opportunities." :action="route('register.employer')" action-text="Join as an employer" icon="⌂" />
        @else
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">@foreach ($companies as $company)<x-company-card :company="$company" />@endforeach</div>
            <x-ui.pagination :paginator="$companies" />
        @endif
    </section>
@endsection
