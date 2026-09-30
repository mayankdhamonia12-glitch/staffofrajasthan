@props(['job', 'layout' => 'grid'])
@php
    $company = $job->company;
    $jobLocation = $job->location?->name ?? $company?->location?->name ?? 'Location to be confirmed';
    $salary = $job->salary_min || $job->salary_max
        ? '₹'.number_format($job->salary_min ?? $job->salary_max).($job->salary_max && $job->salary_min ? ' – ₹'.number_format($job->salary_max) : '').($job->salary_period ? ' / '.str_replace('_', ' ', $job->salary_period) : '')
        : null;
@endphp
<article class="relative rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-brand-200 hover:shadow-md {{ $layout === 'list' ? 'sm:flex sm:items-start sm:gap-5 sm:p-6' : '' }}">
    <div class="flex min-w-0 flex-1 items-start gap-4">
        <div class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-xl border border-slate-100 bg-brand-50 text-lg font-bold text-brand-700">
            @if ($company?->logo_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($company->logo_path) }}" alt="{{ $company->name }} logo" class="h-full w-full object-cover">@else{{ mb_substr($company?->name ?? 'SR', 0, 1) }}@endif
        </div>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2">
                @if ($job->is_featured)<x-ui.badge tone="amber">Featured</x-ui.badge>@endif
                @if ($job->is_urgent)<x-ui.badge tone="red">Urgent</x-ui.badge>@endif
                @if ($job->is_filled)<x-ui.badge tone="slate">Filled</x-ui.badge>@endif
            </div>
            <h3 class="mt-2 text-lg font-bold leading-snug text-slate-950">{{ $job->title }}</h3>
            <p class="mt-1 text-sm text-slate-600">{{ $company?->name ?? 'Employer' }}</p>
            <div class="mt-4 flex flex-wrap gap-x-4 gap-y-2 text-sm text-slate-500">
                <span>{{ $jobLocation }}{{ $job->location?->state ? ', '.$job->location->state : '' }}</span>
                <span>{{ \Illuminate\Support\Str::headline($job->employment_type) }}</span>
                @if ($job->experience_level)<span>{{ $job->experience_level }}</span>@endif
                @if ($salary)<span class="font-semibold text-slate-700">{{ $salary }}</span>@endif
            </div>
            @if ($job->skills->isNotEmpty())<div class="mt-4 flex flex-wrap gap-2">@foreach ($job->skills->take(4) as $skill)<x-ui.chip>{{ $skill->name }}</x-ui.chip>@endforeach</div>@endif
        </div>
    </div>
    <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-4 text-xs text-slate-500 sm:mt-0 sm:flex-col sm:items-end sm:justify-between sm:border-0 sm:pt-0 {{ $layout === 'list' ? 'sm:min-h-24' : '' }}">
        <span>{{ $job->published_at?->diffForHumans() }}</span>
        <span class="font-medium text-brand-700">{{ $job->category?->name ?? 'Open opportunity' }}</span>
    </div>
</article>
