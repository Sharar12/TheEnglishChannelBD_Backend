<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'book_id',
        'user_id',
        'user_name',
        'user_email',
        'question',
        'answer',
        'is_answered',
        'is_approved',
    ];

    protected $casts = [
        'is_answered' => 'boolean',
        'is_approved' => 'boolean',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
