<x-guest-layout>
    <div class="space-y-6">
        <div>
            <p class="text-sm font-semibold uppercase tracking-[0.16em] text-brand-700">Staff of Rajasthan</p>
            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950">Choose how you’ll use the platform</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">Select the account that matches your goals. Your account type is securely set by the registration route.</p>
        </div>

        <a href="{{ route('register.candidate') }}" class="block rounded-2xl border border-slate-200 p-5 transition hover:border-brand-300 hover:bg-brand-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
            <span class="block text-lg font-semibold text-slate-900">I’m looking for a job</span>
            <span class="mt-1 block text-sm leading-6 text-slate-600">Build your profile, add your experience, and discover current roles.</span>
        </a>

        <a href="{{ route('register.employer') }}" class="block rounded-2xl border border-slate-200 p-5 transition hover:border-brand-300 hover:bg-brand-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-600">
            <span class="block text-lg font-semibold text-slate-900">I’m hiring</span>
            <span class="mt-1 block text-sm leading-6 text-slate-600">Create an employer account and get ready for the hiring tools taking shape.</span>
        </a>

        <p class="text-center text-sm text-slate-600">Already have an account? <a class="font-semibold text-brand-700 hover:text-brand-900" href="{{ route('login') }}">Log in</a></p>
    </div>
</x-guest-layout>
