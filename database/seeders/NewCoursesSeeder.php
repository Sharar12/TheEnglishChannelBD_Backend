<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class NewCoursesSeeder extends Seeder
{
    public function run(): void
    {
        $courses = [
            [
                'title' => 'Advanced English Grammar',
                'slug' => 'advanced-english-grammar',
                'instructor' => 'Michael Brown',
                'description' => 'Master English grammar rules.',
                'price' => 49.99,
                'duration_hours' => 12,
                'lessons_count' => 36,
                'level' => 'Intermediate',
                'status' => 'published',
                'image' => 'courses/grammar.jpg',
                'preview_video' => 'https://www.youtube.com/embed/2z7f5Xz7v0I',
                'is_featured' => false,
                'is_active' => true,
                'category' => 'language',
            ],
            [
                'title' => 'Creative Storytelling',
                'slug' => 'creative-storytelling',
                'instructor' => 'Emma Watson',
                'description' => 'Learn storytelling techniques.',
                'price' => 39.99,
                'duration_hours' => 8,
                'lessons_count' => 24,
                'level' => 'Beginner',
                'status' => 'published',
                'image' => 'courses/story.jpg',
                'preview_video' => 'https://www.youtube.com/embed/Nj-hdQMa3uA',
                'is_featured' => false,
                'is_active' => true,
                'category' => 'writing',
            ],
            [
                'title' => 'Quantum Physics Basics',
                'slug' => 'quantum-physics-basics',
                'instructor' => 'Dr. Brian Cox',
                'description' => 'Intro to quantum world.',
                'price' => 59.99,
                'duration_hours' => 10,
                'lessons_count' => 30,
                'level' => 'Beginner',
                'status' => 'published',
                'image' => 'courses/quantum.jpg',
                'preview_video' => 'https://www.youtube.com/embed/p7bzE1E5PMY',
                'is_featured' => false,
                'is_active' => true,
                'category' => 'science',
            ],
            [
                'title' => 'Space Science & Astronomy',
                'slug' => 'space-science-astronomy',
                'instructor' => 'Neil Tyson',
                'description' => 'Explore the universe.',
                'price' => 44.99,
                'duration_hours' => 9,
                'lessons_count' => 27,
                'level' => 'Beginner',
                'status' => 'published',
                'image' => 'courses/space.jpg',
                'preview_video' => 'https://www.youtube.com/embed/X9otDixAtFw',
                'is_featured' => false,
                'is_active' => true,
                'category' => 'science',
            ],
            [
                'title' => 'Java DSA Bootcamp',
                'slug' => 'java-dsa-bootcamp',
                'instructor' => 'Sarah Lee',
                'description' => 'Data structures using Java.',
                'price' => 79.99,
                'duration_hours' => 18,
                'lessons_count' => 54,
                'level' => 'Advanced',
                'status' => 'published',
                'image' => 'courses/dsa.jpg',
                'preview_video' => 'https://www.youtube.com/embed/8hly31xKli0',
                'is_featured' => false,
                'is_active' => true,
                'category' => 'technology',
            ],
            [
                'title' => 'Full Stack Development',
                'slug' => 'full-stack-development',
                'instructor' => 'Alex Morgan',
                'description' => 'Frontend + backend complete.',
                'price' => 89.99,
                'duration_hours' => 20,
                'lessons_count' => 60,
                'level' => 'Intermediate',
                'status' => 'published',
                'image' => 'courses/fullstack.jpg',
                'preview_video' => 'https://www.youtube.com/embed/nu_pCVPKzTk',
                'is_featured' => false,
                'is_active' => true,
                'category' => 'technology',
            ],
        ];

        foreach ($courses as $course) {
            Course::updateOrCreate(['slug' => $course['slug']], $course);
        }
    }
}
