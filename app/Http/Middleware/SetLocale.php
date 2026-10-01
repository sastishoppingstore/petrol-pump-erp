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

        // Session override (dashboard launcher ka apna toggle) ko tarjeeh do —
        // is tarah launcher aur baqi site hamesha ek hi bhasha bolte hain.
        $sessionLocale = $request->hasSession() ? $request->session()->get('locale') : null;
        if (in_array($sessionLocale, ['en', 'ur'], true)) {
            App::setLocale($sessionLocale);

            return $next($request);
        }

        App::setLocale($locale);

        return $next($request);
    }
}
