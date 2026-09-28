<x-guest-layout>
    <div class="space-y-6">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-orange-700">Staff of Rajasthan</p>
            <h1 class="mt-2 text-2xl font-bold text-slate-900">Join the right way</h1>
            <p class="mt-2 text-sm text-slate-600">Choose the account that matches what you want to do. Account type is set securely by the registration route.</p>
        </div>

        <a href="{{ route('register.candidate') }}" class="block rounded-xl border border-slate-200 p-5 transition hover:border-orange-500 hover:bg-orange-50">
            <span class="block text-lg font-semibold text-slate-900">I’m looking for a job</span>
            <span class="mt-1 block text-sm text-slate-600">Build your profile, discover roles, and track applications.</span>
        </a>

        <a href="{{ route('register.employer') }}" class="block rounded-xl border border-slate-200 p-5 transition hover:border-orange-500 hover:bg-orange-50">
            <span class="block text-lg font-semibold text-slate-900">I’m hiring</span>
            <span class="mt-1 block text-sm text-slate-600">Create a company profile, post jobs, and manage applicants.</span>
        </a>

        <p class="text-center text-sm text-slate-600">Already have an account? <a class="font-semibold text-orange-700 hover:text-orange-800" href="{{ route('login') }}">Log in</a></p>
    </div>
</x-guest-layout>
