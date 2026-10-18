<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $employer_user_id
 * @property int $candidate_profile_id
 * @property int|null $package_entitlement_id
 * @property Carbon $granted_at
 * @property Carbon|null $valid_until
 */
#[Fillable(['employer_user_id', 'candidate_profile_id', 'package_entitlement_id', 'granted_at', 'valid_until'])]
class CandidateProfileAccess extends Model
{
    protected function casts(): array
    {
        return ['granted_at' => 'immutable_datetime', 'valid_until' => 'immutable_datetime'];
    }

    public function isActive(): bool
    {
        return $this->employer_user_id !== null && ($this->valid_until === null || $this->valid_until->isFuture());
    }

    /** @return BelongsTo<User, $this> */
    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_user_id');
    }

    /** @return BelongsTo<CandidateProfile, $this> */
    public function candidateProfile(): BelongsTo
    {
        return $this->belongsTo(CandidateProfile::class);
    }

    /** @return BelongsTo<PackageEntitlement, $this> */
    public function entitlement(): BelongsTo
    {
        return $this->belongsTo(PackageEntitlement::class, 'package_entitlement_id');
    }
}
