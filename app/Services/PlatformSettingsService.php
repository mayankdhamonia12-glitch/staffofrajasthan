<?php

namespace App\Services;

use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PlatformSettingsService
{
    private ?PlatformSetting $settings = null;

    public function __construct(private readonly AdminAuditLogger $audit) {}

    public function current(): PlatformSetting
    {
        return $this->settings ??= PlatformSetting::query()->firstOrCreate(['id' => 1], [
            'site_name' => 'Staff of Rajasthan',
            'timezone' => 'UTC',
            'locale' => 'en',
        ]);
    }

    /** @param array{site_name: string, contact_email: string|null, contact_phone: string|null, timezone: string, locale: string} $values */
    public function update(User $actor, array $values): PlatformSetting
    {
        return DB::transaction(function () use ($actor, $values): PlatformSetting {
            $settings = PlatformSetting::query()->whereKey(1)->lockForUpdate()->firstOrCreate(['id' => 1], [
                'site_name' => 'Staff of Rajasthan',
                'timezone' => 'UTC',
                'locale' => 'en',
            ]);
            $before = $settings->only(['site_name', 'contact_email', 'contact_phone', 'timezone', 'locale']);
            $settings->forceFill([...$values, 'updated_by_user_id' => $actor->getKey()])->save();
            $after = $settings->only(['site_name', 'contact_email', 'contact_phone', 'timezone', 'locale']);

            $this->audit->record($actor, 'platform.settings_updated', $settings, 'Updated platform settings.', null, $before, $after);
            $settings->refresh();
            $this->settings = $settings;

            return $this->settings;
        });
    }
}
