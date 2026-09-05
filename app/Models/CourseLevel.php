<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseLevel extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'order'];

    public function courses()
    {
        return $this->hasMany(Course::class, 'level', 'slug');
    }
}