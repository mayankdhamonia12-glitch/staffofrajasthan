<header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/95 backdrop-blur-xl">
    <div class="mx-auto flex min-h-[76px] max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5 rounded-lg text-base font-extrabold tracking-tight text-slate-950 focus-visible:ring-2 focus-visible:ring-brand-600 sm:text-lg">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-brand-700 text-sm font-black text-white shadow-sm">SR</span>
            <span>Staff of <span class="text-brand-700">Rajasthan</span></span>
        </a>

        <nav aria-label="Primary navigation" class="hidden items-center gap-5 text-sm font-semibold text-slate-600 xl:flex 2xl:gap-7">
            <a class="rounded-md py-2 transition hover:text-brand-700" href="{{ route('home') }}">Home</a>
            <a class="rounded-md py-2 transition hover:text-brand-700" href="{{ route('jobs.index') }}">Jobs</a>
            <a class="rounded-md py-2 transition hover:text-brand-700" href="{{ route('candidates.index') }}">Candidates</a>
            <a class="rounded-md py-2 transition hover:text-brand-700" href="{{ route('companies.index') }}">Companies</a>
            <a class="rounded-md py-2 transition hover:text-brand-700" href="{{ route('blog.index') }}">Blog</a>
            <a class="rounded-md py-2 transition hover:text-brand-700" href="{{ route('about') }}">About</a>
            <a class="rounded-md py-2 transition hover:text-brand-700" href="{{ route('contact') }}">Contact</a>
        </nav>

        <div class="hidden shrink-0 items-center gap-2 text-sm font-semibold md:flex">
            @auth
                <x-ui.button :href="route('dashboard')" variant="ghost" size="sm">Dashboard</x-ui.button>
                <form method="POST" action="{{ route('logout') }}">@csrf<x-ui.button type="submit" variant="secondary" size="sm">Log out</x-ui.button></form>
            @else
                <x-ui.button :href="route('login')" variant="ghost" size="sm">Log in</x-ui.button>
                <x-ui.button :href="route('register')" size="sm">Register</x-ui.button>
                <x-ui.button :href="route('register.employer')" variant="secondary" size="sm" class="hidden 2xl:inline-flex">For employers</x-ui.button>
            @endauth
        </div>

        <details class="group relative xl:hidden">
            <summary class="grid h-11 w-11 cursor-pointer list-none place-items-center rounded-xl border border-slate-200 text-slate-700 transition hover:bg-slate-50 focus-visible:ring-2 focus-visible:ring-brand-600" aria-label="Toggle navigation menu">
                <svg class="h-5 w-5 group-open:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
                <svg class="hidden h-5 w-5 group-open:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 6 12 12M18 6 6 18" /></svg>
            </summary>
            <nav aria-label="Mobile navigation" class="absolute right-0 z-20 mt-3 grid w-[min(90vw,22rem)] gap-1 rounded-2xl border border-slate-200 bg-white p-3 shadow-xl shadow-slate-950/10">
                <a class="rounded-lg px-3 py-2.5 font-medium hover:bg-brand-50 hover:text-brand-800" href="{{ route('home') }}">Home</a>
                <a class="rounded-lg px-3 py-2.5 font-medium hover:bg-brand-50 hover:text-brand-800" href="{{ route('jobs.index') }}">Jobs</a>
                <a class="rounded-lg px-3 py-2.5 font-medium hover:bg-brand-50 hover:text-brand-800" href="{{ route('candidates.index') }}">Candidates</a>
                <a class="rounded-lg px-3 py-2.5 font-medium hover:bg-brand-50 hover:text-brand-800" href="{{ route('companies.index') }}">Companies</a>
                <a class="rounded-lg px-3 py-2.5 font-medium hover:bg-brand-50 hover:text-brand-800" href="{{ route('blog.index') }}">Blog</a>
                <a class="rounded-lg px-3 py-2.5 font-medium hover:bg-brand-50 hover:text-brand-800" href="{{ route('about') }}">About</a>
                <a class="rounded-lg px-3 py-2.5 font-medium hover:bg-brand-50 hover:text-brand-800" href="{{ route('contact') }}">Contact</a>
                <div class="mt-2 grid grid-cols-2 gap-2 border-t border-slate-100 pt-3">
                    @auth
                        <x-ui.button :href="route('dashboard')" variant="secondary" size="sm">Dashboard</x-ui.button>
                        <form method="POST" action="{{ route('logout') }}">@csrf<x-ui.button type="submit" variant="ghost" size="sm" class="w-full">Log out</x-ui.button></form>
                    @else
                        <x-ui.button :href="route('login')" variant="secondary" size="sm">Log in</x-ui.button>
                        <x-ui.button :href="route('register')" size="sm">Register</x-ui.button>
                        <x-ui.button :href="route('register.employer')" variant="soft" size="sm" class="col-span-2">For employers</x-ui.button>
                    @endauth
                </div>
            </nav>
        </details>
    </div>
</header>
