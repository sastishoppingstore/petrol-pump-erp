<?php

namespace App\Http\Middleware;

use App\Services\System\SettingService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the admin-chosen site language (setting: ui_language) to every
 * web request, so __('ui.*') strings render site-wide in English or Urdu.
 *
 * The setting defaults to 'en'. Anything other than 'en'/'ur' stored in
 * the database falls back to 'en' instead of breaking translations.
 */
class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = 'en';

        try {
            $stored = app(SettingService::class)->get('ui_language', 'en');
            if (in_array($stored, ['en', 'ur'], true)) {
                $locale = $stored;
            }
        } catch (\Throwable $e) {
            // Settings table missing (pre-install) — keep the default locale.
            $locale = 'en';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
