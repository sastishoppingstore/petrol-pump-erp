<?php

namespace Database\Seeders;

use App\Models\InvoiceTemplate;
use Illuminate\Database\Seeder;

class InvoiceTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Modern Red Band',
                'slug' => InvoiceTemplate::SLUG_MODERN_RED_BAND,
                'description' => 'Vital Petroleum official red band A4 invoice with bold header and footer bands',
                'paper_size' => 'A4',
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
            ],
            [
                'name' => 'Classic Corporate',
                'slug' => InvoiceTemplate::SLUG_CLASSIC,
                'description' => 'Traditional corporate receipt format with subtle borders and red highlights',
                'paper_size' => 'A4',
                'primary_color' => '#D71920',
                'dark_red_color' => '#A30F15',
                'accent_color' => '#334155',
                'background_color' => '#FFFFFF',
                'light_grey_color' => '#F8FAFC',
                'text_color' => '#0F172A',
                'header_bg' => '#1E293B',
                'header_text' => '#FFFFFF',
                'footer_bg' => '#0F172A',
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
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Minimal Ink-Saver',
                'slug' => InvoiceTemplate::SLUG_MINIMAL,
                'description' => 'Clean lightweight monochrome design optimized for ink saving and fast laser printing',
                'paper_size' => 'A4',
                'primary_color' => '#D71920',
                'dark_red_color' => '#881337',
                'accent_color' => '#000000',
                'background_color' => '#FFFFFF',
                'light_grey_color' => '#FAFAFA',
                'text_color' => '#111827',
                'header_bg' => '#FFFFFF',
                'header_text' => '#D71920',
                'footer_bg' => '#F3F4F6',
                'footer_text' => '#374151',
                'show_logo' => false,
                'show_urdu_name' => true,
                'show_vehicle' => true,
                'show_customer_box' => true,
                'show_amount_in_words' => true,
                'show_signatures' => true,
                'show_qr_code' => true,
                'show_udhaar_balance' => true,
                'show_fbr_details' => false,
                'is_default' => false,
                'is_active' => true,
            ],
        ];

        foreach ($templates as $tmpl) {
            InvoiceTemplate::updateOrCreate(
                ['slug' => $tmpl['slug']],
                $tmpl
            );
        }
    }
}
