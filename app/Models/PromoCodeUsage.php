<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoCodeUsage extends Model
{
    protected $table = 'promo_code_usages';

    protected $fillable = [
        'promo_code_id',
        'user_id',
        'order_id',
        'discount_given',
    ];

    protected $casts = [
        'discount_given' => 'decimal:2',
    ];

    public function promoCode()
    {
        return $this->belongsTo(PromoCode::class, 'promo_code_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}