<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'total',
        'status',
        'tracking_number',
        'payment_method',
        'payment_mobile',
        'transaction_id',
        'discount_amount',
        'cod_charge',
        'shipping_address',
        'city',
        'state',
        'postal_code',
        'phone',
        'notes',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'cod_charge' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
