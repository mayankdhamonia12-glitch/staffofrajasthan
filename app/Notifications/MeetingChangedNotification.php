<?php

namespace App\Notifications;

use App\Models\Meeting;
use App\Models\MeetingEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MeetingChangedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Meeting $meeting, private readonly MeetingEvent $event) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, int|string|null> */
    public function toArray(object $notifiable): array
    {
        return [
            'meeting_id' => $this->meeting->getKey(),
            'event_id' => $this->event->getKey(),
            'event' => $this->event->event,
            'title' => $this->meeting->title,
            'starts_at' => $this->meeting->starts_at->toIso8601String(),
            'timezone' => $this->meeting->timezone,
        ];
    }
}
