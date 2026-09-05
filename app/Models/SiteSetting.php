<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'bkash_number',
        'nagad_number',
        'cod_charge',
        'bkash_discount_percent',
        'nagad_discount_percent',
    ];

    protected $casts = [
        'cod_charge' => 'decimal:2',
        'bkash_discount_percent' => 'decimal:2',
        'nagad_discount_percent' => 'decimal:2',
    ];
}
