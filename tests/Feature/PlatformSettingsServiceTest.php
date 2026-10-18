<?php

use App\Models\PlatformSetting;
use App\Services\PlatformSettingsService;
use Illuminate\Support\Facades\Schema;

it('has the platform settings table after a fresh test migration', function () {
    expect(Schema::hasTable('platform_settings'))->toBeTrue();
});

it('initializes default platform settings idempotently', function () {
    $service = app(PlatformSettingsService::class);

    $first = $service->current();
    $second = $service->current();

    expect($first->id)->toBe(1)
        ->and($first->site_name)->toBe('Staff of Rajasthan')
        ->and($first->timezone)->toBe('UTC')
        ->and($first->locale)->toBe('en')
        ->and($second->id)->toBe($first->id)
        ->and(PlatformSetting::query()->count())->toBe(1);
});
