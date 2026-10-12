<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class CandidateDashboardSectionController extends Controller
{
    /** @var array<string, array{title: string, description: string, eyebrow: string, action: string|null, actionLabel: string|null}> */
    private const SECTIONS = [
        'alerts' => ['title' => 'Job alerts', 'description' => 'Saved search alerts are not available yet. Browse current roles whenever you are ready; alert preferences will be added with the job-alert milestone.', 'eyebrow' => 'Coming with job alerts', 'action' => 'jobs.index', 'actionLabel' => 'Browse jobs'],
        'following' => ['title' => 'Following employers', 'description' => 'Employer following has not been implemented yet. This page will list companies you choose to follow once that feature is available.', 'eyebrow' => 'Employer discovery', 'action' => 'companies.index', 'actionLabel' => 'Explore companies'],
        'notifications' => ['title' => 'Notifications', 'description' => 'There are no candidate notifications yet. Notifications will appear here once application and account events are connected to the notification system.', 'eyebrow' => 'Stay up to date', 'action' => null, 'actionLabel' => null],
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
