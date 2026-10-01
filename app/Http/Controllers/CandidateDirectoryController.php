<?php

namespace App\Http\Controllers;

use App\Models\CandidateProfile;
use App\Models\JobCategory;
use App\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CandidateDirectoryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'experience_level' => ['nullable', 'in:entry,junior,mid,senior,lead,executive'],
            'qualification' => ['nullable', 'string', 'max:150'],
            'language' => ['nullable', 'string', 'max:80'],
            'sort' => ['nullable', 'in:recent,name'],
        ]);

        $query = CandidateProfile::query()
            ->where('is_public', true)
            ->whereHas('user', fn (Builder $users) => $users->where('role', UserRole::Candidate))
            ->with(['user', 'skills', 'categories', 'educations', 'experiences', 'languages', 'portfolios', 'awards']);

        if ($keyword = trim((string) ($filters['keyword'] ?? ''))) {
            $query->where(function (Builder $profiles) use ($keyword): void {
                $profiles->where('headline', 'like', '%'.$keyword.'%')
                    ->orWhere('about', 'like', '%'.$keyword.'%')
                    ->orWhereHas('user', fn (Builder $users) => $users->where('name', 'like', '%'.$keyword.'%'))
                    ->orWhereHas('skills', fn (Builder $skills) => $skills->where('name', 'like', '%'.$keyword.'%'));
            });
        }

        if ($location = trim((string) ($filters['location'] ?? ''))) {
            $query->where(fn (Builder $profiles) => $profiles->where('city', 'like', '%'.$location.'%')->orWhere('state', 'like', '%'.$location.'%'));
        }
        if (! empty($filters['category'])) {
            $query->whereHas('categories', fn (Builder $categories) => $categories->where('slug', $filters['category']));
        }
        if (! empty($filters['experience_level'])) {
            $query->where('experience_level', $filters['experience_level']);
        }
        if (! empty($filters['qualification'])) {
            $query->whereHas('educations', fn (Builder $education) => $education->where('qualification', 'like', '%'.$filters['qualification'].'%'));
        }
        if (! empty($filters['language'])) {
            $query->whereHas('languages', fn (Builder $languages) => $languages->where('language', 'like', '%'.$filters['language'].'%'));
        }

        if (($filters['sort'] ?? 'recent') === 'name') {
            $query->join('users as candidate_users', 'candidate_users.id', '=', 'candidate_profiles.user_id')
                ->select('candidate_profiles.*')->orderBy('candidate_users.name');
        } else {
            $query->latest('candidate_profiles.updated_at');
        }

        return view('public.candidates', [
            'profiles' => $query->paginate(12)->withQueryString(),
            'filters' => $filters,
            'categories' => JobCategory::query()->where('is_active', true)->whereHas('candidateProfiles', fn (Builder $profiles) => $profiles->where('is_public', true))->orderBy('name')->get(),
        ]);
    }

    public function show(CandidateProfile $candidate): View
    {
        abort_unless($candidate->is_public && $candidate->user?->role === UserRole::Candidate, 404);
        $candidate->load(['user', 'skills', 'categories', 'educations', 'experiences', 'languages', 'portfolios', 'awards']);

        return view('public.candidate-profile', ['candidate' => $candidate]);
    }

    public function photo(CandidateProfile $candidate): StreamedResponse
    {
        abort_unless($candidate->is_public && $candidate->user?->role === UserRole::Candidate && $candidate->profile_photo_path, 404);
        $disk = Storage::disk((string) config('candidate.private_disk'));
        abort_unless($disk->exists($candidate->profile_photo_path), 404);

        return $disk->response($candidate->profile_photo_path, null, ['Cache-Control' => 'public, max-age=600']);
    }
}
