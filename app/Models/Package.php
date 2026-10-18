<?php

namespace App\Models;

use App\PackageType;
use App\Support\InrMoney;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property PackageType $type
 * @property string $name
 * @property string|null $description
 * @property int $price_paise
 * @property string $currency
 * @property int|null $job_posting_credits
 * @property int|null $listing_validity_days
 * @property int|null $candidate_access_credits
 * @property int|null $entitlement_validity_days
 * @property array<int, string>|null $features
 * @property bool $is_active
 * @property int $display_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder<static> active()
 */
#[Fillable([
    'type', 'name', 'description', 'price_paise', 'currency', 'job_posting_credits',
    'listing_validity_days', 'candidate_access_credits', 'entitlement_validity_days',
    'features', 'is_active', 'display_order',
])]
class Package extends Model
{
    protected function casts(): array
    {
        return [
            'type' => PackageType::class,
            'price_paise' => 'integer',
            'job_posting_credits' => 'integer',
            'listing_validity_days' => 'integer',
            'candidate_access_credits' => 'integer',
            'entitlement_validity_days' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    /** @param Builder<Package> $query
     * @return Builder<Package>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function formattedPrice(): string
    {
        return InrMoney::format($this->price_paise);
    }

    /** @return HasMany<PackagePurchase, $this> */
    public function purchases(): HasMany
    {
        return $this->hasMany(PackagePurchase::class);
    }

    /** @return HasMany<PackageEntitlement, $this> */
    public function entitlements(): HasMany
    {
        return $this->hasMany(PackageEntitlement::class);
    }
}
