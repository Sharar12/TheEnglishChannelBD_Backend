<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GalleryPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'image_path',
        'order',
        'is_active',
        'date',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
        'date' => 'date',
    ];
}
