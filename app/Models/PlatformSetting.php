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
 * @property int|null $updated_by_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['site_name', 'contact_email', 'contact_phone', 'timezone', 'locale', 'updated_by_user_id'])]
class PlatformSetting extends Model
{
    /** @return BelongsTo<User, $this> */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
