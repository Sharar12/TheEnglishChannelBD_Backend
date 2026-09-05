<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseProgress extends Model
{
    protected $fillable = ['user_id', 'course_id', 'lesson_id', 'quiz_id', 'completed', 'watched', 'video_seconds', 'score'];

    protected $casts = [
        'completed' => 'boolean',
        'watched' => 'boolean',
        'video_seconds' => 'integer',
        'score' => 'integer',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function course() { return $this->belongsTo(Course::class); }
    public function lesson() { return $this->belongsTo(CourseLesson::class, 'lesson_id'); }
    public function quiz() { return $this->belongsTo(CourseQuiz::class, 'quiz_id'); }
}
