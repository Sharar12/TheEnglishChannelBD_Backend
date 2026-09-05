<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookCourseAccess extends Model
{
    protected $table = 'book_course_access';

    protected $fillable = [
        'book_id',
        'course_id',
        'access_type',
        'discount_percent',
    ];

    protected $casts = [
        'discount_percent' => 'integer',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
