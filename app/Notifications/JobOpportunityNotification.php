<?php

namespace App\Notifications;

use App\Models\Job;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class JobOpportunityNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly int $jobId,
        private readonly string $jobTitle,
        private readonly string $companyName,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array{event: string, job_id: int, job_title: string, company_name: string} */
    public function toArray(object $notifiable): array
    {
        return [
            'event' => 'job_opportunity',
            'job_id' => $this->jobId,
            'job_title' => $this->jobTitle,
            'company_name' => $this->companyName,
        ];
    }

    public static function forJob(Job $job): self
    {
        return new self((int) $job->getKey(), $job->title, $job->company->name);
    }
}
