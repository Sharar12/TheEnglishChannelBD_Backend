<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;

class SiteSettingsController extends Controller
{
    public function show()
    {
        $settings = SiteSetting::first();
        
        if (!$settings) {
            $settings = SiteSetting::create([
                'bkash_number' => '',
                'nagad_number' => '',
                'cod_charge' => 0,
                'bkash_discount_percent' => 0,
                'nagad_discount_percent' => 0,
            ]);
        }
        
        return response()->json($settings);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'bkash_number' => 'nullable|string|max:20',
            'nagad_number' => 'nullable|string|max:20',
            'cod_charge' => 'nullable|numeric|min:0',
            'bkash_discount_percent' => 'nullable|numeric|min:0|max:100',
            'nagad_discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $settings = SiteSetting::first();
        
        if (!$settings) {
            $settings = SiteSetting::create($validated);
        } else {
            $settings->update($validated);
        }

        return response()->json([
            'settings' => $settings,
            'message' => 'Payment settings updated successfully',
        ]);
    }
}
