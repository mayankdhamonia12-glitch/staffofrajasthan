<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property int $id */
#[Fillable(['package_entitlement_id', 'job_id', 'employer_user_id', 'consumed_at'])]
class JobPostingUse extends Model
{
    protected function casts(): array
    {
        return ['consumed_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<PackageEntitlement, $this> */
    public function entitlement(): BelongsTo
    {
        return $this->belongsTo(PackageEntitlement::class, 'package_entitlement_id');
    }

    /** @return BelongsTo<Job, $this> */
    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    /** @return BelongsTo<User, $this> */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_user_id');
    }
}
