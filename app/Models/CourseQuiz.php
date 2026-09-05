<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseQuiz extends Model
{
    protected $fillable = ['course_id', 'lesson_id', 'title', 'order'];

    public function course() { return $this->belongsTo(Course::class); }
    public function lesson() { return $this->belongsTo(CourseLesson::class, 'lesson_id'); }
    public function questions() { return $this->hasMany(CourseQuizQuestion::class, 'quiz_id')->orderBy('order'); }
}
