<?php

namespace App\Http\Middleware;

use App\Services\PlatformSettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyPlatformSettings
{
    public function __construct(private readonly PlatformSettingsService $settings) {}

    /** @param Closure(Request): (Response) $next */
    public function handle(Request $request, Closure $next): Response
    {
        $settings = $this->settings->current();
        config(['app.name' => $settings->site_name]);
        config(['app.timezone' => $settings->timezone]);
        date_default_timezone_set($settings->timezone);
        app()->setLocale($settings->locale);
        view()->share('platformSettings', $settings);

        return $next($request);
    }
}
