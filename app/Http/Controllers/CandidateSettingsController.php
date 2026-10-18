<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CandidateSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $profile = $user->candidateProfile()->firstOrCreate();

        return view('candidate.settings.edit', compact('user', 'profile'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'is_public' => ['required', 'boolean'],
            'show_expected_salary' => ['required', 'boolean'],
            'share_contact_details' => ['sometimes', 'boolean'],
            'share_resume_with_employers' => ['sometimes', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $data): void {
            $request->user()->update(['name' => $data['name']]);
            $profile = $request->user()->candidateProfile()->firstOrCreate();
            $profile->update([
                'is_public' => (bool) $data['is_public'],
                'show_expected_salary' => (bool) $data['show_expected_salary'],
                'share_contact_details' => (bool) ($data['share_contact_details'] ?? $profile->share_contact_details),
                'share_resume_with_employers' => (bool) ($data['share_resume_with_employers'] ?? $profile->share_resume_with_employers),
            ]);
        });

        return back()->with('status', 'Account settings saved.');
    }
}
