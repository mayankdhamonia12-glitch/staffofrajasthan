<?php

namespace App\Notifications;

use App\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ApplicationStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly JobApplication $application) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array{event: string, application_id: int, job_id: int, job_title: string, status: string} */
    public function toArray(object $notifiable): array
    {
        return [
            'event' => 'application_status_changed',
            'application_id' => (int) $this->application->getKey(),
            'job_id' => (int) $this->application->job_id,
            'job_title' => $this->application->job->title,
            'status' => (string) $this->application->status,
        ];
    }
}
