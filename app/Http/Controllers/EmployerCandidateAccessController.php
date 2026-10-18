<?php

namespace App\Http\Controllers;

use App\Models\CandidateProfile;
use App\Services\CandidateContactAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployerCandidateAccessController extends Controller
{
    public function unlock(Request $request, CandidateProfile $candidate, CandidateContactAccessService $access): RedirectResponse
    {
        try {
            $access->unlock($request->user(), $candidate);
        } catch (ValidationException $exception) {
            return redirect()->route('employer.packages.index')
                ->with('status', $exception->errors()['candidate'][0] ?? 'An active candidate-access package is required.');
        }

        return redirect()->route('employer.candidate-access.show', $candidate);
    }

    public function show(Request $request, CandidateProfile $candidate, CandidateContactAccessService $access): Response
    {
        return response()->view('employer.candidate-access.show', [
            'contact' => $access->contactCard($request->user(), $candidate),
            'candidate' => $candidate,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function resume(Request $request, CandidateProfile $candidate, CandidateContactAccessService $access): StreamedResponse
    {
        $candidate = $access->assertResumeDownloadAllowed($request->user(), $candidate);
        $disk = Storage::disk((string) config('candidate.private_disk'));
        abort_unless($disk->exists((string) $candidate->resume_path), 404);

        return $disk->download(
            (string) $candidate->resume_path,
            basename((string) ($candidate->resume_original_name ?: 'resume.pdf')),
            ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'],
        );
    }
}
