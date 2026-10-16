<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class JobApplication extends Model
{
    protected $fillable = ['user_id', 'job_id', 'cover_letter', 'status'];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Job, $this> */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /** @return HasMany<JobApplicationStatusHistory, $this> */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(JobApplicationStatusHistory::class)->latest('created_at');
    }

    /** @return HasMany<JobApplicationNote, $this> */
    public function notes(): HasMany
    {
        return $this->hasMany(JobApplicationNote::class, 'job_application_id')->latest();
    }

    /** @return HasOne<Conversation, $this> */
    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }

    /** @return HasMany<Meeting, $this> */
    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class);
    }
}
