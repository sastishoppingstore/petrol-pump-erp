<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceTemplate extends Model
{
    public const SLUG_MODERN_RED_BAND = 'modern_red_band';
    public const SLUG_CLASSIC = 'classic';
    public const SLUG_MINIMAL = 'minimal';

    public const PAPER_A4 = 'A4';
    public const PAPER_THERMAL_80MM = 'thermal_80mm';
    public const PAPER_THERMAL_58MM = 'thermal_58mm';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'paper_size',
        'primary_color',
        'dark_red_color',
        'accent_color',
        'background_color',
        'light_grey_color',
        'text_color',
        'header_bg',
        'header_text',
        'footer_bg',
        'footer_text',
        'show_logo',
        'show_urdu_name',
        'show_vehicle',
        'show_customer_box',
        'show_amount_in_words',
        'show_signatures',
        'show_qr_code',
        'show_udhaar_balance',
        'show_fbr_details',
        'custom_css',
        'config',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'show_logo' => 'boolean',
            'show_urdu_name' => 'boolean',
            'show_vehicle' => 'boolean',
            'show_customer_box' => 'boolean',
            'show_amount_in_words' => 'boolean',
            'show_signatures' => 'boolean',
            'show_qr_code' => 'boolean',
            'show_udhaar_balance' => 'boolean',
            'show_fbr_details' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'config' => 'array',
        ];
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'template_id');
    }

    /**
     * Get or create the default template (Modern Red Band).
     */
    public static function defaultTemplate(): self
    {
        $template = static::where('is_default', true)->where('is_active', true)->first();

        if ($template) {
            return $template;
        }

        return static::firstOrCreate(
            ['slug' => self::SLUG_MODERN_RED_BAND],
            [
                'name' => 'Modern Red Band',
                'description' => 'Vital Petroleum official red band A4 invoice with bold header and footer',
                'paper_size' => self::PAPER_A4,
                'primary_color' => '#D71920',
                'dark_red_color' => '#A30F15',
                'accent_color' => '#1B1B1B',
                'background_color' => '#FFFFFF',
                'light_grey_color' => '#F6F6F6',
                'text_color' => '#1B1B1B',
                'header_bg' => '#D71920',
                'header_text' => '#FFFFFF',
                'footer_bg' => '#D71920',
                'footer_text' => '#FFFFFF',
                'show_logo' => true,
                'show_urdu_name' => true,
                'show_vehicle' => true,
                'show_customer_box' => true,
                'show_amount_in_words' => true,
                'show_signatures' => true,
                'show_qr_code' => true,
                'show_udhaar_balance' => true,
                'show_fbr_details' => true,
                'is_default' => true,
                'is_active' => true,
            ]
        );
    }
}
