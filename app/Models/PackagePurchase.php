<?php

namespace App\Models;

use App\PackagePurchaseStatus;
use App\Support\InrMoney;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $purchase_reference
 * @property string $idempotency_key
 * @property int|null $buyer_user_id
 * @property int|null $package_id
 * @property array<string, mixed> $package_snapshot
 * @property int $amount_paise
 * @property string $currency
 * @property PackagePurchaseStatus $status
 * @property Carbon|null $paid_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'purchase_reference', 'idempotency_key', 'buyer_user_id', 'package_id', 'package_snapshot',
    'amount_paise', 'currency', 'status', 'paid_at',
])]
class PackagePurchase extends Model
{
    public function getRouteKeyName(): string
    {
        return 'purchase_reference';
    }

    protected function casts(): array
    {
        return [
            'package_snapshot' => 'array',
            'amount_paise' => 'integer',
            'status' => PackagePurchaseStatus::class,
            'paid_at' => 'immutable_datetime',
        ];
    }

    public function formattedAmount(): string
    {
        return InrMoney::format($this->amount_paise);
    }

    /** @return BelongsTo<User, $this> */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    /** @return BelongsTo<Package, $this> */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /** @return HasMany<PackagePayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(PackagePayment::class);
    }

    /** @return HasOne<PackageEntitlement, $this> */
    public function entitlement(): HasOne
    {
        return $this->hasOne(PackageEntitlement::class);
    }
}
