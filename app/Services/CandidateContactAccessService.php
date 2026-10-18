<?php

namespace App\Services;

use App\Models\CandidateProfile;
use App\Models\CandidateProfileAccess;
use App\Models\PackageEntitlement;
use App\Models\User;
use App\PackageType;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CandidateContactAccessService
{
    public function unlock(User $employer, CandidateProfile $candidate): CandidateProfileAccess
    {
        $this->assertEmployer($employer);
        $this->assertCandidateOptedIn($candidate);

        return DB::transaction(function () use ($employer, $candidate): CandidateProfileAccess {
            $lockedCandidate = CandidateProfile::query()->whereKey($candidate->getKey())->lockForUpdate()->firstOrFail();
            $this->assertCandidateOptedIn($lockedCandidate);

            $existing = CandidateProfileAccess::query()
                ->where('employer_user_id', $employer->getKey())
                ->where('candidate_profile_id', $lockedCandidate->getKey())
                ->lockForUpdate()
                ->first();

            if ($existing instanceof CandidateProfileAccess && $existing->isActive()) {
                return $existing;
            }

            $entitlement = PackageEntitlement::query()
                ->where('user_id', $employer->getKey())
                ->where('type', PackageType::CandidateAccess)
                ->where('credits_remaining', '>', 0)
                ->where(fn ($query) => $query->whereNull('valid_until')->orWhere('valid_until', '>', now()))
                ->orderBy('valid_until')
                ->orderBy('created_at')
                ->lockForUpdate()
                ->first();

            if (! $entitlement instanceof PackageEntitlement) {
                throw ValidationException::withMessages([
                    'candidate' => 'Your candidate-contact package is expired or out of credits. Browse packages to continue.',
                ]);
            }

            $entitlement->decrement('credits_remaining');
            $attributes = [
                'package_entitlement_id' => $entitlement->getKey(),
                'granted_at' => now(),
                'valid_until' => $entitlement->valid_until,
            ];

            if ($existing instanceof CandidateProfileAccess) {
                $existing->update($attributes);

                return $existing->fresh() ?? $existing;
            }

            return CandidateProfileAccess::query()->create([
                ...$attributes,
                'employer_user_id' => $employer->getKey(),
                'candidate_profile_id' => $lockedCandidate->getKey(),
            ]);
        });
    }

    public function authorizedProfile(User $employer, CandidateProfile $candidate): CandidateProfile
    {
        $this->assertEmployer($employer);
        $this->assertCandidateOptedIn($candidate);

        $access = CandidateProfileAccess::query()
            ->where('employer_user_id', $employer->getKey())
            ->where('candidate_profile_id', $candidate->getKey())
            ->first();

        if (! $access instanceof CandidateProfileAccess || ! $access->isActive()) {
            throw new AuthorizationException('An active candidate-contact entitlement is required.');
        }

        return $candidate->loadMissing('user');
    }

    /** @return array{name: string, headline: string|null, location: string|null, email: string|null, phone: string|null, resume_title: string|null, resume_available: bool, valid_until: string|null} */
    public function contactCard(User $employer, CandidateProfile $candidate): array
    {
        $candidate = $this->authorizedProfile($employer, $candidate);
        $access = CandidateProfileAccess::query()
            ->where('employer_user_id', $employer->getKey())
            ->where('candidate_profile_id', $candidate->getKey())
            ->firstOrFail();
        $shareContact = (bool) $candidate->share_contact_details;
        $shareResume = (bool) $candidate->share_resume_with_employers && $candidate->resume_path !== null;

        return [
            'name' => $candidate->user->name,
            'headline' => $candidate->headline,
            'location' => collect([$candidate->city, $candidate->state])->filter()->join(', ') ?: null,
            'email' => $shareContact ? $candidate->user->email : null,
            'phone' => $shareContact ? $candidate->phone : null,
            'resume_title' => $shareResume ? ($candidate->resume_title ?: 'Candidate résumé') : null,
            'resume_available' => $shareResume,
            'valid_until' => $access->valid_until?->toIso8601String(),
        ];
    }

    public function assertResumeDownloadAllowed(User $employer, CandidateProfile $candidate): CandidateProfile
    {
        $candidate = $this->authorizedProfile($employer, $candidate);
        abort_unless($candidate->share_resume_with_employers && $candidate->resume_path !== null, 404);

        return $candidate;
    }

    private function assertEmployer(User $employer): void
    {
        if (! $employer->isEmployer() || $employer->isSuspended() || ! $employer->companies()->where('is_visible', true)->exists()) {
            throw new AuthorizationException('An active employer with an approved company profile is required.');
        }
    }

    private function assertCandidateOptedIn(CandidateProfile $candidate): void
    {
        $candidate->loadMissing('user');
        abort_unless(
            $candidate->is_public
                && $candidate->user?->isCandidate()
                && ! $candidate->user->isSuspended()
                && ($candidate->share_contact_details || $candidate->share_resume_with_employers),
            404,
        );
    }
}
