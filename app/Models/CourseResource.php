<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseResource extends Model
{
    protected $fillable = ['lesson_id', 'course_id', 'title', 'file_path', 'file_type', 'file_size'];

    public function lesson() { return $this->belongsTo(CourseLesson::class); }
    public function course() { return $this->belongsTo(Course::class); }
}
