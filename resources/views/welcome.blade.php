@extends('layouts.public')

@section('title', 'Find Your Next Job in Rajasthan | Staff of Rajasthan')
@section('description', 'Discover current jobs, growing employers, and career opportunities across Rajasthan.')

@section('content')
    <section class="relative isolate overflow-hidden bg-[#f3f7ff]">
        <div aria-hidden="true" class="absolute inset-0 -z-10 overflow-hidden">
            <div class="absolute -right-36 -top-48 h-[34rem] w-[34rem] rounded-full bg-brand-100/70 blur-3xl"></div>
            <div class="absolute -bottom-48 left-1/4 h-80 w-80 rounded-full bg-white/80 blur-3xl"></div>
            <div class="absolute inset-0 opacity-[.18] [background-image:radial-gradient(#1d4ed8_1px,transparent_1px)] [background-size:24px_24px]"></div>
        </div>
        <div class="mx-auto grid max-w-7xl items-center gap-10 px-4 py-14 sm:px-6 sm:py-20 lg:grid-cols-[1.08fr_.92fr] lg:px-8 lg:py-24">
            <div class="relative z-10">
                <x-ui.badge><span class="mr-2 inline-block h-1.5 w-1.5 rounded-full bg-brand-600"></span>Careers rooted in Rajasthan</x-ui.badge>
                <h1 class="mt-6 max-w-3xl text-4xl font-extrabold leading-[1.08] tracking-tight text-slate-950 sm:text-5xl lg:text-[3.75rem]">Find a job that moves you <span class="text-brand-700">forward.</span></h1>
                <p class="mt-5 max-w-2xl text-base leading-7 text-slate-600 sm:text-lg sm:leading-8">Explore real opportunities from employers across Rajasthan and take the next step in your career.</p>

                <div class="mt-8 max-w-3xl">
                    <x-search-form :action="route('jobs.index')" />
                </div>

                <div class="mt-5 flex flex-wrap items-center gap-2.5" aria-label="Popular job searches">
                    <span class="mr-1 text-sm font-semibold text-slate-600">Explore:</span>
                    @forelse ($popularLocations->take(2) as $location)
                        <x-ui.chip :href="route('jobs.index', ['location' => $location->name])">{{ $location->name }}</x-ui.chip>
                    @empty
                    @endforelse
                    @foreach ($categories->take(3) as $category)
                        <x-ui.chip :href="route('jobs.index', ['category' => $category->slug])">{{ $category->name }}</x-ui.chip>
                    @endforeach
                    @if ($popularLocations->isEmpty() && $categories->isEmpty())
                        <x-ui.chip :href="route('jobs.index')">Browse all jobs</x-ui.chip>
                    @endif
                </div>
            </div>

            <div class="relative mx-auto w-full max-w-[550px] lg:ml-auto" aria-label="Rajasthan career opportunities">
                <div aria-hidden="true" class="absolute left-1/2 top-1/2 aspect-square w-[82%] -translate-x-1/2 -translate-y-1/2 rounded-[42%] bg-gradient-to-br from-brand-200 via-brand-100 to-white"></div>
                <div aria-hidden="true" class="absolute left-[11%] top-[17%] h-16 w-16 rounded-2xl border border-white/80 bg-white/70 shadow-sm rotate-[-12deg]"></div>
                <div aria-hidden="true" class="absolute bottom-[12%] right-[8%] h-24 w-24 rounded-full border-[18px] border-white/60"></div>
                <div class="relative mx-auto max-w-[430px] rounded-[2rem] border border-white/90 bg-white/90 p-5 shadow-[0_30px_90px_-40px_rgba(30,64,175,.42)] backdrop-blur sm:p-7">
                    <div class="flex items-center justify-between gap-4">
                        <div><p class="text-xs font-bold uppercase tracking-[.16em] text-brand-700">Your next chapter</p><p class="mt-1 text-lg font-bold text-slate-950 sm:text-xl">Start with the right opportunity</p></div>
                        <span aria-hidden="true" class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-brand-50 text-brand-700"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M4 19.5h16M6.5 16V9.5L12 5l5.5 4.5V16M9.5 16v-3.5h5V16" /></svg></span>
                    </div>
                    <div class="mt-6 grid grid-cols-2 gap-3">
                        <div class="rounded-2xl bg-brand-50 p-4 sm:p-5"><p class="text-3xl font-extrabold tracking-tight text-brand-800">{{ number_format($jobCount) }}</p><p class="mt-1 text-sm font-medium text-slate-600">Open opportunities</p></div>
                        <div class="rounded-2xl bg-slate-50 p-4 sm:p-5"><p class="text-3xl font-extrabold tracking-tight text-slate-900">{{ number_format($categoryCount) }}</p><p class="mt-1 text-sm font-medium text-slate-600">Career categories</p></div>
                    </div>
                    <div class="mt-4 rounded-2xl border border-slate-100 p-4 sm:p-5">
                        <div class="flex items-center justify-between"><p class="font-bold text-slate-900">A better way to find work</p><span aria-hidden="true" class="text-brand-700">✦</span></div>
                        <div class="mt-4 space-y-3">
                            <div class="flex items-center gap-3"><span class="grid h-9 w-9 place-items-center rounded-xl bg-emerald-50 text-emerald-700" aria-hidden="true">✓</span><div><p class="text-sm font-semibold text-slate-800">Current employer listings</p><p class="text-xs text-slate-500">Only roles published on the platform</p></div></div>
                            <div class="flex items-center gap-3"><span class="grid h-9 w-9 place-items-center rounded-xl bg-amber-50 text-amber-700" aria-hidden="true">⌖</span><div><p class="text-sm font-semibold text-slate-800">Local opportunities</p><p class="text-xs text-slate-500">Focused on Rajasthan’s workforce</p></div></div>
                        </div>
                    </div>
                    <a href="{{ route('jobs.index') }}" class="mt-4 flex items-center justify-between rounded-xl px-1 py-2 text-sm font-bold text-brand-700 hover:text-brand-900">Explore the job directory <span aria-hidden="true">→</span></a>
                </div>
                <div class="absolute -left-2 top-1/2 hidden -translate-x-1/4 -translate-y-1/2 rounded-2xl border border-slate-100 bg-white p-4 shadow-xl sm:block lg:-left-5">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-amber-50 text-amber-700" aria-hidden="true">✦</span>
                    <p class="mt-2 text-xs font-bold text-slate-800">Built for your next move</p>
                </div>
            </div>
        </div>
    </section>

    <section id="categories" class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
        <x-ui.section-heading eyebrow="Find your field" title="Explore popular categories" description="Browse career areas with current openings from employers across Rajasthan." :link="route('jobs.index')" link-text="Browse all jobs" />
        @if ($categories->isEmpty())
            <x-ui.empty-state class="mt-8" title="Categories are taking shape" description="Job categories will appear here as employers publish opportunities. In the meantime, you can browse all current listings." :action="route('jobs.index')" action-text="Browse jobs" icon="▦" />
        @else
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">@foreach ($categories as $category)<x-category-card :category="$category" />@endforeach</div>
        @endif
    </section>

    <section id="jobs" class="border-y border-slate-100 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
            <x-ui.section-heading eyebrow="Handpicked opportunities" title="Featured jobs" description="Standout roles from employers hiring right now." :link="route('jobs.index')" link-text="See all jobs" />
            @if ($featuredJobs->isEmpty())
                <x-ui.empty-state class="mt-8" title="No featured jobs just yet" description="Featured roles will show here when employers publish them. Browse the full directory for current opportunities." :action="route('jobs.index')" action-text="Explore jobs" icon="✦" />
            @else
                <div class="mt-8 grid gap-4 lg:grid-cols-3">@foreach ($featuredJobs as $job)<x-job-card :job="$job" />@endforeach</div>
            @endif
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
        <x-ui.section-heading eyebrow="Recently published" title="The latest opportunities" description="Fresh listings from companies across Rajasthan." :link="route('jobs.index')" link-text="Browse the job directory" />
        @if ($latestJobs->isEmpty())
            <x-ui.empty-state class="mt-8" title="There are no current job listings" description="New employer listings will appear here when they are published. Check back soon or explore how to get started." :action="route('register.candidate')" action-text="Create candidate account" icon="⌕" />
        @else
            <div class="mt-8 grid gap-4">@foreach ($latestJobs as $job)<x-job-card :job="$job" layout="list" />@endforeach</div>
        @endif
    </section>

    <section id="companies" class="bg-[#f3f7ff]">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
            <x-ui.section-heading eyebrow="Meet the teams" title="Employers to know" description="Discover companies with current open roles." :link="route('companies.index')" link-text="Explore companies" />
            @if ($companies->isEmpty())
                <x-ui.empty-state class="mt-8" title="Company profiles are on the way" description="Employer cards will appear here when visible company profiles have published jobs." :action="route('register.employer')" action-text="Create employer account" icon="⌂" />
            @else
                <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">@foreach ($companies as $company)<x-company-card :company="$company" />@endforeach</div>
            @endif
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
        <x-ui.section-heading eyebrow="Start today" title="A better next step starts here" description="Whether you’re exploring a new role or building a team, Staff of Rajasthan is here to help you move forward." />
        <div class="mt-8 grid gap-5 lg:grid-cols-2">
            <article class="relative overflow-hidden rounded-3xl bg-brand-800 p-7 text-white shadow-lg shadow-brand-900/10 sm:p-10">
                <div aria-hidden="true" class="absolute -right-16 -top-16 h-56 w-56 rounded-full border-[30px] border-white/10"></div>
                <p class="text-sm font-bold uppercase tracking-[.14em] text-brand-200">For candidates</p><h3 class="mt-3 max-w-md text-3xl font-extrabold tracking-tight sm:text-4xl">Find your next opportunity</h3><p class="mt-3 max-w-lg leading-7 text-blue-100">Create your profile, add your experience, and discover roles that match your goals.</p>
                <x-ui.button :href="route('register.candidate')" variant="secondary" class="mt-6">Create candidate account <span aria-hidden="true">→</span></x-ui.button>
            </article>
            <article class="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-7 shadow-sm sm:p-10">
                <div aria-hidden="true" class="absolute -right-16 -top-16 h-56 w-56 rounded-full border-[30px] border-brand-50"></div>
                <p class="text-sm font-bold uppercase tracking-[.14em] text-brand-700">For employers</p><h3 class="mt-3 max-w-md text-3xl font-extrabold tracking-tight text-slate-950 sm:text-4xl">Find the talent your business needs</h3><p class="mt-3 max-w-lg leading-7 text-slate-600">Join the platform and get ready to connect with people building their careers in Rajasthan.</p>
                <x-ui.button :href="route('register.employer')" class="mt-6">Create employer account <span aria-hidden="true">→</span></x-ui.button>
            </article>
        </div>
    </section>

    <section class="border-t border-slate-100 bg-white">
        <div class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
            <x-ui.section-heading eyebrow="Career resources" title="Ideas for your next move" description="Practical insights for candidates and employers will live here." :link="route('blog.index')" link-text="Visit the blog" />
            <x-ui.empty-state class="mt-8" title="The blog is coming soon" description="We’re preparing useful career and hiring resources. No articles have been published yet." :action="route('blog.index')" action-text="Learn about the platform" icon="✎" />
        </div>
    </section>
@endsection
