<?php

namespace App\Http\Controllers;

use App\MeetingStatus;
use App\MeetingType;
use App\Models\JobApplication;
use App\Models\Meeting;
use App\Models\MeetingEvent;
use App\Models\User;
use App\Notifications\MeetingChangedNotification;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MeetingController extends Controller
{
    private const TIMEZONE = 'Asia/Kolkata';

    public function index(Request $request): View
    {
        $user = $this->participant($request);
        $base = $this->participantMeetings($user)->with(['application.job.company', 'candidate', 'employer']);
        $upcoming = (clone $base)
            ->where('status', MeetingStatus::Scheduled->value)
            ->where('starts_at', '>=', now())
            ->orderBy('starts_at')
            ->paginate(10, ['*'], 'upcoming_page')
            ->withQueryString();
        $past = (clone $base)
            ->where(fn (Builder $query) => $query->where('starts_at', '<', now())->orWhere('status', '!=', MeetingStatus::Scheduled->value))
            ->orderByDesc('starts_at')
            ->paginate(10, ['*'], 'history_page')
            ->withQueryString();

        return view($user->isCandidate() ? 'candidate.meetings.index' : 'employer.meetings.index', compact('upcoming', 'past', 'user'));
    }

    public function create(Request $request, JobApplication $jobApplication): View
    {
        $employer = $this->employer($request);
        $application = $this->ownedApplications($employer)->with(['job.company', 'user'])->whereKey($jobApplication->getKey())->firstOrFail();

        return view('employer.meetings.form', ['application' => $application, 'meeting' => null]);
    }

    public function edit(Request $request, Meeting $meeting): View
    {
        $employer = $this->employer($request);
        $meeting = $this->participantMeetings($employer)->with(['application.job.company', 'candidate'])->whereKey($meeting->getKey())->firstOrFail();
        abort_unless($meeting->status === MeetingStatus::Scheduled && $meeting->starts_at->isFuture(), 409, 'This meeting can no longer be rescheduled.');

        return view('employer.meetings.form', ['application' => $meeting->application, 'meeting' => $meeting]);
    }

    public function store(Request $request, JobApplication $jobApplication): RedirectResponse
    {
        $employer = $this->employer($request);
        $application = $this->ownedApplications($employer)->with('job.company')->whereKey($jobApplication->getKey())->firstOrFail();
        $data = $this->validatedMeetingData($request);

        $meeting = DB::transaction(function () use ($application, $employer, $data): Meeting {
            $meeting = Meeting::query()->create([
                'job_application_id' => $application->getKey(),
                'candidate_user_id' => $application->user_id,
                'employer_user_id' => $employer->getKey(),
                'created_by_user_id' => $employer->getKey(),
                ...$data,
                'timezone' => self::TIMEZONE,
                'status' => MeetingStatus::Scheduled,
            ]);
            $event = $meeting->events()->create([
                'changed_by_user_id' => $employer->getKey(),
                'event' => 'scheduled',
                'starts_at' => $meeting->starts_at,
                'status' => MeetingStatus::Scheduled->value,
            ]);
            $this->notifyParticipants($meeting, $event);

            return $meeting;
        });

        return redirect()->route('employer.meetings.show', $meeting)->with('status', 'Meeting scheduled. Both participants received an in-app notification.');
    }

    public function show(Request $request, Meeting $meeting): View
    {
        $user = $this->participant($request);
        $meeting = $this->participantMeetings($user)
            ->with(['application.job.company', 'candidate', 'employer', 'events.changedBy'])
            ->whereKey($meeting->getKey())
            ->firstOrFail();

        return view($user->isCandidate() ? 'candidate.meetings.show' : 'employer.meetings.show', compact('meeting', 'user'));
    }

    public function reschedule(Request $request, Meeting $meeting): RedirectResponse
    {
        $employer = $this->employer($request);
        $data = $this->validatedMeetingData($request);

        $meeting = DB::transaction(function () use ($employer, $meeting, $data): Meeting {
            $meeting = $this->participantMeetings($employer)->whereKey($meeting->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($meeting->status === MeetingStatus::Scheduled && $meeting->starts_at->isFuture(), 409, 'This meeting can no longer be rescheduled.');

            $previousStartsAt = $meeting->starts_at;
            $sameDetails = $previousStartsAt->equalTo($data['starts_at'])
                && $meeting->duration_minutes === $data['duration_minutes']
                && $meeting->title === $data['title']
                && $meeting->type === $data['type']
                && $meeting->meeting_url === $data['meeting_url']
                && $meeting->location === $data['location']
                && $meeting->agenda === $data['agenda'];
            if ($sameDetails) {
                return $meeting;
            }

            $meeting->update($data);
            $meeting->update(['timezone' => self::TIMEZONE]);
            $event = $meeting->events()->create([
                'changed_by_user_id' => $employer->getKey(),
                'event' => 'rescheduled',
                'previous_starts_at' => $previousStartsAt,
                'starts_at' => $meeting->starts_at,
                'previous_status' => MeetingStatus::Scheduled->value,
                'status' => MeetingStatus::Scheduled->value,
            ]);
            $this->notifyParticipants($meeting, $event);

            return $meeting;
        });

        return redirect()->route('employer.meetings.show', $meeting)->with('status', 'Meeting updated. Both participants received an in-app notification.');
    }

    public function cancel(Request $request, Meeting $meeting): RedirectResponse
    {
        $employer = $this->employer($request);
        DB::transaction(function () use ($employer, $meeting): void {
            $meeting = $this->participantMeetings($employer)->whereKey($meeting->getKey())->lockForUpdate()->firstOrFail();
            if ($meeting->status !== MeetingStatus::Scheduled) {
                return;
            }

            $previousStatus = $meeting->status->value;
            $meeting->update(['status' => MeetingStatus::Cancelled]);
            $event = $meeting->events()->create([
                'changed_by_user_id' => $employer->getKey(),
                'event' => 'cancelled',
                'previous_starts_at' => $meeting->starts_at,
                'starts_at' => $meeting->starts_at,
                'previous_status' => $previousStatus,
                'status' => MeetingStatus::Cancelled->value,
            ]);
            $this->notifyParticipants($meeting, $event);
        });

        return redirect()->route('employer.meetings.show', $meeting)->with('status', 'Meeting cancelled. Both participants received an in-app notification.');
    }

    public function complete(Request $request, Meeting $meeting): RedirectResponse
    {
        $employer = $this->employer($request);
        DB::transaction(function () use ($employer, $meeting): void {
            $meeting = $this->participantMeetings($employer)->whereKey($meeting->getKey())->lockForUpdate()->firstOrFail();
            abort_unless($meeting->status === MeetingStatus::Scheduled, 409, 'Only a scheduled meeting can be completed.');
            abort_unless($meeting->starts_at->isPast(), 409, 'A future meeting cannot be marked completed.');

            $meeting->update(['status' => MeetingStatus::Completed]);
            $meeting->events()->create([
                'changed_by_user_id' => $employer->getKey(),
                'event' => 'completed',
                'previous_starts_at' => $meeting->starts_at,
                'starts_at' => $meeting->starts_at,
                'previous_status' => MeetingStatus::Scheduled->value,
                'status' => MeetingStatus::Completed->value,
            ]);
        });

        return back()->with('status', 'Meeting marked as completed.');
    }

    /** @return array{title: string, starts_at: CarbonImmutable, duration_minutes: int, type: MeetingType, meeting_url: string|null, location: string|null, agenda: string|null} */
    private function validatedMeetingData(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'starts_at' => ['required', 'date_format:Y-m-d\\TH:i'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'type' => ['required', Rule::enum(MeetingType::class)],
            'meeting_url' => ['nullable', 'required_if:type,online', 'url:http,https', 'max:2048'],
            'location' => ['nullable', 'required_if:type,in_person', 'string', 'max:255'],
            'agenda' => ['nullable', 'string', 'max:3000'],
        ]);

        $startsAt = CarbonImmutable::createFromFormat('Y-m-d\\TH:i', $data['starts_at'], self::TIMEZONE);
        if (! $startsAt instanceof CarbonImmutable || ! $startsAt->isFuture()) {
            throw ValidationException::withMessages(['starts_at' => 'Choose a meeting time in the future (Rajasthan time).']);
        }
        $type = MeetingType::from($data['type']);

        return [
            'title' => trim($data['title']),
            'starts_at' => $startsAt->utc(),
            'duration_minutes' => (int) $data['duration_minutes'],
            'type' => $type,
            'meeting_url' => $type === MeetingType::Online ? trim((string) $data['meeting_url']) : null,
            'location' => $type === MeetingType::InPerson ? trim((string) $data['location']) : null,
            'agenda' => isset($data['agenda']) ? trim($data['agenda']) : null,
        ];
    }

    private function notifyParticipants(Meeting $meeting, MeetingEvent $event): void
    {
        foreach ([$meeting->candidate_user_id, $meeting->employer_user_id] as $userId) {
            $inserted = DB::table('meeting_notification_deliveries')->insertOrIgnore([
                'meeting_event_id' => $event->getKey(),
                'user_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            if ($inserted === 1) {
                User::query()->findOrFail($userId)->notify(new MeetingChangedNotification($meeting, $event));
            }
        }
    }

    private function participant(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && ($user->isCandidate() || $user->isEmployer()), 403);

        return $user;
    }

    private function employer(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->isEmployer(), 403);

        return $user;
    }

    /** @return Builder<JobApplication> */
    private function ownedApplications(User $employer): Builder
    {
        return JobApplication::query()->whereHas('job', fn (Builder $jobs) => $jobs->ownedByEmployer($employer));
    }

    /** @return Builder<Meeting> */
    private function participantMeetings(User $user): Builder
    {
        return Meeting::query()->where($user->isCandidate() ? 'candidate_user_id' : 'employer_user_id', $user->getKey());
    }
}
