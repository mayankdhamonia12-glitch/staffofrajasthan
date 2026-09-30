@props(['company'])
<x-ui.card :interactive="true" class="flex h-full flex-col">
    <div class="flex items-start gap-4">
        <div class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-xl border border-slate-100 bg-brand-50 text-lg font-bold text-brand-700">
            @if ($company->logo_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($company->logo_path) }}" alt="{{ $company->name }} logo" class="h-full w-full object-cover">@else<span aria-hidden="true">{{ mb_substr($company->name, 0, 1) }}</span>@endif
        </div>
        <div class="min-w-0"><h3 class="truncate font-bold text-slate-950">{{ $company->name }}</h3><p class="mt-1 truncate text-sm text-slate-500">{{ $company->industry?->name ?? 'Employer' }}</p></div>
    </div>
    <div class="mt-5 flex flex-wrap gap-x-4 gap-y-2 text-sm text-slate-600">
        @if ($company->location)<span>{{ $company->location->name }}{{ $company->location->state ? ', '.$company->location->state : '' }}</span>@endif
        <span>{{ number_format($company->jobs_count) }} {{ \Illuminate\Support\Str::plural('open role', $company->jobs_count) }}</span>
    </div>
    <x-ui.button :href="route('companies.show', $company)" variant="secondary" size="sm" class="mt-5 w-full">View company <span aria-hidden="true">→</span></x-ui.button>
</x-ui.card>
