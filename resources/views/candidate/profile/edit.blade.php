<x-app-layout>
    <x-slot name="header"><h1 class="text-xl font-bold text-slate-900">My candidate profile</h1></x-slot>

    <div class="mx-auto max-w-4xl space-y-6 px-4 py-8 sm:px-6">
        @if (session('status')) <div class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div> @endif

        <form method="POST" action="{{ route('candidate.profile.update') }}" class="rounded-xl bg-white p-6 shadow-sm">
            @csrf @method('PATCH')
            <h2 class="text-lg font-semibold">Professional details</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                @foreach (['headline' => 'Professional headline', 'phone' => 'Phone', 'location' => 'Location', 'linkedin_url' => 'LinkedIn URL', 'portfolio_url' => 'Portfolio URL'] as $field => $label)
                    <label class="block text-sm font-medium text-slate-700">{{ $label }}<input name="{{ $field }}" value="{{ old($field, $profile->$field) }}" class="mt-1 w-full rounded-lg border-slate-300" /></label>
                @endforeach
            </div>
            <label class="mt-4 block text-sm font-medium text-slate-700">About<textarea name="about" rows="5" class="mt-1 w-full rounded-lg border-slate-300">{{ old('about', $profile->about) }}</textarea></label>
            <button class="mt-4 rounded-lg bg-orange-700 px-4 py-2 text-sm font-semibold text-white">Save profile</button>
        </form>

        <form method="POST" enctype="multipart/form-data" action="{{ route('candidate.resume.store') }}" class="rounded-xl bg-white p-6 shadow-sm">
            @csrf <h2 class="text-lg font-semibold">Resume</h2>
            <p class="mt-1 text-sm text-slate-600">PDF, DOC, or DOCX up to 5 MB. Your resume is stored privately.</p>
            <input class="mt-3 block text-sm" type="file" name="resume" accept=".pdf,.doc,.docx" required />
            <button class="mt-3 rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold">Upload resume</button>
        </form>

        <section class="rounded-xl bg-white p-6 shadow-sm"><h2 class="text-lg font-semibold">Skills</h2>
            <form method="POST" action="{{ route('candidate.skills.store') }}" class="mt-3 flex gap-2">@csrf <input name="name" class="w-full rounded-lg border-slate-300" placeholder="e.g. Laravel" required /><button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Add</button></form>
            <div class="mt-4 flex flex-wrap gap-2">@foreach ($profile->skills as $skill)<form method="POST" action="{{ route('candidate.skills.destroy', $skill) }}">@csrf @method('DELETE')<button class="rounded-full bg-orange-50 px-3 py-1 text-sm text-orange-800">{{ $skill->name }} ×</button></form>@endforeach</div>
        </section>
    </div>
</x-app-layout>
