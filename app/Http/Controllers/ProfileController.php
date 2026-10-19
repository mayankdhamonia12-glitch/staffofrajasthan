<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $userId = (int) $request->user()->getKey();
        $filesToDelete = DB::transaction(function () use ($userId): array {
            $user = User::query()->lockForUpdate()->findOrFail($userId);
            $blockReason = $this->accountDeletionBlockReason($user);
            if ($blockReason !== null) {
                throw ValidationException::withMessages(['account' => $blockReason])->errorBag('userDeletion');
            }

            $files = [];
            $candidateProfile = $user->candidateProfile()->first();
            if ($candidateProfile) {
                $disk = (string) config('candidate.private_disk');
                foreach ([$candidateProfile->resume_path, $candidateProfile->profile_photo_path] as $path) {
                    if (is_string($path) && $path !== '') {
                        $files[] = [$disk, $path];
                    }
                }
            }
            if (is_string($user->blog_profile_image_path) && $user->blog_profile_image_path !== '') {
                $files[] = ['public', $user->blog_profile_image_path];
            }

            // Delete the database row before removing files. Referential
            // integrity failures therefore leave the account's files intact.
            $user->delete();

            return $files;
        });

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        foreach ($filesToDelete as [$disk, $path]) {
            if (! Storage::disk($disk)->delete($path)) {
                Log::warning('Account deletion could not remove an associated file.', ['user_id' => $userId, 'disk' => $disk]);
            }
        }

        return Redirect::to('/');
    }

    private function accountDeletionBlockReason(User $user): ?string
    {
        if ($user->isOwner()) {
            return 'Owner accounts cannot be deleted through self-service. Ownership transfer is not yet available.';
        }

        if ($user->isCandidate()) {
            $profileHasSharedRecords = $user->candidateProfile()
                ->where(fn ($profiles) => $profiles->whereHas('shortlists')->orWhereHas('accesses'))
                ->exists();

            if ($user->jobApplications()->exists()
                || $user->companyReviews()->exists()
                || $user->candidateConversations()->exists()
                || $user->candidateMeetings()->exists()
                || $profileHasSharedRecords) {
                return 'This account has recruitment records shared with employers. Contact support to request deletion while preserving that history.';
            }
        }

        if ($user->isEmployer() && (
            $user->companies()->exists()
            || $user->employerConversations()->exists()
            || $user->employerMeetings()->exists()
        )) {
            return 'This account has company or recruitment records used by candidates. Contact support to request deletion while preserving that history.';
        }

        return null;
    }
}
