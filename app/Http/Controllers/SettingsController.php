<?php

namespace App\Http\Controllers;

use App\Services\System\SettingService;
use App\Support\PermissionList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingService $settingService
    ) {}

    public function index(): View
    {
        $station = $this->settingService->stationIdentity();
        $settings = $this->settingService->all();

        return view('settings.index', compact('station', 'settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'station_name_ur' => 'nullable|string|max:255',
            'station_name_en' => 'nullable|string|max:255',
            'owner_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'whatsapp' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'ntn' => 'nullable|string|max:50',
            'strn' => 'nullable|string|max:50',
            'omc_brand' => 'nullable|string|max:255',
            'thank_you_message' => 'nullable|string|max:255',
            'shift_variance_threshold' => 'nullable|numeric|min:0',
            'meter_variance_tolerance' => 'nullable|numeric|min:0',
            'overdue_credit_days' => 'nullable|integer|min:1',
            'theme_primary' => 'nullable|string|max:20',
        ]);

        foreach ($data as $key => $value) {
            if ($value !== null) {
                $this->settingService->set($key, (string) $value);
            }
        }

        return back()->with('success', 'ترتیبات کامیابی کے ساتھ محفوظ ہو گئی ہیں۔');
    }
}
