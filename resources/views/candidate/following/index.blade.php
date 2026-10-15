<x-candidate-dashboard-layout title="Following Employers" active="following" subtitle="Keep up with public companies you follow.">
    @if (session('status'))<x-ui.alert tone="success">{{ session('status') }}</x-ui.alert>@endif
    <div><p class="text-sm font-semibold text-brand-700">Your company list</p><h2 class="mt-1 text-2xl font-extrabold">Following employers</h2><p class="mt-1 text-sm text-slate-500">New public roles from these companies can appear in your notifications.</p></div>
    @if($companies->isEmpty())
        <x-ui.empty-state title="You are not following any employers" description="Visit a public company profile and choose Follow to keep it here." :action="route('companies.index')" action-text="Explore companies" icon="♧" />
    @else
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($companies as $company)
                <article class="flex min-w-0 flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="flex items-start gap-3"><div class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-xl bg-blue-50 font-bold text-brand-700">@if($company->logo_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($company->logo_path) }}" alt="" class="h-full w-full object-cover">@else{{ mb_substr($company->name, 0, 1) }}@endif</div><div class="min-w-0"><h3 class="truncate font-bold"><a href="{{ route('companies.show', $company) }}" class="hover:text-brand-700">{{ $company->name }}</a></h3><p class="mt-1 truncate text-sm text-slate-500">{{ $company->industry?->name ?? 'Employer' }}@if($company->location) · {{ $company->location->name }}@endif</p></div></div>
                    <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-4"><span class="text-sm font-semibold text-slate-600">{{ number_format($company->open_jobs_count) }} open {{ \Illuminate\Support\Str::plural('role', $company->open_jobs_count) }}</span><a href="{{ route('companies.show', $company) }}" class="text-sm font-semibold text-brand-700 hover:underline">Company profile</a></div>
                    <form method="POST" action="{{ route('companies.unfollow', $company) }}" class="mt-3">@csrf @method('DELETE')<button class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Unfollow</button></form>
                </article>
            @endforeach
        </div>
        <x-ui.pagination :paginator="$companies" />
    @endif
</x-candidate-dashboard-layout>
