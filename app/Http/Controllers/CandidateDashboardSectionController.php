<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class CandidateDashboardSectionController extends Controller
{
    /** @var array<string, array{title: string, description: string, eyebrow: string, action: string|null, actionLabel: string|null}> */
    private const SECTIONS = [
        'meetings' => ['title' => 'Meetings', 'description' => 'Interview scheduling is not available yet. Your meeting list will appear here when employers can schedule interviews through the platform.', 'eyebrow' => 'Interview schedule', 'action' => null, 'actionLabel' => null],
        'messages' => ['title' => 'Messages', 'description' => 'Private messaging is not available yet. No conversations are stored or displayed on this page.', 'eyebrow' => 'Conversations', 'action' => null, 'actionLabel' => null],
        'reviews' => ['title' => 'Reviews', 'description' => 'Candidate reviews are not available yet. Reviews will be shown here only after a real review workflow is introduced.', 'eyebrow' => 'Your reputation', 'action' => null, 'actionLabel' => null],
    ];

    public function __invoke(Request $request, string $section): View
    {
        abort_unless(isset(self::SECTIONS[$section]), 404);
        $page = self::SECTIONS[$section];

        return view('candidate.dashboard.section', compact('section', 'page'));
    }
}
