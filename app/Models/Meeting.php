<?php

namespace App\Models;

use App\MeetingStatus;
use App\MeetingType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property CarbonImmutable $starts_at
 * @property int $duration_minutes
 * @property MeetingType $type
 * @property MeetingStatus $status
 */
class Meeting extends Model
{
    protected $fillable = [
        'job_application_id', 'candidate_user_id', 'employer_user_id', 'created_by_user_id',
        'title', 'starts_at', 'duration_minutes', 'timezone', 'type', 'meeting_url', 'location', 'agenda', 'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'duration_minutes' => 'integer',
            'type' => MeetingType::class,
            'status' => MeetingStatus::class,
        ];
    }

    /** @return BelongsTo<JobApplication, $this> */
    public function application(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class, 'job_application_id');
    }

    /** @return BelongsTo<User, $this> */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_user_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** @return HasMany<MeetingEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(MeetingEvent::class)->latest();
    }
}
