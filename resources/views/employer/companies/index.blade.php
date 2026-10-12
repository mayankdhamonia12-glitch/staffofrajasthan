<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <p class="text-sm font-medium text-brand-700">Employer workspace</p>
                <h2 class="mt-1 text-2xl font-bold text-slate-950">Company profiles</h2>
            </div>
            <a href="{{ route('employer.companies.create') }}" class="inline-flex items-center justify-center rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">Add company</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if (session('status'))<div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>@endif
        @if ($companies->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center">
                <h3 class="text-lg font-bold text-slate-950">Create your public company profile</h3>
                <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-600">Add the details candidates need to understand your team. You control whether the profile is visible on the public company directory.</p>
                <a href="{{ route('employer.companies.create') }}" class="mt-5 inline-flex rounded-xl bg-brand-700 px-5 py-3 text-sm font-semibold text-white hover:bg-brand-800">Create company profile</a>
            </div>
        @else
            <div class="grid gap-5 md:grid-cols-2">
                @foreach ($companies as $company)
                    <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex min-w-0 items-center gap-4">
                                <div class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded-xl bg-brand-50 text-lg font-bold text-brand-700">
                                    @if ($company->logo_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($company->logo_path) }}" alt="{{ $company->name }} logo" class="h-full w-full object-cover">@else{{ mb_substr($company->name, 0, 1) }}@endif
                                </div>
                                <div class="min-w-0"><h3 class="truncate font-bold text-slate-950">{{ $company->name }}</h3><p class="mt-1 text-sm text-slate-500">{{ $company->industry?->name ?? 'Industry not selected' }}</p></div>
                            </div>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $company->is_visible ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $company->is_visible ? 'Public' : 'Private' }}</span>
                        </div>
                        <div class="mt-5 flex flex-wrap gap-x-4 gap-y-2 text-sm text-slate-500"><span>{{ $company->location?->name ?? 'Location not selected' }}</span><span>{{ $company->jobs()->publiclyAvailable()->count() }} published roles</span></div>
                        <div class="mt-5 flex flex-wrap gap-3"><a href="{{ route('employer.companies.edit', $company) }}" class="rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-800">Edit profile</a>@if ($company->is_visible)<a href="{{ route('companies.show', $company) }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:border-brand-300">View public page</a>@endif</div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-app-layout>
