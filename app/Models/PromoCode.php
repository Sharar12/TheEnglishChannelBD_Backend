<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'usage_limit',
        'usage_count',
        'per_user_limit',
        'min_order_amount',
        'valid_from',
        'valid_until',
        'is_active',
        'description',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'usage_limit' => 'integer',
        'usage_count' => 'integer',
        'per_user_limit' => 'integer',
        'min_order_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function usages()
    {
        return $this->hasMany(PromoCodeUsage::class);
    }

    public function isValid($userId = null)
    {
        if (!$this->is_active) {
            return ['valid' => false, 'message' => 'This promo code is inactive'];
        }

        $now = now();
        if ($this->valid_from && $now->lt($this->valid_from)) {
            return ['valid' => false, 'message' => 'This promo code is not yet valid'];
        }

        if ($this->valid_until && $now->gt($this->valid_until)) {
            return ['valid' => false, 'message' => 'This promo code has expired'];
        }

        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) {
            return ['valid' => false, 'message' => 'This promo code has reached its usage limit'];
        }

        if ($userId) {
            $userUsageCount = $this->usages()->where('user_id', $userId)->count();
            if ($userUsageCount >= $this->per_user_limit) {
                return ['valid' => false, 'message' => 'You have already used this promo code'];
            }
        }

        return ['valid' => true, 'message' => 'Promo code is valid'];
    }

    public function calculateDiscount($orderTotal)
    {
        if ($this->discount_type === 'percentage') {
            return ($orderTotal * $this->discount_value) / 100;
        }
        return min($this->discount_value, $orderTotal);
    }

    public function recordUsage($userId, $orderId, $discountGiven)
    {
        $this->usages()->create([
            'user_id' => $userId,
            'order_id' => $orderId,
            'discount_given' => $discountGiven,
        ]);
        
        $this->increment('usage_count');
    }

    public function resetUserUsage($userId)
    {
        $usage = $this->usages()->where('user_id', $userId)->first();
        if ($usage) {
            $usage->delete();
            $this->decrement('usage_count');
            return true;
        }
        return false;
    }

    public function getUserUsages()
    {
        return $this->usages()->with('user:id,name,email')->get();
    }
}