<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $site_name
 * @property string|null $contact_email
 * @property string|null $contact_phone
 * @property string $timezone
 * @property string $locale
 * @property bool $package_purchases_enabled
 * @property bool $paid_job_posting_required
 * @property int|null $updated_by_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['site_name', 'contact_email', 'contact_phone', 'timezone', 'locale', 'package_purchases_enabled', 'paid_job_posting_required', 'updated_by_user_id'])]
class PlatformSetting extends Model
{
    protected function casts(): array
    {
        return [
            'package_purchases_enabled' => 'boolean',
            'paid_job_posting_required' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
