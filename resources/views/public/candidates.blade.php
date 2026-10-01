@extends('layouts.public')

@section('title', 'Candidate Directory | Staff of Rajasthan')
@section('description', 'Discover professionals across Rajasthan who have chosen to make their candidate profiles public.')

@section('content')
    <section class="bg-[#f3f7ff]"><div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8"><x-ui.breadcrumbs :items="[['label' => 'Candidates']]"/><x-ui.section-heading class="mt-6" level="1" eyebrow="Rajasthan’s workforce" title="Candidate directory" description="Explore real candidate profiles shared publicly by professionals across Rajasthan."/></div></section>
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <form method="GET" action="{{ route('candidates.index') }}" class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-3 lg:grid-cols-6">
            <label class="text-xs font-semibold text-slate-500 lg:col-span-2">Keyword<input name="keyword" value="{{ $filters['keyword'] ?? '' }}" placeholder="Name, skill, headline" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label>
            <label class="text-xs font-semibold text-slate-500">Location<input name="location" value="{{ $filters['location'] ?? '' }}" placeholder="City or state" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label>
            <label class="text-xs font-semibold text-slate-500">Category<select name="category" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->slug }}" @selected(($filters['category'] ?? '')===$category->slug)>{{ $category->name }}</option>@endforeach</select></label>
            <label class="text-xs font-semibold text-slate-500">Experience<select name="experience_level" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">Any level</option>@foreach(['entry'=>'Entry','junior'=>'Junior','mid'=>'Mid-level','senior'=>'Senior','lead'=>'Lead','executive'=>'Executive'] as $v=>$l)<option value="{{ $v }}" @selected(($filters['experience_level'] ?? '')===$v)>{{ $l }}</option>@endforeach</select></label>
            <label class="text-xs font-semibold text-slate-500">Sort<select name="sort" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="recent" @selected(($filters['sort'] ?? 'recent')==='recent')>Recently updated</option><option value="name" @selected(($filters['sort'] ?? '')==='name')>Name A–Z</option></select></label>
            <label class="text-xs font-semibold text-slate-500">Qualification<input name="qualification" value="{{ $filters['qualification'] ?? '' }}" placeholder="e.g. MBA" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label>
            <label class="text-xs font-semibold text-slate-500">Language<input name="language" value="{{ $filters['language'] ?? '' }}" placeholder="e.g. Hindi" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label>
            <div class="flex items-end gap-2 lg:col-span-2"><button class="rounded-lg bg-brand-700 px-5 py-2.5 text-sm font-bold text-white">Search candidates</button><a href="{{ route('candidates.index') }}" class="px-3 py-2.5 text-sm font-semibold text-slate-600">Clear</a></div>
        </form>
        <div class="mt-6 flex items-center justify-between"><p class="text-sm text-slate-600">{{ $profiles->total() }} {{ Str::plural('profile', $profiles->total()) }} shared publicly</p><a href="{{ route('register.candidate') }}" class="text-sm font-semibold text-brand-700 hover:underline">Create your candidate profile →</a></div>
        @if($profiles->isEmpty())
            <div class="mt-6"><x-ui.empty-state title="No public profiles match these filters" description="Try a different keyword or location. Candidate directory listings only include profiles that their owners have chosen to share." :action="route('candidates.index')" action-text="Clear search" icon="◎" /></div>
        @else
            <div class="mt-5 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach($profiles as $profile)
                    <article class="flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <div class="flex items-center gap-4"><div class="h-16 w-16 shrink-0 overflow-hidden rounded-full bg-slate-100">@if($profile->profile_photo_path)<img src="{{ route('candidates.photo',$profile->slug) }}" alt="" class="h-full w-full object-cover">@else<span class="grid h-full place-items-center text-xl font-bold text-slate-500">{{ mb_substr($profile->user->name,0,1) }}</span>@endif</div><div class="min-w-0"><h2 class="truncate text-lg font-bold text-slate-950"><a class="hover:text-brand-700" href="{{ route('candidates.show',$profile->slug) }}">{{ $profile->user->name }}</a></h2><p class="line-clamp-2 text-sm text-slate-600">{{ $profile->headline ?: 'Professional profile' }}</p></div></div>
                        <div class="mt-4 flex flex-wrap gap-x-3 gap-y-1 text-sm text-slate-500">@if($profile->city || $profile->state)<span>⌖ {{ collect([$profile->city,$profile->state])->filter()->join(', ') }}</span>@endif @if($profile->experience_level)<span>{{ str($profile->experience_level)->replace('_',' ')->title() }}</span>@endif @if($profile->years_experience !== null)<span>{{ $profile->years_experience }}+ yrs</span>@endif</div>
                        <p class="mt-4 line-clamp-3 flex-1 text-sm leading-6 text-slate-600">{{ $profile->about ?: 'This candidate has shared their professional profile.' }}</p>
                        @if($profile->skills->isNotEmpty())<div class="mt-4 flex flex-wrap gap-2">@foreach($profile->skills->take(4) as $skill)<span class="rounded-full bg-orange-50 px-2.5 py-1 text-xs font-medium text-orange-900">{{ $skill->name }}</span>@endforeach @if($profile->skills->count()>4)<span class="px-1 py-1 text-xs text-slate-500">+{{ $profile->skills->count()-4 }}</span>@endif</div>@endif
                        @if($profile->show_expected_salary && $profile->expected_salary)<p class="mt-4 text-sm font-semibold text-slate-800">Expected {{ number_format($profile->expected_salary) }}{{ $profile->salary_type==='yearly' ? ' / year' : ($profile->salary_type==='monthly' ? ' / month' : ' / hour') }}</p>@endif
                        <a href="{{ route('candidates.show',$profile->slug) }}" class="mt-5 inline-flex items-center font-bold text-brand-700 hover:underline">View profile <span class="ml-1">→</span></a>
                    </article>
                @endforeach
            </div>
            <div class="mt-8">{{ $profiles->links() }}</div>
        @endif
    </section>
@endsection
