<?php

namespace App\Http\Controllers;

use App\Models\CandidateAward;
use App\Models\CandidateLanguage;
use App\Models\CandidatePortfolio;
use App\Models\Education;
use App\Models\Experience;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Illuminate\Validation\ValidationException;

class CandidateProfileResourceController extends Controller
{
    public function storeEducation(Request $request): RedirectResponse
    {
        $data = $request->validate($this->educationRules());
        $data['currently_studying'] = (bool) ($data['currently_studying'] ?? false);
        if ($data['currently_studying']) {
            $data['end_date'] = null;
        }
        $request->user()->candidateProfile()->firstOrCreate()->educations()->create($data);

        return back()->with('status', 'Education added.');
    }

    public function updateEducation(Request $request, Education $education): RedirectResponse
    {
        $record = $request->user()->candidateProfile()->firstOrFail()->educations()->whereKey($education->getKey())->firstOrFail();
        $data = $request->validate($this->educationRules());
        $data['currently_studying'] = (bool) ($data['currently_studying'] ?? false);
        if ($data['currently_studying']) {
            $data['end_date'] = null;
        }
        $record->update($data);

        return back()->with('status', 'Education updated.');
    }

    public function destroyEducation(Request $request, Education $education): RedirectResponse
    {
        $request->user()->candidateProfile()->firstOrFail()->educations()->whereKey($education->getKey())->firstOrFail()->delete();

        return back()->with('status', 'Education removed.');
    }

    public function storeExperience(Request $request): RedirectResponse
    {
        $data = $request->validate($this->experienceRules());
        $data['is_current'] = (bool) ($data['is_current'] ?? false);
        if ($data['is_current']) {
            $data['end_date'] = null;
        }
        $request->user()->candidateProfile()->firstOrCreate()->experiences()->create($data);

        return back()->with('status', 'Experience added.');
    }

    public function updateExperience(Request $request, Experience $experience): RedirectResponse
    {
        $record = $request->user()->candidateProfile()->firstOrFail()->experiences()->whereKey($experience->getKey())->firstOrFail();
        $data = $request->validate($this->experienceRules());
        $data['is_current'] = (bool) ($data['is_current'] ?? false);
        if ($data['is_current']) {
            $data['end_date'] = null;
        }
        $record->update($data);

        return back()->with('status', 'Experience updated.');
    }

    public function destroyExperience(Request $request, Experience $experience): RedirectResponse
    {
        $request->user()->candidateProfile()->firstOrFail()->experiences()->whereKey($experience->getKey())->firstOrFail()->delete();

        return back()->with('status', 'Experience removed.');
    }

    public function storeLanguage(Request $request): RedirectResponse
    {
        $data = $request->validate($this->languageRules());
        $profile = $request->user()->candidateProfile()->firstOrCreate();
        $language = Str::title(trim(preg_replace('/\s+/', ' ', $data['language']) ?? $data['language']));
        $this->ensureLanguageIsUnique($profile->getKey(), $language);
        $profile->languages()->create(['language' => $language, 'proficiency' => $data['proficiency']]);

        return back()->with('status', 'Language added.');
    }

    public function updateLanguage(Request $request, CandidateLanguage $language): RedirectResponse
    {
        $record = $request->user()->candidateProfile()->firstOrFail()->languages()->whereKey($language->getKey())->firstOrFail();
        $data = $request->validate($this->languageRules());
        $name = Str::title(trim(preg_replace('/\s+/', ' ', $data['language']) ?? $data['language']));
        $this->ensureLanguageIsUnique($record->candidate_profile_id, $name, $record->getKey());
        $record->update(['language' => $name, 'proficiency' => $data['proficiency']]);

        return back()->with('status', 'Language updated.');
    }

    public function destroyLanguage(Request $request, CandidateLanguage $language): RedirectResponse
    {
        $request->user()->candidateProfile()->firstOrFail()->languages()->whereKey($language->getKey())->firstOrFail()->delete();

        return back()->with('status', 'Language removed.');
    }

    public function storePortfolio(Request $request): RedirectResponse
    {
        $data = $request->validate($this->portfolioRules());
        $request->user()->candidateProfile()->firstOrCreate()->portfolios()->create($data);

        return back()->with('status', 'Portfolio item added.');
    }

    public function updatePortfolio(Request $request, CandidatePortfolio $portfolio): RedirectResponse
    {
        $record = $request->user()->candidateProfile()->firstOrFail()->portfolios()->whereKey($portfolio->getKey())->firstOrFail();
        $record->update($request->validate($this->portfolioRules()));

        return back()->with('status', 'Portfolio item updated.');
    }

    public function destroyPortfolio(Request $request, CandidatePortfolio $portfolio): RedirectResponse
    {
        $request->user()->candidateProfile()->firstOrFail()->portfolios()->whereKey($portfolio->getKey())->firstOrFail()->delete();

        return back()->with('status', 'Portfolio item removed.');
    }

    public function storeAward(Request $request): RedirectResponse
    {
        $request->user()->candidateProfile()->firstOrCreate()->awards()->create($request->validate($this->awardRules()));

        return back()->with('status', 'Award added.');
    }

    public function updateAward(Request $request, CandidateAward $award): RedirectResponse
    {
        $record = $request->user()->candidateProfile()->firstOrFail()->awards()->whereKey($award->getKey())->firstOrFail();
        $record->update($request->validate($this->awardRules()));

        return back()->with('status', 'Award updated.');
    }

    public function destroyAward(Request $request, CandidateAward $award): RedirectResponse
    {
        $request->user()->candidateProfile()->firstOrFail()->awards()->whereKey($award->getKey())->firstOrFail()->delete();

        return back()->with('status', 'Award removed.');
    }

    /** @return array<string, array<int, string|In>> */
    private function educationRules(): array
    {
        return [
            'institution' => ['required', 'string', 'max:180'],
            'qualification' => ['required', 'string', 'max:150'],
            'field_of_study' => ['nullable', 'string', 'max:150'],
            'start_date' => ['nullable', 'date', 'before_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'currently_studying' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:3000'],
        ];
    }

    /** @return array<string, array<int, string|In>> */
    private function experienceRules(): array
    {
        return [
            'company' => ['required', 'string', 'max:180'],
            'title' => ['required', 'string', 'max:160'],
            'location' => ['nullable', 'string', 'max:160'],
            'start_date' => ['required', 'date', 'before_or_equal:today'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_current' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:3000'],
        ];
    }

    /** @return array<string, array<int, string|In>> */
    private function languageRules(): array
    {
        return [
            'language' => ['required', 'string', 'max:80'],
            'proficiency' => ['required', Rule::in(['basic', 'intermediate', 'fluent', 'native'])],
        ];
    }

    /** @return array<string, array<int, string|In>> */
    private function portfolioRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'url' => ['nullable', 'url', 'regex:/^https:\/\//i', 'max:2048'],
            'completed_at' => ['nullable', 'date', 'before_or_equal:today'],
        ];
    }

    /** @return array<string, array<int, string|In>> */
    private function awardRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'issuer' => ['nullable', 'string', 'max:160'],
            'awarded_at' => ['nullable', 'date', 'before_or_equal:today'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    private function ensureLanguageIsUnique(int $profileId, string $language, ?int $ignoreId = null): void
    {
        $query = CandidateLanguage::query()->where('candidate_profile_id', $profileId)->whereRaw('LOWER(language) = ?', [mb_strtolower($language)]);
        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }
        if ($query->exists()) {
            throw ValidationException::withMessages(['language' => 'This language is already in your profile.']);
        }
    }
}
