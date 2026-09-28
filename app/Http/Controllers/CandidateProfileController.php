<?php

namespace App\Http\Controllers;

use App\Models\CandidateProfile;
use App\Models\Skill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CandidateProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $profile = $this->profile($request)->load(['skills', 'educations', 'experiences']);

        return view('candidate.profile.edit', compact('profile'));
    }

    public function update(Request $request): RedirectResponse
    {
        $profile = $this->profile($request);
        $profile->update($request->validate([
            'headline' => ['nullable', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:30'],
            'location' => ['nullable', 'string', 'max:160'],
            'about' => ['nullable', 'string', 'max:3000'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'portfolio_url' => ['nullable', 'url', 'max:255'],
        ]));

        return back()->with('status', 'Profile updated.');
    }

    public function uploadResume(Request $request): RedirectResponse
    {
        $request->validate(['resume' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120']]);
        $profile = $this->profile($request);

        if ($profile->resume_path) {
            Storage::disk('local')->delete($profile->resume_path);
        }

        $profile->update(['resume_path' => $request->file('resume')->store("resumes/{$request->user()->id}", 'local')]);

        return back()->with('status', 'Resume uploaded.');
    }

    public function addSkill(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80']]);
        $skill = Skill::firstOrCreate(['name' => trim($data['name'])]);
        $this->profile($request)->skills()->syncWithoutDetaching([$skill->id]);

        return back()->with('status', 'Skill added.');
    }

    public function removeSkill(Request $request, Skill $skill): RedirectResponse
    {
        $this->profile($request)->skills()->detach($skill);

        return back()->with('status', 'Skill removed.');
    }

    private function profile(Request $request): CandidateProfile
    {
        return $request->user()->candidateProfile()->firstOrCreate();
    }
}
