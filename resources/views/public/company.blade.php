@extends('layouts.public')

@section('title', $company->name.' | Staff of Rajasthan')
@section('description', 'Explore current opportunities at '.$company->name.' in Rajasthan.')

@section('structured_data')
    @php($organization = array_filter(['@context' => 'https://schema.org', '@type' => 'Organization', 'name' => $company->name, 'url' => $company->website, 'logo' => $company->logo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($company->logo_path) : null, 'description' => $company->description]))
    <script type="application/ld+json">{!! json_encode($organization, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endsection

@section('content')
    <section class="bg-[#f3f7ff]">
        <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
            <x-ui.breadcrumbs :items="[['label' => 'Companies', 'url' => route('companies.index')], ['label' => $company->name]]" />
            @if ($company->cover_path)<div class="mt-7 h-36 overflow-hidden rounded-2xl bg-slate-200 sm:h-52"><img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($company->cover_path) }}" alt="{{ $company->name }} cover" class="h-full w-full object-cover"></div>@endif
            <div class="mt-7 flex flex-col gap-6 sm:flex-row sm:items-center">
                <div class="grid h-20 w-20 shrink-0 place-items-center overflow-hidden rounded-2xl border border-slate-200 bg-white text-2xl font-bold text-brand-700 shadow-sm">
                    @if ($company->logo_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($company->logo_path) }}" alt="{{ $company->name }} logo" class="h-full w-full object-cover">@else<span aria-hidden="true">{{ mb_substr($company->name, 0, 1) }}</span>@endif
                </div>
                <div class="min-w-0 flex-1">
                    <x-ui.badge>{{ $company->industry?->name ?? 'Rajasthan employer' }}</x-ui.badge>
                    <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl">{{ $company->name }}</h1>
                    <div class="mt-2 flex flex-wrap gap-x-5 gap-y-2 text-sm text-slate-600">
                        @if ($company->location)<span>{{ $company->location->name }}{{ $company->location->state ? ', '.$company->location->state : '' }}</span>@endif
                        @if ($company->company_size)<span>Company size · {{ $company->company_size }}</span>@endif
                        <span>{{ number_format($company->jobs_count ?? $jobs->total()) }} {{ \Illuminate\Support\Str::plural('open role', $company->jobs_count ?? $jobs->total()) }}</span>
                    </div>
                </div>
                <x-ui.button :href="route('jobs.index', ['keyword' => $company->name])">Browse open roles <span aria-hidden="true">→</span></x-ui.button>
                @auth
                    @if(auth()->user()->isCandidate())
                        @if($isFollowing)
                            <form method="POST" action="{{ route('companies.unfollow', $company) }}">@csrf @method('DELETE')<button class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Following · Unfollow</button></form>
                        @else
                            <form method="POST" action="{{ route('companies.follow', $company) }}">@csrf<button class="rounded-xl border border-brand-700 bg-white px-5 py-3 text-sm font-bold text-brand-700 hover:bg-blue-50">Follow company</button></form>
                        @endif
                    @endif
                @else
                    <a href="{{ route('login') }}" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-bold text-slate-700 hover:bg-slate-50">Sign in to follow</a>
                @endauth
            </div>
        </div>
    </section>

    <section class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 sm:py-16 lg:grid-cols-[.7fr_1.3fr] lg:px-8">
        <aside>
            <h2 class="text-lg font-bold text-slate-950">About the company</h2>
            @if ($company->description)
                <div class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-600">{{ $company->description }}</div>
            @else
                <p class="mt-3 text-sm leading-7 text-slate-600">This employer has not added a company description yet.</p>
            @endif
            @if ($company->address || $company->founded_year || $company->website || $company->email || $company->phone)
                <dl class="mt-6 space-y-3 border-t border-slate-100 pt-6 text-sm text-slate-600">
                    @if ($company->address)<div><dt class="font-semibold text-slate-800">Address</dt><dd class="mt-1 whitespace-pre-line">{{ $company->address }}</dd></div>@endif
                    @if ($company->founded_year)<div><dt class="font-semibold text-slate-800">Founded</dt><dd class="mt-1">{{ $company->founded_year }}</dd></div>@endif
                    @if ($company->website)<div><dt class="font-semibold text-slate-800">Website</dt><dd class="mt-1"><a class="break-all text-brand-700 hover:underline" rel="nofollow noopener" target="_blank" href="{{ $company->website }}">{{ $company->website }}</a></dd></div>@endif
                    @if ($company->email)<div><dt class="font-semibold text-slate-800">Email</dt><dd class="mt-1"><a class="text-brand-700 hover:underline" href="mailto:{{ $company->email }}">{{ $company->email }}</a></dd></div>@endif
                    @if ($company->phone)<div><dt class="font-semibold text-slate-800">Phone</dt><dd class="mt-1"><a class="text-brand-700 hover:underline" href="tel:{{ $company->phone }}">{{ $company->phone }}</a></dd></div>@endif
                </dl>
            @endif
            @if ($company->linkedin_url || $company->facebook_url || $company->twitter_url || $company->instagram_url)
                <div class="mt-6 flex flex-wrap gap-3">@foreach(['linkedin_url' => 'LinkedIn', 'facebook_url' => 'Facebook', 'twitter_url' => 'X / Twitter', 'instagram_url' => 'Instagram'] as $field => $label) @if ($company->{$field})<a href="{{ $company->{$field} }}" target="_blank" rel="nofollow noopener" class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-brand-50 hover:text-brand-700">{{ $label }}</a>@endif @endforeach</div>
            @endif
            <x-ui.button :href="route('companies.index')" variant="ghost" size="sm" class="mt-4">All companies <span aria-hidden="true">→</span></x-ui.button>
        </aside>
        <div>
            <x-ui.section-heading eyebrow="Join the team" title="Current opportunities" description="Published roles from {{ $company->name }}." />
            @if ($jobs->isEmpty())
                <x-ui.empty-state class="mt-6" title="No open roles right now" description="This company does not currently have published opportunities. Browse other employers or check back later." :action="route('companies.index')" action-text="Explore companies" />
            @else
                <div class="mt-6 grid gap-4">@foreach ($jobs as $job)<x-job-card :job="$job" layout="list" />@endforeach</div>
                <x-ui.pagination :paginator="$jobs" />
            @endif
        </div>
    </section>
@endsection
