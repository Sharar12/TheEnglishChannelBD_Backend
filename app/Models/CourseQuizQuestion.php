<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseQuizQuestion extends Model
{
    protected $fillable = ['quiz_id', 'question', 'options', 'correct_answer', 'order'];

    protected $casts = [
        'options' => 'array',
        'correct_answer' => 'integer',
        'order' => 'integer',
    ];

    public function quiz() { return $this->belongsTo(CourseQuiz::class); }
}
