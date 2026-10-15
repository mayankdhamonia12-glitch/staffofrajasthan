<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use App\Notifications\ApplicationStatusChangedNotification;
use App\Notifications\JobOpportunityNotification;
use App\Notifications\NewJobApplicationNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardNotificationController extends Controller
{
    public function candidateIndex(Request $request): View
    {
        $user = $this->user($request, 'candidate');

        return $this->inbox($user, true);
    }

    public function employerIndex(Request $request): View
    {
        $user = $this->user($request, 'employer');

        return $this->inbox($user, false);
    }

    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && ($user->isCandidate() || $user->isEmployer()), 403);

        $record = $user->notifications()->whereKey($notification)->firstOrFail();
        $record->markAsRead();

        return back()->with('status', 'Notification marked as read.');
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && ($user->isCandidate() || $user->isEmployer()), 403);
        $user->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('status', 'All notifications marked as read.');
    }

    private function inbox(User $user, bool $candidate): View
    {
        $notifications = $user->notifications()->latest()->paginate(15);
        $targets = [];

        foreach ($notifications as $notification) {
            $data = $notification->data;
            $target = null;

            if ($candidate && $notification->type === JobOpportunityNotification::class && isset($data['job_id'])) {
                $job = Job::query()->publiclyAvailable()->whereKey((int) $data['job_id'])->first();
                if ($job instanceof Job) {
                    $target = route('jobs.show', $job);
                }
            } elseif ($candidate && $notification->type === ApplicationStatusChangedNotification::class) {
                $target = route('candidate.applied-jobs.index');
            } elseif (! $candidate && $notification->type === NewJobApplicationNotification::class && isset($data['application_id'])) {
                $application = JobApplication::query()
                    ->whereHas('job', fn (Builder $jobs) => $jobs->ownedByEmployer($user))
                    ->whereKey((int) $data['application_id'])
                    ->first();
                if ($application instanceof JobApplication) {
                    $target = route('employer.applications.show', $application);
                }
            }

            if ($target !== null) {
                $targets[$notification->getKey()] = $target;
            }
        }

        return view('dashboard.notifications.index', [
            'notifications' => $notifications,
            'targets' => $targets,
            'candidate' => $candidate,
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }

    private function user(Request $request, string $role): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && ($role === 'candidate' ? $user->isCandidate() : $user->isEmployer()), 403);

        return $user;
    }
}
