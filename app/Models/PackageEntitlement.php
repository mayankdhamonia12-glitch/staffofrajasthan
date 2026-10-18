<?php

namespace App\Models;

use App\PackageType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $package_purchase_id
 * @property int|null $user_id
 * @property int|null $package_id
 * @property PackageType $type
 * @property int $credits_granted
 * @property int $credits_remaining
 * @property array<string, mixed> $package_snapshot
 * @property Carbon $starts_at
 * @property Carbon|null $valid_until
 */
#[Fillable([
    'package_purchase_id', 'user_id', 'package_id', 'type', 'credits_granted', 'credits_remaining',
    'package_snapshot', 'starts_at', 'valid_until',
])]
class PackageEntitlement extends Model
{
    protected function casts(): array
    {
        return [
            'type' => PackageType::class,
            'credits_granted' => 'integer',
            'credits_remaining' => 'integer',
            'package_snapshot' => 'array',
            'starts_at' => 'immutable_datetime',
            'valid_until' => 'immutable_datetime',
        ];
    }

    public function isActive(): bool
    {
        return $this->credits_remaining > 0 && ($this->valid_until === null || $this->valid_until->isFuture());
    }

    /** @return BelongsTo<PackagePurchase, $this> */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(PackagePurchase::class, 'package_purchase_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Package, $this> */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /** @return HasMany<JobPostingUse, $this> */
    public function jobUses(): HasMany
    {
        return $this->hasMany(JobPostingUse::class);
    }

    /** @return HasMany<CandidateProfileAccess, $this> */
    public function candidateAccesses(): HasMany
    {
        return $this->hasMany(CandidateProfileAccess::class);
    }
}
