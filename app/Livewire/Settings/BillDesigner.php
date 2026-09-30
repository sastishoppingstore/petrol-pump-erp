<?php

namespace App\Livewire\Settings;

use App\Models\InvoiceTemplate;
use App\Services\System\SettingService;
use App\Support\PermissionList;
use Illuminate\Support\Str;
use Livewire\Component;

class BillDesigner extends Component
{
    public ?int $templateId = null;
    public string $name = 'Modern Red Band';
    public string $slug = 'modern_red_band';
    public ?string $description = 'Official Vital Petroleum franchise red band template';
    public string $paper_size = 'A4';

    // Color Palette
    public string $primary_color = '#D71920';
    public string $dark_red_color = '#A30F15';
    public string $accent_color = '#1B1B1B';
    public string $background_color = '#FFFFFF';
    public string $light_grey_color = '#F6F6F6';
    public string $text_color = '#1B1B1B';
    public string $header_bg = '#D71920';
    public string $header_text = '#FFFFFF';
    public string $footer_bg = '#D71920';
    public string $footer_text = '#FFFFFF';

    // Field Toggles
    public bool $show_logo = true;
    public bool $show_urdu_name = true;
    public bool $show_customer_box = true;
    public bool $show_vehicle = true;
    public bool $show_amount_in_words = true;
    public bool $show_signatures = true;
    public bool $show_qr_code = true;
    public bool $show_udhaar_balance = true;
    public bool $show_fbr_details = true;
    public bool $is_default = true;
    public ?string $custom_css = null;

    // Preview Mode
    public string $previewFormat = 'a4'; // 'a4', 'thermal_80mm', 'thermal_58mm'
    public bool $savedSuccess = false;

    public function mount(SettingService $settings): void
    {
        $template = InvoiceTemplate::where('is_default', true)->first()
            ?? InvoiceTemplate::where('slug', 'modern_red_band')->first()
            ?? InvoiceTemplate::first();

        if ($template) {
            $this->loadTemplate($template);
        } else {
            $this->resetToDefaults();
        }
    }

    public function selectTemplate(string $slug): void
    {
        $template = InvoiceTemplate::where('slug', $slug)->first();
        if ($template) {
            $this->loadTemplate($template);
        } elseif ($slug === 'classic') {
            $this->name = 'Classic Corporate';
            $this->slug = 'classic';
            $this->primary_color = '#D71920';
            $this->dark_red_color = '#A30F15';
            $this->header_bg = '#1E293B';
            $this->header_text = '#FFFFFF';
            $this->footer_bg = '#0F172A';
            $this->footer_text = '#FFFFFF';
            $this->templateId = null;
        } elseif ($slug === 'minimal') {
            $this->name = 'Minimal Ink-Saver';
            $this->slug = 'minimal';
            $this->primary_color = '#D71920';
            $this->dark_red_color = '#881337';
            $this->header_bg = '#FFFFFF';
            $this->header_text = '#D71920';
            $this->footer_bg = '#F3F4F6';
            $this->footer_text = '#374151';
            $this->show_logo = false;
            $this->templateId = null;
        } else {
            $this->resetToDefaults();
        }

        $this->savedSuccess = false;
    }

    public function applyPalette(string $palette): void
    {
        match ($palette) {
            'vital_red' => [
                $this->primary_color = '#D71920',
                $this->dark_red_color = '#A30F15',
                $this->header_bg = '#D71920',
                $this->header_text = '#FFFFFF',
                $this->footer_bg = '#D71920',
                $this->footer_text = '#FFFFFF',
                $this->text_color = '#1B1B1B',
            ],
            'corporate_navy' => [
                $this->primary_color = '#1E293B',
                $this->dark_red_color = '#0F172A',
                $this->header_bg = '#1E293B',
                $this->header_text = '#FFFFFF',
                $this->footer_bg = '#0F172A',
                $this->footer_text = '#FFFFFF',
                $this->text_color = '#0F172A',
            ],
            'emerald_green' => [
                $this->primary_color = '#059669',
                $this->dark_red_color = '#047857',
                $this->header_bg = '#059669',
                $this->header_text = '#FFFFFF',
                $this->footer_bg = '#047857',
                $this->footer_text = '#FFFFFF',
                $this->text_color = '#064E3B',
            ],
            'slate_charcoal' => [
                $this->primary_color = '#334155',
                $this->dark_red_color = '#1E293B',
                $this->header_bg = '#334155',
                $this->header_text = '#FFFFFF',
                $this->footer_bg = '#1E293B',
                $this->footer_text = '#FFFFFF',
                $this->text_color = '#1E293B',
            ],
            default => null,
        };

        $this->savedSuccess = false;
    }

    public function resetToDefaults(): void
    {
        $this->name = 'Modern Red Band';
        $this->slug = 'modern_red_band';
        $this->description = 'Vital Petroleum official red band A4 invoice with bold header and footer bands';
        $this->paper_size = 'A4';
        $this->primary_color = '#D71920';
        $this->dark_red_color = '#A30F15';
        $this->accent_color = '#1B1B1B';
        $this->background_color = '#FFFFFF';
        $this->light_grey_color = '#F6F6F6';
        $this->text_color = '#1B1B1B';
        $this->header_bg = '#D71920';
        $this->header_text = '#FFFFFF';
        $this->footer_bg = '#D71920';
        $this->footer_text = '#FFFFFF';
        $this->show_logo = true;
        $this->show_urdu_name = true;
        $this->show_customer_box = true;
        $this->show_vehicle = true;
        $this->show_amount_in_words = true;
        $this->show_signatures = true;
        $this->show_qr_code = true;
        $this->show_udhaar_balance = true;
        $this->show_fbr_details = true;
        $this->is_default = true;
        $this->custom_css = null;
        $this->savedSuccess = false;
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'primary_color' => ['required', 'string', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'dark_red_color' => ['required', 'string', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'header_bg' => ['required', 'string', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'header_text' => ['required', 'string', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'footer_bg' => ['required', 'string', 'regex:/^#[a-fA-F0-9]{6}$/'],
            'footer_text' => ['required', 'string', 'regex:/^#[a-fA-F0-9]{6}$/'],
        ]);

        if ($this->is_default) {
            InvoiceTemplate::where('id', '!=', $this->templateId)->update(['is_default' => false]);
        }

        $template = InvoiceTemplate::updateOrCreate(
            ['slug' => $this->slug ?: Str::slug($this->name)],
            [
                'name' => $this->name,
                'description' => $this->description,
                'paper_size' => $this->paper_size,
                'primary_color' => $this->primary_color,
                'dark_red_color' => $this->dark_red_color,
                'accent_color' => $this->accent_color,
                'background_color' => $this->background_color,
                'light_grey_color' => $this->light_grey_color,
                'text_color' => $this->text_color,
                'header_bg' => $this->header_bg,
                'header_text' => $this->header_text,
                'footer_bg' => $this->footer_bg,
                'footer_text' => $this->footer_text,
                'show_logo' => $this->show_logo,
                'show_urdu_name' => $this->show_urdu_name,
                'show_customer_box' => $this->show_customer_box,
                'show_vehicle' => $this->show_vehicle,
                'show_amount_in_words' => $this->show_amount_in_words,
                'show_signatures' => $this->show_signatures,
                'show_qr_code' => $this->show_qr_code,
                'show_udhaar_balance' => $this->show_udhaar_balance,
                'show_fbr_details' => $this->show_fbr_details,
                'custom_css' => $this->custom_css,
                'is_default' => $this->is_default,
                'is_active' => true,
            ]
        );

        $this->templateId = $template->id;
        $this->slug = $template->slug;
        $this->savedSuccess = true;
    }

    private function loadTemplate(InvoiceTemplate $template): void
    {
        $this->templateId = $template->id;
        $this->name = $template->name;
        $this->slug = $template->slug;
        $this->description = $template->description;
        $this->paper_size = $template->paper_size;
        $this->primary_color = $template->primary_color;
        $this->dark_red_color = $template->dark_red_color;
        $this->accent_color = $template->accent_color;
        $this->background_color = $template->background_color;
        $this->light_grey_color = $template->light_grey_color;
        $this->text_color = $template->text_color;
        $this->header_bg = $template->header_bg;
        $this->header_text = $template->header_text;
        $this->footer_bg = $template->footer_bg;
        $this->footer_text = $template->footer_text;
        $this->show_logo = (bool) $template->show_logo;
        $this->show_urdu_name = (bool) $template->show_urdu_name;
        $this->show_customer_box = (bool) $template->show_customer_box;
        $this->show_vehicle = (bool) $template->show_vehicle;
        $this->show_amount_in_words = (bool) $template->show_amount_in_words;
        $this->show_signatures = (bool) $template->show_signatures;
        $this->show_qr_code = (bool) $template->show_qr_code;
        $this->show_udhaar_balance = (bool) $template->show_udhaar_balance;
        $this->show_fbr_details = (bool) $template->show_fbr_details;
        $this->is_default = (bool) $template->is_default;
        $this->custom_css = $template->custom_css;
    }

    public function render(SettingService $settings)
    {
        $station = $settings->stationIdentity();
        $allTemplates = InvoiceTemplate::query()->orderBy('name')->get();

        return view('livewire.settings.bill-designer', [
            'station' => $station,
            'allTemplates' => $allTemplates,
        ])->layout('layouts.app', ['title' => 'Bill Designer & Branding — Mehar Filling Station']);
    }
}
