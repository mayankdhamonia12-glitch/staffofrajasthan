@extends('layouts.public')

@section('title', 'Career Resources | Staff of Rajasthan')
@section('description', 'Career and hiring resources from Staff of Rajasthan.')

@section('content')
    <section class="bg-[#f3f7ff]">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
            <x-ui.breadcrumbs :items="[['label' => 'Blog']]" />
            <x-ui.section-heading class="mt-6" level="1" eyebrow="Career resources" title="Ideas for your next move" description="Insights for candidates, employers, and teams across Rajasthan." />
        </div>
    </section>
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8">
        <x-ui.empty-state title="No articles have been published yet" description="The blog is ready for career guidance, hiring insights, and stories from Rajasthan’s workforce. Check back when the first articles are published." :action="route('jobs.index')" action-text="Explore current jobs" icon="✎" />
    </section>
@endsection
