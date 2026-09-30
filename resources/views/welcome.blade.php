<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Staff of Rajasthan connects job seekers with employers across Rajasthan.">
    <meta name="theme-color" content="#c2410c">
    <title>Find jobs and talent in Rajasthan | Staff of Rajasthan</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    @unless (app()->environment('testing'))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endunless
</head>
<body id="top" class="bg-slate-50 font-sans text-slate-900 antialiased">
    <x-public-header />

    <main>
        <section class="overflow-hidden bg-slate-950">
            <div class="mx-auto grid max-w-7xl gap-12 px-4 py-20 sm:px-6 lg:grid-cols-[1.2fr_.8fr] lg:px-8 lg:py-28">
                <div class="relative z-10">
                    <p class="inline-flex rounded-full border border-orange-400/30 bg-orange-400/10 px-3 py-1 text-sm font-semibold text-orange-300">Careers made local</p>
                    <h1 class="mt-6 max-w-3xl text-4xl font-extrabold tracking-tight text-white sm:text-6xl">Find Your Next Job</h1>
                    <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-300">Find opportunities that move your career forward, and discover the people who will move your business forward.</p>

                    <form class="mt-9 rounded-2xl bg-white p-3 shadow-2xl" action="#jobs" method="get" aria-label="Job search preview">
                        <div class="grid gap-3 md:grid-cols-[1fr_1fr_auto]">
                            <label class="rounded-xl border border-slate-200 px-4 py-3"><span class="sr-only">Job title or keyword</span><input class="w-full outline-none placeholder:text-slate-400" name="keyword" placeholder="Job title or keywords" disabled></label>
                            <label class="rounded-xl border border-slate-200 px-4 py-3"><span class="sr-only">Location</span><input class="w-full outline-none placeholder:text-slate-400" name="location" placeholder="Location in Rajasthan" disabled></label>
                            <button type="button" disabled class="rounded-xl bg-orange-700 px-6 py-3 font-bold text-white opacity-75" title="Search launches when job listings are available">Search Jobs</button>
                        </div>
                        <p class="px-1 pt-3 text-xs text-slate-500">Search is being connected to the upcoming job listings database.</p>
                    </form>
                </div>
                <aside class="self-end rounded-3xl border border-white/10 bg-white/5 p-7 text-white shadow-2xl backdrop-blur">
                    <p class="text-sm font-semibold text-orange-300">Your marketplace, taking shape</p>
                    <div class="mt-6 grid grid-cols-2 gap-4"><div class="rounded-2xl bg-white/10 p-4"><p class="text-2xl font-bold">Jobs</p><p class="mt-1 text-sm text-slate-300">Listings launch soon</p></div><div class="rounded-2xl bg-white/10 p-4"><p class="text-2xl font-bold">Talent</p><p class="mt-1 text-sm text-slate-300">Profiles underway</p></div></div>
                </aside>
            </div>
        </section>

        <section id="categories" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8"><div class="max-w-2xl"><p class="text-sm font-bold uppercase tracking-widest text-orange-700">Explore by field</p><h2 class="mt-3 text-3xl font-extrabold tracking-tight">Popular categories</h2><p class="mt-3 text-slate-600">The jobs board will organize opportunities around the work people do.</p></div><div class="mt-9 grid gap-4 sm:grid-cols-2 lg:grid-cols-4"><x-category-card name="IT & Software" description="Build products and systems." /><x-category-card name="Sales & Marketing" description="Grow brands and revenue." /><x-category-card name="Finance & Accounting" description="Keep business moving." /><x-category-card name="Healthcare" description="Care for communities." /><x-category-card name="Education" description="Shape the next generation." /><x-category-card name="Engineering" description="Design what comes next." /><x-category-card name="Hospitality" description="Create exceptional service." /><x-category-card name="Logistics" description="Keep Rajasthan connected." /></div></section>

        <section id="jobs" class="bg-white"><div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8"><div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><p class="text-sm font-bold uppercase tracking-widest text-orange-700">Coming with the job board</p><h2 class="mt-3 text-3xl font-extrabold tracking-tight">Featured jobs</h2></div><span class="text-sm text-slate-500">Preview cards — no job data has been published yet.</span></div><div class="mt-9 grid gap-5 lg:grid-cols-3">@foreach ([['Role matched to your skills', 'Company profile', 'Jaipur, Rajasthan'], ['Opportunities built for Rajasthan', 'Company profile', 'Across Rajasthan'], ['A better way to find your next role', 'Company profile', 'Location coming soon']] as [$title, $company, $location])<article class="rounded-2xl border border-slate-200 p-6"><span class="text-xs font-bold uppercase tracking-wider text-orange-700">Job preview</span><h3 class="mt-4 text-xl font-bold">{{ $title }}</h3><p class="mt-2 text-sm text-slate-500">{{ $company }} · {{ $location }}</p><p class="mt-5 text-sm leading-6 text-slate-600">This placement is intentionally marked as a preview until employer-created job listings are available.</p></article>@endforeach</div></div></section>

        <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8"><div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><p class="text-sm font-bold uppercase tracking-widest text-orange-700">Fresh opportunities</p><h2 class="mt-3 text-3xl font-extrabold tracking-tight">Latest jobs</h2></div><span class="rounded-full bg-slate-200 px-3 py-1 text-sm font-semibold text-slate-600">Awaiting job listings</span></div><div class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-slate-100 p-8 text-center"><p class="font-semibold text-slate-700">Latest employer listings will appear here.</p><p class="mt-2 text-sm text-slate-500">No placeholder job records have been created.</p></div></section>

        <section id="companies" class="bg-orange-50"><div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8"><p class="text-sm font-bold uppercase tracking-widest text-orange-700">For growing teams</p><h2 class="mt-3 text-3xl font-extrabold tracking-tight">Popular companies</h2><p class="mt-3 max-w-2xl text-slate-600">Company profiles will be shown here once employers join and publish their profiles.</p><div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">@foreach (['Employer profiles', 'Company stories', 'Open roles', 'Hiring updates'] as $company)<div class="rounded-2xl border border-orange-100 bg-white p-5"><div class="grid h-11 w-11 place-items-center rounded-xl bg-slate-100 font-bold text-slate-500">SR</div><p class="mt-5 font-bold text-slate-800">{{ $company }}</p><p class="mt-1 text-sm text-slate-500">Available after launch</p></div>@endforeach</div></div></section>

        <section id="about" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8"><div class="grid gap-6 lg:grid-cols-2"><div class="rounded-3xl bg-slate-950 p-8 text-white sm:p-12"><p class="text-sm font-bold uppercase tracking-widest text-orange-300">For candidates</p><h2 class="mt-4 text-3xl font-extrabold">Bring your experience into focus.</h2><p class="mt-4 max-w-lg leading-7 text-slate-300">Create your secure profile now, add your skills and resume, and be ready when job applications open.</p><a href="{{ route('register.candidate') }}" class="mt-7 inline-flex rounded-lg bg-orange-600 px-5 py-3 font-bold hover:bg-orange-500">Create candidate account</a></div><div class="rounded-3xl bg-orange-700 p-8 text-white sm:p-12"><p class="text-sm font-bold uppercase tracking-widest text-orange-100">For employers</p><h2 class="mt-4 text-3xl font-extrabold">Build the team behind your next success.</h2><p class="mt-4 max-w-lg leading-7 text-orange-50">Register your employer account today. Company profiles and job posting tools are the next core platform milestone.</p><a href="{{ route('register.employer') }}" class="mt-7 inline-flex rounded-lg bg-white px-5 py-3 font-bold text-orange-800 hover:bg-orange-50">Create employer account</a></div></div></section>
    </main>

    <x-public-footer />
</body>
</html>
