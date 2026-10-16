<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingEvent extends Model
{
    protected $fillable = [
        'meeting_id', 'changed_by_user_id', 'event', 'previous_starts_at', 'starts_at', 'previous_status', 'status',
    ];

    protected function casts(): array
    {
        return ['previous_starts_at' => 'immutable_datetime', 'starts_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<Meeting, $this> */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    /** @return BelongsTo<User, $this> */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
