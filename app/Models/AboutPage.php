<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AboutPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'hero_description',
        'our_story',
        'our_mission',
        'our_values',
        'contact_email',
        'contact_phone',
        'contact_address',
    ];
}
