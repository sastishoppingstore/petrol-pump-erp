<?php

/*
|--------------------------------------------------------------------------
| Global helpers
|--------------------------------------------------------------------------
| Ye file AppServiceProvider::register() se load hoti hai (composer
| autoload change kiye baghair), taake shared-hosting deploy par bhi
| hamesha available rahe.
*/

if (! function_exists('setting')) {
    /**
     * Kisi bhi setting ki value parho — pehle Admin SettingsService,
     * phir System SettingService, warna default. Kabhi fatal nahi hota:
     * settings table na ho (pre-install) to default return hota hai.
     *
     * (Kai services is helper ko call karti theen jabke ye exist hi
     * nahi karta tha — wahi "undefined function" fatals ki jarr thi.)
     */
    function setting(string $key, mixed $default = null): mixed
    {
        try {
            $value = app(\App\Services\Admin\SettingsService::class)->get($key, null);
            if ($value !== null) {
                return $value;
            }
        } catch (\Throwable) {
            // settings backend ready nahi — default par gir jao
        }

        try {
            $value = app(\App\Services\System\SettingService::class)->get($key, null);
            if ($value !== null) {
                return $value;
            }
        } catch (\Throwable) {
            // ignore — default return hoga
        }

        return $default;
    }
}
