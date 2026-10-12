<?php

namespace App\Http\Controllers;

use App\Services\CandidateProfileViewsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CandidateProfileViewsController extends Controller
{
    public function index(Request $request, CandidateProfileViewsService $views): View
    {
        $profile = $request->user()->candidateProfile()->firstOrCreate();

        return view('candidate.profile-views.index', [
            'summary' => $views->summary($profile, 30),
            'candidateProfile' => $profile,
        ]);
    }
}
