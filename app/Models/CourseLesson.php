<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseLesson extends Model
{
    protected $fillable = ['section_id', 'title', 'type', 'description', 'video_url', 'video_file', 'duration_minutes', 'order', 'is_free_preview'];

    protected $casts = [
        'is_free_preview' => 'boolean',
        'duration_minutes' => 'integer',
        'order' => 'integer',
    ];

    public function section() { return $this->belongsTo(CourseSection::class); }
    public function resources() { return $this->hasMany(CourseResource::class, 'lesson_id'); }
    public function quizzes() { return $this->hasMany(CourseQuiz::class, 'lesson_id')->orderBy('order'); }
}
