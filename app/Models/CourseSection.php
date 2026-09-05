<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseSection extends Model
{
    protected $fillable = ['course_id', 'title', 'order'];

    public function course() { return $this->belongsTo(Course::class); }
    public function lessons() { return $this->hasMany(CourseLesson::class, 'section_id')->orderBy('order'); }
}
