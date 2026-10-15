<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JobAlertMatch extends Model
{
    public $timestamps = false;

    protected $fillable = ['job_alert_id', 'job_id', 'matched_at'];

    protected function casts(): array
    {
        return ['matched_at' => 'datetime'];
    }

    /** @return BelongsTo<JobAlert, $this> */
    public function alert(): BelongsTo
    {
        return $this->belongsTo(JobAlert::class, 'job_alert_id');
    }

    /** @return BelongsTo<Job, $this> */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }
}
