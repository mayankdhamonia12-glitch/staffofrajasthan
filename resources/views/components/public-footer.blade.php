<footer id="contact" class="bg-slate-950 text-slate-300">
    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 md:grid-cols-2 lg:grid-cols-4 lg:px-8">
        <div><p class="text-lg font-bold text-white">Staff of <span class="text-blue-400">Rajasthan</span></p><p class="mt-4 text-sm leading-6">A focused platform for people building careers and employers building teams across Rajasthan.</p></div>
        <div><h2 class="font-semibold text-white">Explore</h2><ul class="mt-4 space-y-3 text-sm"><li><a href="{{ route('jobs.index') }}" class="hover:text-white">Jobs</a></li><li><a href="{{ route('home') }}#companies" class="hover:text-white">Companies</a></li><li><a href="{{ route('home') }}#categories" class="hover:text-white">Categories</a></li></ul></div>
        <div><h2 class="font-semibold text-white">For you</h2><ul class="mt-4 space-y-3 text-sm"><li><a href="{{ route('register.candidate') }}" class="hover:text-white">Find a job</a></li><li><a href="{{ route('register.employer') }}" class="hover:text-white">Hire talent</a></li><li><a href="{{ route('login') }}" class="hover:text-white">Account login</a></li></ul></div>
        <div><h2 class="font-semibold text-white">Get in touch</h2><p class="mt-4 text-sm leading-6">Questions about the platform? Our support channels will be published with the public launch.</p></div>
    </div>
    <div class="border-t border-slate-800"><div class="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-5 text-xs sm:flex-row sm:justify-between sm:px-6 lg:px-8"><span>© {{ now()->year }} Staff of Rajasthan. All rights reserved.</span><span>Built for Rajasthan’s workforce.</span></div></div>
</footer>
