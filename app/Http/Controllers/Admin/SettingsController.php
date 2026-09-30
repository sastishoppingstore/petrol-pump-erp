<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\SettingsService;
use Illuminate\Http\Request;

/**
 * System Settings Panel (Super Admin Only)
 * Manages all configurable settings: prices, taxes, thresholds, notifications, reports
 */
class SettingsController extends Controller
{
    protected SettingsService $settings;
    
    public function __construct(SettingsService $settings)
    {
        $this->settings = $settings;
        $this->middleware('auth');
        $this->middleware('admin'); // Super admin only
    }
    
    /**
     * Show settings dashboard
     */
    public function index()
    {
        $categories = [
            'fuel' => 'Fuel Prices',
            'tax' => 'Tax Configuration',
            'variance' => 'Variance Thresholds',
            'notifications' => 'Notifications & Alerts',
            'reports' => 'Report Settings',
            'remember' => 'Remember/Keep Settings',
            'system' => 'System Configuration',
        ];
        
        $activeCategory = request('category', 'fuel');
        $settings = $this->settings->byCategory($activeCategory);
        $allSettings = $this->settings->allWithMetadata();
        
        return view('admin.settings.index', compact('categories', 'activeCategory', 'settings', 'allSettings'));
    }
    
    /**
     * Update a single setting
     */
    public function update(Request $request, $key)
    {
        $this->authorize('admin'); // Super admin check
        
        $value = $request->input('value');
        $allSettings = $this->settings->allWithMetadata();
        
        if (!isset($allSettings[$key])) {
            return response()->json(['error' => 'Invalid setting key'], 422);
        }
        
        $type = $allSettings[$key]['type'];
        
        // Validate by type
        $validated = match ($type) {
            'decimal' => $request->validate(['value' => 'required|numeric']),
            'integer' => $request->validate(['value' => 'required|integer']),
            'boolean' => $request->validate(['value' => 'required|boolean']),
            'string' => $request->validate(['value' => 'required|string|max:500']),
            default => $request->validate(['value' => 'required'])
        };
        
        $this->settings->set($key, $validated['value'], $type);
        
        return response()->json([
            'success' => true,
            'message' => "Setting '{$key}' updated successfully",
        ]);
    }
    
    /**
     * Bulk update multiple settings
     */
    public function updateBulk(Request $request)
    {
        $this->authorize('admin');
        
        $updates = $request->input('updates', []);
        $allSettings = $this->settings->allWithMetadata();
        
        foreach ($updates as $key => $value) {
            if (isset($allSettings[$key])) {
                $type = $allSettings[$key]['type'];
                $this->settings->set($key, $value, $type);
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => count($updates) . ' settings updated successfully',
        ]);
    }
    
    /**
     * Show audit trail of setting changes
     */
    public function auditLog()
    {
        $changes = \App\Models\AuditLog::where('module', 'settings')
            ->with('user')
            ->latest()
            ->paginate(50);
        
        return view('admin.settings.audit', compact('changes'));
    }
}
