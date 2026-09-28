<nav class="border-b border-slate-200 bg-white">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}" class="text-lg font-bold tracking-tight text-slate-900">
            Staff of <span class="text-orange-700">Rajasthan</span>
        </a>

        <div class="hidden items-center gap-6 text-sm font-medium text-slate-600 md:flex">
            <a class="hover:text-orange-700" href="{{ route('home') }}">Home</a>
            <a class="hover:text-orange-700" href="{{ route('home') }}#jobs">Jobs</a>
            <a class="hover:text-orange-700" href="{{ route('home') }}#companies">Companies</a>
            <a class="hover:text-orange-700" href="{{ route('home') }}#about">About</a>
            <a class="hover:text-orange-700" href="{{ route('home') }}#contact">Contact</a>
        </div>

        <div class="flex items-center gap-3 text-sm font-semibold">
            @auth
                <a class="text-slate-700 hover:text-orange-700" href="{{ route('dashboard') }}">Dashboard</a>
                <a class="text-slate-700 hover:text-orange-700" href="{{ route('profile.edit') }}">Settings</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="rounded-lg border border-slate-300 px-3 py-2 text-slate-700 hover:border-slate-400">Log out</button>
                </form>
            @else
                <a class="text-slate-700 hover:text-orange-700" href="{{ route('login') }}">Log in</a>
                <a class="rounded-lg bg-orange-700 px-4 py-2 text-white hover:bg-orange-800" href="{{ route('register') }}">Register</a>
            @endauth
        </div>
    </div>
</nav>
