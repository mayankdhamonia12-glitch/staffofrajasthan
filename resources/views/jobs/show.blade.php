@extends('layouts.public')

@section('title', $job->title.' | Staff of Rajasthan')
@section('description', \Illuminate\Support\Str::limit(strip_tags($job->description), 155))
@section('canonical', route('jobs.show', $job))
@section('og_type', 'article')
@section('structured_data')
    @if ($structuredData)
        <script type="application/ld+json">@json($structuredData)</script>
    @endif
@endsection

@section('content')
    @php($jobLocation = $job->location ?? $job->company?->location)
    <section class="border-b border-slate-200 bg-[#f3f7ff]">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-11 lg:px-8">
            <x-ui.breadcrumbs :items="[['label' => 'Jobs', 'url' => route('jobs.index')], ['label' => $job->title]]" />
            <div class="mt-7 flex flex-col gap-5 rounded-3xl border border-white bg-white p-5 shadow-sm sm:flex-row sm:items-center sm:p-7">
                <div class="grid h-20 w-20 shrink-0 place-items-center overflow-hidden rounded-2xl border border-slate-100 bg-brand-50 text-2xl font-bold text-brand-700">
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
                        @if ($job->is_filled)<x-ui.badge tone="slate">Filled</x-ui.badge>@endif
                    </div>
                    <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl">{{ $job->title }}</h1>
                    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-slate-600">
                        @if ($job->company)<span class="font-semibold text-slate-800">{{ $job->company->name }}</span>@endif
                        @if ($jobLocation)<span>{{ $jobLocation->name }}{{ $jobLocation->state ? ', '.$jobLocation->state : '' }}</span>@endif
                        @if ($job->category)<x-ui.badge>{{ $job->category->name }}</x-ui.badge>@endif
                        <span>{{ \Illuminate\Support\Str::headline($job->employment_type) }}</span>
                        @if ($job->experience_level)<span>{{ $job->experience_level }}</span>@endif
                        @if ($job->published_at)<span>Published {{ $job->published_at->diffForHumans() }}</span>@endif
                    </div>
                    <div class="mt-4 flex flex-wrap items-center gap-3">
                        @if ($isCandidate)
                            @if ($application)<x-ui.badge tone="green">Application recorded</x-ui.badge>@else<x-ui.button :href="route('jobs.apply', $job)" size="sm">Apply Now <span aria-hidden="true">→</span></x-ui.button>@endif
                        @elseif (! auth()->check())
                            <x-ui.button :href="route('jobs.apply', $job)" size="sm">Apply Now <span aria-hidden="true">→</span></x-ui.button>
                            <a href="{{ route('register.candidate') }}" class="text-sm font-semibold text-brand-700 hover:underline">Register as a candidate</a>
                        @else
                            <x-ui.badge tone="slate">Applications are for candidates</x-ui.badge>
                        @endif
                        @if ($isCandidate)
                            @if ($savedJob)
                                <form method="POST" action="{{ route('candidate.saved-jobs.destroy', $savedJob) }}">
                                    @csrf @method('DELETE')
                                    <x-ui.button type="submit" variant="secondary" size="sm"><span aria-hidden="true">♥</span> Saved · Remove</x-ui.button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('candidate.jobs.save', $job) }}">
                                    @csrf
                                    <x-ui.button type="submit" variant="secondary" size="sm"><span aria-hidden="true">♡</span> Save job</x-ui.button>
                                </form>
                            @endif
                        @elseif (! auth()->check())
                            <x-ui.button :href="route('login')" variant="secondary" size="sm">Sign in to save</x-ui.button>
                        @endif
                        <x-job-share :job="$job" />
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto grid max-w-7xl items-start gap-8 px-4 py-9 sm:px-6 sm:py-12 lg:grid-cols-[minmax(0,1fr)_350px] lg:px-8">
        <article class="min-w-0 space-y-6">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <h2 class="text-xl font-bold text-slate-950">Job description</h2>
                <div class="mt-4 whitespace-pre-line text-sm leading-7 text-slate-600">{{ $job->description }}</div>
            </section>

            @if ($job->responsibilities)
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <h2 class="text-xl font-bold text-slate-950">Responsibilities</h2>
                    <div class="mt-4 whitespace-pre-line text-sm leading-7 text-slate-600">{{ $job->responsibilities }}</div>
                </section>
            @endif

            @if ($job->requirements)
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <h2 class="text-xl font-bold text-slate-950">Requirements &amp; qualifications</h2>
                    <div class="mt-4 whitespace-pre-line text-sm leading-7 text-slate-600">{{ $job->requirements }}</div>
                </section>
            @endif

            @if ($job->skills->isNotEmpty())
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                    <h2 class="text-xl font-bold text-slate-950">Skills</h2>
                    <div class="mt-4 flex flex-wrap gap-2">@foreach ($job->skills as $skill)<x-ui.chip>{{ $skill->name }}</x-ui.chip>@endforeach</div>
                </section>
            @endif
        </article>

        <aside class="space-y-6 lg:sticky lg:top-24">
            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-950">Job overview</h2>
                <dl class="mt-5 divide-y divide-slate-100 text-sm">
                    @if ($salary)<div class="py-3"><dt class="text-slate-500">Salary</dt><dd class="mt-1 font-semibold text-slate-900">{{ $salary }}</dd></div>@endif
                    <div class="py-3"><dt class="text-slate-500">Job type</dt><dd class="mt-1 font-semibold text-slate-900">{{ \Illuminate\Support\Str::headline($job->employment_type) }}</dd></div>
                    @if ($job->experience_level)<div class="py-3"><dt class="text-slate-500">Experience</dt><dd class="mt-1 font-semibold text-slate-900">{{ $job->experience_level }}</dd></div>@endif
                    @if ($job->category)<div class="py-3"><dt class="text-slate-500">Category</dt><dd class="mt-1 font-semibold text-slate-900">{{ $job->category->name }}</dd></div>@endif
                    @if ($job->industry)<div class="py-3"><dt class="text-slate-500">Industry</dt><dd class="mt-1 font-semibold text-slate-900">{{ $job->industry->name }}</dd></div>@endif
                    @if ($jobLocation)<div class="py-3"><dt class="text-slate-500">Location</dt><dd class="mt-1 font-semibold text-slate-900">{{ $jobLocation->name }}{{ $jobLocation->state ? ', '.$jobLocation->state : '' }}</dd></div>@endif
                    @if ($job->published_at)<div class="py-3"><dt class="text-slate-500">Published</dt><dd class="mt-1 font-semibold text-slate-900">{{ $job->published_at->toFormattedDateString() }}</dd></div>@endif
                    @if ($job->application_deadline)<div class="py-3"><dt class="text-slate-500">Deadline</dt><dd class="mt-1 font-semibold text-slate-900">{{ $job->application_deadline->toFormattedDateString() }}</dd></div>@endif
                </dl>
                @if ($application)
                    <x-ui.badge tone="green" class="mt-4">Application recorded</x-ui.badge>
                @elseif ($isCandidate || ! auth()->check())
                    <x-ui.button :href="route('jobs.apply', $job)" class="mt-4 w-full">Apply Now</x-ui.button>
                    @if (! auth()->check())<a href="{{ route('register.candidate') }}" class="mt-3 block text-center text-sm font-semibold text-brand-700 hover:underline">Create candidate account</a>@endif
                @else
                    <p class="mt-4 text-sm text-slate-600">Applications are available to candidate accounts only.</p>
                @endif
            </section>

            @if ($job->company)
                <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-xl bg-brand-50 font-bold text-brand-700">
                            @if ($job->company->logo_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($job->company->logo_path) }}" alt="{{ $job->company->name }} logo" class="h-full w-full object-cover">@else<span aria-hidden="true">{{ mb_substr($job->company->name, 0, 1) }}</span>@endif
                        </div>
                        <div class="min-w-0"><h2 class="truncate font-bold text-slate-950">{{ $job->company->name }}</h2><p class="truncate text-sm text-slate-500">{{ $job->company->industry?->name ?? 'Employer' }}</p></div>
                    </div>
                    <div class="mt-4 space-y-2 text-sm text-slate-600">
                        @if ($job->company->location)<p>{{ $job->company->location->name }}{{ $job->company->location->state ? ', '.$job->company->location->state : '' }}</p>@endif
                        <p>{{ number_format($companyJobsCount) }} {{ \Illuminate\Support\Str::plural('open job', $companyJobsCount) }}</p>
                    </div>
                    @if ($job->company->is_visible)
                        <x-ui.button :href="route('companies.show', $job->company)" variant="secondary" size="sm" class="mt-5 w-full">View company <span aria-hidden="true">→</span></x-ui.button>
                    @endif
                </section>
            @endif

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-bold text-slate-950">Location</h2>
                @if ($jobLocation)
                    <p class="mt-3 text-sm leading-6 text-slate-600">{{ $jobLocation->name }}{{ $jobLocation->state ? ', '.$jobLocation->state : '' }}, India</p>
                    <p class="mt-2 text-xs text-slate-500">Map preview is unavailable because no coordinates are recorded for this location.</p>
                @else
                    <p class="mt-3 text-sm text-slate-600">The employer has not provided a location.</p>
                @endif
            </section>
        </aside>
    </section>

    @if ($relatedJobs->isNotEmpty())
        <section class="border-t border-slate-200 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <x-ui.section-heading eyebrow="Keep exploring" title="Related opportunities" description="Current roles with a shared category, industry, or location." :link="route('jobs.index')" link-text="Browse all jobs" />
                <div class="mt-7 grid gap-4 lg:grid-cols-3">@foreach ($relatedJobs as $relatedJob)<x-job-card :job="$relatedJob" />@endforeach</div>
            </div>
        </section>
    @endif
@endsection
