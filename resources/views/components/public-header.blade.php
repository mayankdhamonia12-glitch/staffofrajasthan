<header class="border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}" class="flex items-center gap-2 text-lg font-extrabold tracking-tight text-slate-950">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-blue-700 text-sm text-white">SR</span>
            <span>Staff of <span class="text-blue-700">Rajasthan</span></span>
        </a>

        <nav aria-label="Primary navigation" class="hidden items-center gap-7 text-sm font-semibold text-slate-600 lg:flex">
            <a class="transition hover:text-blue-700" href="{{ route('home') }}">Home</a>
            <a class="transition hover:text-blue-700" href="{{ route('jobs.index') }}">Jobs</a>
            <a class="transition hover:text-blue-700" href="{{ route('home') }}#companies">Companies</a>
            <a class="transition hover:text-blue-700" href="{{ route('home') }}#about">About</a>
            <a class="transition hover:text-blue-700" href="{{ route('home') }}#contact">Contact</a>
        </nav>

        <div class="hidden items-center gap-3 text-sm font-semibold sm:flex">
            @auth
                <a class="text-slate-700 hover:text-blue-700" href="{{ route('dashboard') }}">Dashboard</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-lg border border-slate-300 px-3 py-2 text-slate-700 hover:border-slate-400">Log out</button></form>
            @else
                <a class="text-slate-700 hover:text-blue-700" href="{{ route('login') }}">Log in</a>
                <a class="rounded-lg bg-blue-700 px-4 py-2 text-white shadow-sm transition hover:bg-blue-800" href="{{ route('register') }}">Register</a>
            @endauth
        </div>

        <details class="relative sm:hidden">
            <summary class="cursor-pointer list-none rounded-lg border border-slate-300 p-2 text-slate-700" aria-label="Open menu">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16" /></svg>
            </summary>
            <nav aria-label="Mobile navigation" class="absolute right-0 z-20 mt-3 w-52 rounded-xl border border-slate-200 bg-white p-3 shadow-xl">
                <a class="block rounded-lg px-3 py-2 hover:bg-blue-50" href="{{ route('home') }}">Home</a><a class="block rounded-lg px-3 py-2 hover:bg-blue-50" href="{{ route('jobs.index') }}">Jobs</a><a class="block rounded-lg px-3 py-2 hover:bg-blue-50" href="{{ route('home') }}#companies">Companies</a><a class="block rounded-lg px-3 py-2 hover:bg-blue-50" href="{{ route('home') }}#about">About</a><a class="block rounded-lg px-3 py-2 hover:bg-blue-50" href="{{ route('home') }}#contact">Contact</a>
                @guest <div class="mt-2 border-t border-slate-100 pt-2"><a class="block rounded-lg px-3 py-2 hover:bg-blue-50" href="{{ route('login') }}">Log in</a><a class="block rounded-lg bg-blue-700 px-3 py-2 text-white" href="{{ route('register') }}">Register</a></div> @endguest
                @auth <div class="mt-2 border-t border-slate-100 pt-2"><a class="block rounded-lg px-3 py-2 hover:bg-blue-50" href="{{ route('dashboard') }}">Dashboard</a><a class="block rounded-lg px-3 py-2 hover:bg-blue-50" href="{{ route('profile.edit') }}">Settings</a><form method="POST" action="{{ route('logout') }}">@csrf<button class="block w-full rounded-lg px-3 py-2 text-left hover:bg-blue-50">Log out</button></form></div> @endauth
            </nav>
        </details>
    </div>
</header>
