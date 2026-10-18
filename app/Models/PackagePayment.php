<?php

namespace App\Models;

use App\PackagePaymentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $package_purchase_id
 * @property string $provider
 * @property string|null $provider_order_id
 * @property string|null $provider_payment_id
 * @property int $amount_paise
 * @property string $currency
 * @property PackagePaymentStatus $status
 * @property string|null $failure_code
 * @property Carbon|null $captured_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'package_purchase_id', 'provider', 'provider_order_id', 'provider_payment_id', 'amount_paise',
    'currency', 'status', 'failure_code', 'captured_at',
])]
class PackagePayment extends Model
{
    protected function casts(): array
    {
        return [
            'amount_paise' => 'integer',
            'status' => PackagePaymentStatus::class,
            'captured_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<PackagePurchase, $this> */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(PackagePurchase::class, 'package_purchase_id');
    }
}
