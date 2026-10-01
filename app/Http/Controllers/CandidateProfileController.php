<?php

namespace App\Http\Controllers;

use App\Models\CandidateProfile;
use App\Models\JobCategory;
use App\Models\Skill;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidateProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $profile = $this->profile($request)->load(['skills', 'educations', 'experiences', 'categories', 'languages', 'portfolios', 'awards']);
        $availableSkills = Skill::query()->orderBy('name')->limit(100)->get(['id', 'name']);
        $categories = JobCategory::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('candidate.profile.edit', compact('profile', 'availableSkills', 'categories'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'headline' => ['nullable', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:30'],
            'location' => ['nullable', 'string', 'max:160'],
            'about' => ['nullable', 'string', 'max:3000'],
            'linkedin_url' => ['nullable', 'url', 'regex:/^https:\/\//i', 'max:255'],
            'portfolio_url' => ['nullable', 'url', 'regex:/^https:\/\//i', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::in(['woman', 'man', 'non_binary', 'self_described', 'prefer_not_to_say'])],
            'experience_level' => ['nullable', Rule::in(['entry', 'junior', 'mid', 'senior', 'lead', 'executive'])],
            'years_experience' => ['nullable', 'integer', 'between:0,60'],
            'months_experience' => ['nullable', 'integer', 'between:0,11'],
            'expected_salary' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'salary_type' => ['nullable', Rule::in(['yearly', 'monthly', 'hourly'])],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'facebook_url' => ['nullable', 'url', 'regex:/^https:\/\//i', 'max:255'],
            'twitter_url' => ['nullable', 'url', 'regex:/^https:\/\//i', 'max:255'],
            'website_url' => ['nullable', 'url', 'regex:/^https:\/\//i', 'max:255'],
            'video_provider' => ['nullable', Rule::in(['youtube', 'vimeo'])],
            'intro_video_url' => ['nullable', 'url', 'max:2048'],
            'intro_video_title' => ['nullable', 'string', 'max:160'],
            'availability_status' => ['sometimes', Rule::in(['open_to_work', 'passively_looking', 'not_looking'])],
            'is_public' => ['nullable', 'boolean'],
            'show_expected_salary' => ['nullable', 'boolean'],
            'categories' => ['nullable', 'array', 'max:10'],
            'categories.*' => ['integer', 'distinct', 'exists:job_categories,id,is_active,1'],
            'preferred_employment_types' => ['nullable', 'array', 'max:5'],
            'preferred_employment_types.*' => [Rule::in(['full_time', 'part_time', 'contract', 'temporary', 'internship'])],
        ]);

        $this->validateIntroductionVideo($data['video_provider'] ?? null, $data['intro_video_url'] ?? null);

        $profile = $this->profile($request);
        $profileFields = array_diff_key($data, array_flip(['name', 'categories', 'is_public', 'show_expected_salary']));
        $profileFields['is_public'] = (bool) ($data['is_public'] ?? $profile->is_public);
        $profileFields['show_expected_salary'] = (bool) ($data['show_expected_salary'] ?? $profile->show_expected_salary);
        $profileFields['availability_status'] ??= $profile->availability_status ?: 'open_to_work';
        if (array_key_exists('city', $data) || array_key_exists('state', $data)) {
            $profileFields['location'] = implode(', ', array_filter([$data['city'] ?? '', $data['state'] ?? ''])) ?: ($data['location'] ?? null);
        }
        if ($profileFields['is_public'] && ! $profile->slug) {
            $profileFields['slug'] = Str::slug(($data['name'] ?? $request->user()->name).'-'.$profile->getKey());
        }

        if (isset($data['name'])) {
            $request->user()->update(['name' => $data['name']]);
        }
        $profile->update($profileFields);
        if ($request->has('categories_present')) {
            $profile->categories()->sync($data['categories'] ?? []);
        }

        return back()->with('status', 'Profile updated.');
    }

    public function uploadResume(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'resume' => ['required', 'file', 'mimes:pdf,doc,docx', 'extensions:pdf,doc,docx', 'max:5120'],
            'resume_title' => ['nullable', 'string', 'max:160'],
        ]);
        $profile = $this->profile($request);
        $file = $request->file('resume');
        abort_if($file === null, 422);

        $extension = strtolower($file->getClientOriginalExtension());
        $disk = (string) config('candidate.private_disk');
        $directory = trim((string) config('candidate.private_directory'), '/').'/resumes/'.$request->user()->getKey();
        $storedPath = $file->storeAs($directory, Str::uuid().'.'.$extension, $disk);
        abort_if($storedPath === false, 500, 'Resume could not be stored.');

        $originalName = basename(str_replace('\\', '/', $file->getClientOriginalName()));
        $originalName = preg_replace('/[\x00-\x1F\x7F]+/u', '', $originalName) ?: 'resume.'.$extension;
        $originalName = Str::limit($originalName, 240, '');
        $oldPath = $profile->resume_path;

        $profile->update([
            'resume_path' => $storedPath,
            'resume_title' => $data['resume_title'] ?: pathinfo($originalName, PATHINFO_FILENAME),
            'resume_original_name' => $originalName,
            'resume_mime_type' => $file->getMimeType(),
            'resume_size' => $file->getSize(),
            'resume_uploaded_at' => now(),
        ]);
        if ($oldPath) {
            Storage::disk($disk)->delete($oldPath);
        }

        return back()->with('status', 'Resume uploaded and stored privately.');
    }

    public function downloadResume(Request $request): StreamedResponse
    {
        $profile = $this->profile($request);
        abort_unless($profile->resume_path !== null, 404);
        $disk = Storage::disk((string) config('candidate.private_disk'));
        abort_unless($disk->exists($profile->resume_path), 404);

        return $disk->download($profile->resume_path, $profile->resume_original_name ?: 'resume');
    }

    public function deleteResume(Request $request): RedirectResponse
    {
        $profile = $this->profile($request);
        if ($profile->resume_path) {
            Storage::disk((string) config('candidate.private_disk'))->delete($profile->resume_path);
        }
        $profile->update([
            'resume_path' => null,
            'resume_title' => null,
            'resume_original_name' => null,
            'resume_mime_type' => null,
            'resume_size' => null,
            'resume_uploaded_at' => null,
        ]);

        return back()->with('status', 'Resume removed.');
    }

    public function uploadPhoto(Request $request): RedirectResponse
    {
        $request->validate(['photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=64,min_height=64,max_width=3000,max_height=3000']]);
        $profile = $this->profile($request);
        $file = $request->file('photo');
        abort_if($file === null, 422);

        $diskName = (string) config('candidate.private_disk');
        $directory = trim((string) config('candidate.private_directory'), '/').'/photos/'.$request->user()->getKey();
        $path = $file->storeAs($directory, Str::uuid().'.'.$file->guessExtension(), $diskName);
        abort_if($path === false, 500, 'Profile photo could not be stored.');
        $oldPath = $profile->profile_photo_path;
        $profile->update(['profile_photo_path' => $path]);
        if ($oldPath) {
            Storage::disk($diskName)->delete($oldPath);
        }

        return back()->with('status', 'Profile photo updated.');
    }

    public function photo(Request $request): StreamedResponse
    {
        $profile = $this->profile($request);
        abort_unless($profile->profile_photo_path !== null, 404);
        $disk = Storage::disk((string) config('candidate.private_disk'));
        abort_unless($disk->exists($profile->profile_photo_path), 404);

        return $disk->response($profile->profile_photo_path, null, ['Cache-Control' => 'private, no-store']);
    }

    public function deletePhoto(Request $request): RedirectResponse
    {
        $profile = $this->profile($request);
        if ($profile->profile_photo_path) {
            Storage::disk((string) config('candidate.private_disk'))->delete($profile->profile_photo_path);
        }
        $profile->update(['profile_photo_path' => null]);

        return back()->with('status', 'Profile photo removed.');
    }

    public function addSkill(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'proficiency' => ['nullable', Rule::in(['beginner', 'intermediate', 'advanced', 'expert'])],
        ]);
        $data['proficiency'] ??= 'intermediate';
        $name = trim(preg_replace('/\s+/', ' ', $data['name']) ?? $data['name']);
        $skill = Skill::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first()
            ?? Skill::query()->firstOrCreate(['name' => $name]);
        $profile = $this->profile($request);
        if ($profile->skills()->whereKey($skill->getKey())->exists()) {
            $profile->skills()->updateExistingPivot($skill->getKey(), ['proficiency' => $data['proficiency']]);
        } else {
            $profile->skills()->attach($skill->getKey(), ['proficiency' => $data['proficiency']]);
        }

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

    private function validateIntroductionVideo(?string $provider, ?string $url): void
    {
        if (! $url && ! $provider) {
            return;
        }

        $host = strtolower((string) parse_url((string) $url, PHP_URL_HOST));
        $allowed = [
            'youtube' => ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be'],
            'vimeo' => ['vimeo.com', 'www.vimeo.com', 'player.vimeo.com'],
        ];
        if (! $provider || ! $url || parse_url($url, PHP_URL_SCHEME) !== 'https' || ! in_array($host, $allowed[$provider] ?? [], true)) {
            throw ValidationException::withMessages(['intro_video_url' => 'Use a secure YouTube or Vimeo URL that matches the selected provider.']);
        }
    }
}
