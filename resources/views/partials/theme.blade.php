{{--
    Brand theme bridge — Admin Settings → poori site ki appearance.

    Admin → Settings me save kiye gaye rang (theme_primary_color waghera)
    yahan CSS variables ban kar layout se pehle load hote hain, taake ERP ki
    har screen (sidebar, login, dashboard, buttons) bina rebuild ke
    admin ke chune hue rangon me rang jaye.

    Kuch save na ho to Vital Petroleum ke default rang use hote hain.
    SettingService khud `settings` table ki mojoodgi check karta hai,
    is liye ye partial pre-migration bhi safe hai.
--}}
@php
    $themeSettings = app(\App\Services\System\SettingService::class);
    $brandPrimaryHex = $themeSettings->get('theme_primary_color')
        ?: $themeSettings->get('theme_primary')
        ?: '#D71920';
    $brandDarkHex = $themeSettings->get('theme_dark_red') ?: '#A30F15';
    $hexToRgbChannels = static function (string $hex): string {
        $hex = ltrim(trim($hex), '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return '215 25 32'; // Vital red fallback
        }

        return hexdec(substr($hex, 0, 2)) . ' ' . hexdec(substr($hex, 2, 2)) . ' ' . hexdec(substr($hex, 4, 2));
    };
@endphp
<style>
    :root {
        --brand-primary-rgb: {{ $hexToRgbChannels($brandPrimaryHex) }};
        --brand-dark-rgb: {{ $hexToRgbChannels($brandDarkHex) }};
    }
</style>
