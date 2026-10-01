@extends('layouts.public')

@section('title', 'Apply for '.$job->title.' | Staff of Rajasthan')
@section('description', 'Submit your application for '.$job->title.'.')

@section('content')
    <section class="mx-auto max-w-3xl px-4 py-10 sm:px-6 sm:py-14">
        <x-ui.breadcrumbs :items="[['label' => 'Jobs', 'url' => route('jobs.index')], ['label' => $job->title, 'url' => route('jobs.show', $job)], ['label' => 'Apply']]" />
        <div class="mt-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-9">
            <p class="text-sm font-bold uppercase tracking-wider text-brand-700">Job application</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">{{ $job->title }}</h1>
            <p class="mt-2 text-slate-600">{{ $job->company?->name }}@if ($job->company?->location) · {{ $job->company->location->name }}@endif</p>

            @if (session('status'))<div role="status" class="mt-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>@endif

            @if ($application)
                <div class="mt-7 rounded-2xl bg-slate-50 p-5">
                    <x-ui.badge tone="green">Application recorded</x-ui.badge>
                    <p class="mt-3 text-sm leading-6 text-slate-600">Your application was recorded on {{ $application->created_at->toFormattedDateString() }}. Employer review tools and notifications are not available yet.</p>
                    <x-ui.button :href="route('jobs.show', $job)" variant="secondary" class="mt-4">Back to job details</x-ui.button>
                </div>
            @else
                <p class="mt-5 text-sm leading-6 text-slate-600">Your application will be stored in your candidate account. Add an optional note to introduce yourself. Employer review tools and notifications are planned for a later milestone.</p>
                <form method="POST" action="{{ route('jobs.apply.store', $job) }}" class="mt-7 space-y-5">
                    @csrf
                    <label for="cover_letter" class="block text-sm font-semibold text-slate-800">Cover letter <span class="font-normal text-slate-500">(optional)</span>
                        <textarea id="cover_letter" name="cover_letter" rows="8" maxlength="5000" class="mt-2 w-full rounded-xl border-slate-300 text-sm leading-6 focus:border-brand-500 focus:ring-brand-500" placeholder="Share why this role interests you and what you would bring.">{{ old('cover_letter') }}</textarea>
                    </label>
                    @error('cover_letter')<p class="text-sm text-rose-700">{{ $message }}</p>@enderror
                    <div class="flex flex-wrap gap-3">
                        <x-ui.button type="submit">Submit application <span aria-hidden="true">→</span></x-ui.button>
                        <x-ui.button :href="route('jobs.show', $job)" variant="secondary">Cancel</x-ui.button>
                    </div>
                </form>
            @endif
        </div>
    </section>
@endsection
