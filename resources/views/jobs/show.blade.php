@extends('layouts.public')

@section('title', $job->title.' | Staff of Rajasthan')
@section('description', \Illuminate\Support\Str::limit(strip_tags($job->description), 155))

@section('content')
    <section class="bg-[#f3f7ff]">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
            <x-ui.breadcrumbs :items="[['label' => 'Jobs', 'url' => route('jobs.index')], ['label' => $job->title]]" />
            <div class="mt-7 flex flex-col gap-6 sm:flex-row sm:items-center">
                <div class="grid h-20 w-20 shrink-0 place-items-center overflow-hidden rounded-2xl border border-slate-200 bg-white text-2xl font-bold text-brand-700 shadow-sm">
                    @if ($job->company?->logo_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($job->company->logo_path) }}" alt="{{ $job->company->name }} logo" class="h-full w-full object-cover">
                    @else
                        <span aria-hidden="true">{{ mb_substr($job->company?->name ?? 'SR', 0, 1) }}</span>
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap gap-2">
                        @if ($job->is_featured)<x-ui.badge tone="amber">Featured</x-ui.badge>@endif
                        @if ($job->is_urgent)<x-ui.badge tone="red">Urgent</x-ui.badge>@endif
                        @if ($job->category)<x-ui.badge>{{ $job->category->name }}</x-ui.badge>@endif
                    </div>
                    <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl">{{ $job->title }}</h1>
                    <div class="mt-2 flex flex-wrap gap-x-5 gap-y-2 text-sm text-slate-600">
                        @if ($job->company)<a class="font-semibold text-brand-700 hover:underline" href="{{ route('companies.show', $job->company) }}">{{ $job->company->name }}</a>@endif
                        @if ($job->location ?? $job->company?->location)<span>{{ ($job->location ?? $job->company?->location)->name }}{{ ($job->location ?? $job->company?->location)->state ? ', '.($job->location ?? $job->company?->location)->state : '' }}</span>@endif
                        <span>{{ \Illuminate\Support\Str::headline($job->employment_type) }}</span>
                        @if ($job->experience_level)<span>{{ $job->experience_level }}</span>@endif
                    </div>
                </div>
                <x-ui.button :href="route('jobs.index')" variant="secondary">Browse all jobs <span aria-hidden="true">→</span></x-ui.button>
            </div>
        </div>
    </section>

    <section class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 sm:py-16 lg:grid-cols-[1fr_20rem] lg:px-8">
        <article class="space-y-9">
            <div>
                <h2 class="text-xl font-bold text-slate-950">Job description</h2>
                <div class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-600">{{ $job->description }}</div>
            </div>
            @if ($job->responsibilities)
                <div>
                    <h2 class="text-xl font-bold text-slate-950">Responsibilities</h2>
                    <div class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-600">{{ $job->responsibilities }}</div>
                </div>
            @endif
            @if ($job->requirements)
                <div>
                    <h2 class="text-xl font-bold text-slate-950">Requirements</h2>
                    <div class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-600">{{ $job->requirements }}</div>
                </div>
            @endif
            @if ($job->skills->isNotEmpty())
                <div>
                    <h2 class="text-xl font-bold text-slate-950">Skills</h2>
                    <div class="mt-3 flex flex-wrap gap-2">@foreach ($job->skills as $skill)<x-ui.chip>{{ $skill->name }}</x-ui.chip>@endforeach</div>
                </div>
            @endif
        </article>

        <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-bold text-slate-950">Job overview</h2>
            <dl class="mt-5 space-y-4 text-sm">
                <div><dt class="text-slate-500">Employment type</dt><dd class="mt-1 font-semibold text-slate-900">{{ \Illuminate\Support\Str::headline($job->employment_type) }}</dd></div>
                @if ($job->industry)<div><dt class="text-slate-500">Industry</dt><dd class="mt-1 font-semibold text-slate-900">{{ $job->industry->name }}</dd></div>@endif
                @if ($job->published_at)<div><dt class="text-slate-500">Published</dt><dd class="mt-1 font-semibold text-slate-900">{{ $job->published_at->toFormattedDateString() }}</dd></div>@endif
                @if ($job->application_deadline)<div><dt class="text-slate-500">Application deadline</dt><dd class="mt-1 font-semibold text-slate-900">{{ $job->application_deadline->toFormattedDateString() }}</dd></div>@endif
            </dl>
            <p class="mt-6 border-t border-slate-100 pt-5 text-sm leading-6 text-slate-600">Online applications are not available yet. Application tools will be introduced in a later platform milestone.</p>
        </aside>
    </section>
@endsection
