<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property int|null $actor_user_id
 * @property string $action
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property string $summary
 * @property string|null $reason
 * @property array<string, mixed>|null $before_values
 * @property array<string, mixed>|null $after_values
 * @property Carbon|null $created_at
 */
#[Fillable(['actor_user_id', 'action', 'subject_type', 'subject_id', 'summary', 'reason', 'before_values', 'after_values'])]
class AdminAuditLog extends Model
{
    public $timestamps = false;

    protected static function booted(): void
    {
        static::updating(fn (): never => throw new LogicException('Administrative audit records are append-only.'));
        static::deleting(fn (): never => throw new LogicException('Administrative audit records are append-only.'));
    }

    protected function casts(): array
    {
        return [
            'before_values' => 'array',
            'after_values' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
