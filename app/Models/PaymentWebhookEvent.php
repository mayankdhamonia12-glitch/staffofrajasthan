<?php

namespace App\Models;

use App\PaymentWebhookStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $event_id
 * @property string $event_type
 * @property string $payload_hash
 * @property PaymentWebhookStatus $status
 * @property string|null $failure_code
 * @property Carbon $received_at
 * @property Carbon|null $processed_at
 */
#[Fillable(['event_id', 'event_type', 'payload_hash', 'status', 'failure_code', 'received_at', 'processed_at'])]
class PaymentWebhookEvent extends Model
{
    protected function casts(): array
    {
        return [
            'status' => PaymentWebhookStatus::class,
            'received_at' => 'immutable_datetime',
            'processed_at' => 'immutable_datetime',
        ];
    }
}
