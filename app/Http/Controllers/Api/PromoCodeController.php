<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use Illuminate\Http\Request;

class PromoCodeController extends Controller
{
    public function index()
    {
        $promoCodes = PromoCode::orderBy('created_at', 'desc')->get();
        
        $promoCodes->transform(function ($code) {
            $code->user_usages = $code->usages()->with('user:id,name,email')->get()->map(function ($usage) {
                return [
                    'id' => $usage->id,
                    'user_id' => $usage->user_id,
                    'user_name' => $usage->user->name ?? 'Unknown',
                    'user_email' => $usage->user->email ?? 'Unknown',
                    'order_id' => $usage->order_id,
                    'discount_given' => $usage->discount_given,
                    'used_at' => $usage->created_at,
                ];
            });
            return $code;
        });

        return response()->json($promoCodes);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:promo_codes',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'per_user_limit' => 'nullable|integer|min:1',
            'min_order_amount' => 'nullable|numeric|min:0',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'is_active' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        $promoCode = PromoCode::create([
            'code' => strtoupper($validated['code']),
            'discount_type' => $validated['discount_type'],
            'discount_value' => $validated['discount_value'],
            'usage_limit' => $validated['usage_limit'] ?? 1,
            'per_user_limit' => $validated['per_user_limit'] ?? 1,
            'min_order_amount' => $validated['min_order_amount'] ?? null,
            'valid_from' => $validated['valid_from'] ?? null,
            'valid_until' => $validated['valid_until'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json($promoCode, 201);
    }

    public function update(Request $request, $id)
    {
        $promoCode = PromoCode::findOrFail($id);

        $validated = $request->validate([
            'code' => 'sometimes|string|max:50|unique:promo_codes,code,' . $id,
            'discount_type' => 'sometimes|in:percentage,fixed',
            'discount_value' => 'sometimes|numeric|min:0',
            'usage_limit' => 'sometimes|integer|min:1',
            'per_user_limit' => 'sometimes|integer|min:1',
            'min_order_amount' => 'nullable|numeric|min:0',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'is_active' => 'sometimes|boolean',
            'description' => 'nullable|string',
        ]);

        if (isset($validated['code'])) {
            $validated['code'] = strtoupper($validated['code']);
        }

        $promoCode->update($validated);

        return response()->json($promoCode);
    }

    public function destroy($id)
    {
        $promoCode = PromoCode::findOrFail($id);
        $promoCode->delete();

        return response()->json(['message' => 'Promo code deleted successfully']);
    }

    public function resetUserUsage(Request $request, $id)
    {
        $promoCode = PromoCode::findOrFail($id);
        
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $reset = $promoCode->resetUserUsage($validated['user_id']);

        if ($reset) {
            return response()->json(['message' => 'User usage reset successfully']);
        }

        return response()->json(['message' => 'No usage found for this user'], 404);
    }

    public function validate(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'order_total' => 'required|numeric|min:0',
        ]);

        $promoCode = PromoCode::where('code', strtoupper($validated['code']))->first();

        if (!$promoCode) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid promo code',
            ], 404);
        }

        $validation = $promoCode->isValid(auth()->id());

        if (!$validation['valid']) {
            return response()->json($validation);
        }

        if ($promoCode->min_order_amount && $validated['order_total'] < $promoCode->min_order_amount) {
            return response()->json([
                'valid' => false,
                'message' => 'Minimum order amount of ৳' . number_format($promoCode->min_order_amount, 2) . ' required',
            ]);
        }

        $discount = $promoCode->calculateDiscount($validated['order_total']);

        return response()->json([
            'valid' => true,
            'message' => 'Promo code applied',
            'promo_code' => [
                'id' => $promoCode->id,
                'code' => $promoCode->code,
                'discount_type' => $promoCode->discount_type,
                'discount_value' => $promoCode->discount_value,
            ],
            'discount_amount' => $discount,
        ]);
    }

    public function toggleStatus($id)
    {
        $promoCode = PromoCode::findOrFail($id);
        $promoCode->update(['is_active' => !$promoCode->is_active]);

        return response()->json($promoCode);
    }
}
