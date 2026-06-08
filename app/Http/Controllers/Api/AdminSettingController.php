<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdminSetting;
use Illuminate\Http\Request;

class AdminSettingController extends Controller
{
    private array $defaults = [
        'critical_dispute_notifications' => true,
        'company_verification_notifications' => true,
    ];

    public function show()
    {
        return response()->json([
            'settings' => $this->settings(),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'critical_dispute_notifications' => ['required', 'boolean'],
            'company_verification_notifications' => ['required', 'boolean'],
        ]);

        foreach ($data as $key => $value) {
            AdminSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value ? '1' : '0']
            );
        }

        return response()->json([
            'message' => 'Settings saved successfully.',
            'settings' => $this->settings(),
        ]);
    }

    private function settings(): array
    {
        $settings = [];

        foreach ($this->defaults as $key => $default) {
            $setting = AdminSetting::firstOrCreate(
                ['key' => $key],
                ['value' => $default ? '1' : '0']
            );

            $settings[$key] = $setting->value === '1';
        }

        return $settings;
    }
}
