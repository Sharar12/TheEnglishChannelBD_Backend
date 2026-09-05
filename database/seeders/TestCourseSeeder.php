<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\CourseLesson;
use App\Models\CourseQuiz;
use App\Models\CourseQuizQuestion;

class TestCourseSeeder extends Seeder
{
    public function run(): void
    {
        // Create test course
        $course = Course::create([
            'title' => 'Complete Web Development Bootcamp',
            'slug' => 'complete-web-development-bootcamp',
            'instructor' => 'Sharar Hossain',
            'description' => 'Learn HTML, CSS, JavaScript, React, Node.js and more. Build real-world projects and master modern web development from scratch.',
            'price' => 2999.00,
            'duration_hours' => 12,
            'lessons_count' => 8,
            'level' => 'beginner',
            'image' => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=800',
            'preview_video' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'is_featured' => true,
            'is_active' => true,
            'category' => 'programming',
        ]);

        // Section 1: HTML Basics
        $section1 = CourseSection::create([
            'course_id' => $course->id,
            'title' => 'HTML Fundamentals',
            'order' => 0,
        ]);

        // Lesson 1.1
        $lesson1_1 = CourseLesson::create([
            'section_id' => $section1->id,
            'title' => 'Introduction to HTML',
            'description' => 'Learn what HTML is and why it matters for web development.',
            'video_url' => 'https://sample-videos.com/video321/mp4/720/big_buck_bunny_720p_1mb.mp4',
            'duration_minutes' => 15,
            'order' => 0,
            'is_free_preview' => true,
        ]);

        // Lesson 1.1 Quiz
        $quiz1_1 = CourseQuiz::create([
            'course_id' => $course->id,
            'lesson_id' => $lesson1_1->id,
            'title' => 'HTML Basics Quiz',
            'order' => 0,
        ]);

        CourseQuizQuestion::create([
            'quiz_id' => $quiz1_1->id,
            'question' => 'What does HTML stand for?',
            'options' => json_encode([
                'Hyper Text Markup Language',
                'High Tech Modern Language',
                'Home Tool Markup Language',
                'Hyperlinks and Text Markup Language',
            ]),
            'correct_answer' => 0,
            'order' => 0,
        ]);

        CourseQuizQuestion::create([
            'quiz_id' => $quiz1_1->id,
            'question' => 'Which tag is used for the largest heading?',
            'options' => json_encode([
                '<heading>',
                '<h6>',
                '<h1>',
                '<head>',
            ]),
            'correct_answer' => 2,
            'order' => 1,
        ]);

        CourseQuizQuestion::create([
            'quiz_id' => $quiz1_1->id,
            'question' => 'Which HTML element is used for paragraphs?',
            'options' => json_encode([
                '<para>',
                '<paragraph>',
                '<text>',
                '<p>',
            ]),
            'correct_answer' => 3,
            'order' => 2,
        ]);

        // Lesson 1.2
        $lesson1_2 = CourseLesson::create([
            'section_id' => $section1->id,
            'title' => 'HTML Elements and Attributes',
            'description' => 'Understanding HTML elements, tags, and attributes.',
            'video_url' => 'https://sample-videos.com/video321/mp4/720/big_buck_bunny_720p_1mb.mp4',
            'duration_minutes' => 20,
            'order' => 1,
            'is_free_preview' => false,
        ]);

        // Lesson 1.2 Quiz
        $quiz1_2 = CourseQuiz::create([
            'course_id' => $course->id,
            'lesson_id' => $lesson1_2->id,
            'title' => 'Elements & Attributes Quiz',
            'order' => 0,
        ]);

        CourseQuizQuestion::create([
            'quiz_id' => $quiz1_2->id,
            'question' => 'Which attribute specifies the URL for a link?',
            'options' => json_encode([
                'src',
                'link',
                'href',
                'url',
            ]),
            'correct_answer' => 2,
            'order' => 0,
        ]);

        CourseQuizQuestion::create([
            'quiz_id' => $quiz1_2->id,
            'question' => 'Which tag is used to create a hyperlink?',
            'options' => json_encode([
                '<link>',
                '<a>',
                '<href>',
                '<url>',
            ]),
            'correct_answer' => 1,
            'order' => 1,
        ]);

        // Section 2: CSS Styling
        $section2 = CourseSection::create([
            'course_id' => $course->id,
            'title' => 'CSS Styling',
            'order' => 1,
        ]);

        // Lesson 2.1
        $lesson2_1 = CourseLesson::create([
            'section_id' => $section2->id,
            'title' => 'CSS Selectors and Properties',
            'description' => 'Learn how to select and style HTML elements with CSS.',
            'video_url' => 'https://sample-videos.com/video321/mp4/720/big_buck_bunny_720p_1mb.mp4',
            'duration_minutes' => 25,
            'order' => 0,
            'is_free_preview' => false,
        ]);

        // Lesson 2.1 Quiz
        $quiz2_1 = CourseQuiz::create([
            'course_id' => $course->id,
            'lesson_id' => $lesson2_1->id,
            'title' => 'CSS Selectors Quiz',
            'order' => 0,
        ]);

        CourseQuizQuestion::create([
            'quiz_id' => $quiz2_1->id,
            'question' => 'Which CSS property changes text color?',
            'options' => json_encode([
                'text-color',
                'font-color',
                'color',
                'text-style',
            ]),
            'correct_answer' => 2,
            'order' => 0,
        ]);

        CourseQuizQuestion::create([
            'quiz_id' => $quiz2_1->id,
            'question' => 'Which selector targets elements by their class?',
            'options' => json_encode([
                '#classname',
                '.classname',
                '*classname',
                '@classname',
            ]),
            'correct_answer' => 1,
            'order' => 1,
        ]);

        // Lesson 2.2
        $lesson2_2 = CourseLesson::create([
            'section_id' => $section2->id,
            'title' => 'Flexbox and Grid Layout',
            'description' => 'Master modern CSS layout techniques with Flexbox and Grid.',
            'video_url' => 'https://sample-videos.com/video321/mp4/720/big_buck_bunny_720p_1mb.mp4',
            'duration_minutes' => 30,
            'order' => 1,
            'is_free_preview' => false,
        ]);

        // Lesson 2.2 Quiz
        $quiz2_2 = CourseQuiz::create([
            'course_id' => $course->id,
            'lesson_id' => $lesson2_2->id,
            'title' => 'Flexbox & Grid Quiz',
            'order' => 0,
        ]);

        CourseQuizQuestion::create([
            'quiz_id' => $quiz2_2->id,
            'question' => 'Which property creates a flex container?',
            'options' => json_encode([
                'display: block',
                'display: flex',
                'display: grid',
                'display: inline',
            ]),
            'correct_answer' => 1,
            'order' => 0,
        ]);

        CourseQuizQuestion::create([
            'quiz_id' => $quiz2_2->id,
            'question' => 'Which property aligns items along the main axis in flexbox?',
            'options' => json_encode([
                'align-items',
                'justify-content',
                'flex-direction',
                'align-content',
            ]),
            'correct_answer' => 1,
            'order' => 1,
        ]);

        // Section 3: JavaScript Fundamentals
        $section3 = CourseSection::create([
            'course_id' => $course->id,
            'title' => 'JavaScript Fundamentals',
            'order' => 2,
        ]);

        // Lesson 3.1
        $lesson3_1 = CourseLesson::create([
            'section_id' => $section3->id,
            'title' => 'Variables and Data Types',
            'description' => 'Learn about JavaScript variables, constants, and data types.',
            'video_url' => 'https://sample-videos.com/video321/mp4/720/big_buck_bunny_720p_1mb.mp4',
            'duration_minutes' => 20,
            'order' => 0,
            'is_free_preview' => false,
        ]);

        // Lesson 3.1 Quiz
        $quiz3_1 = CourseQuiz::create([
            'course_id' => $course->id,
            'lesson_id' => $lesson3_1->id,
            'title' => 'Variables & Types Quiz',
            'order' => 0,
        ]);

        CourseQuizQuestion::create([
            'quiz_id' => $quiz3_1->id,
            'question' => 'Which keyword declares a block-scoped variable?',
            'options' => json_encode([
                'var',
                'let',
                'define',
                'variable',
            ]),
            'correct_answer' => 1,
            'order' => 0,
        ]);

        CourseQuizQuestion::create([
            'quiz_id' => $quiz3_1->id,
            'question' => 'What is the output of typeof null?',
            'options' => json_encode([
                'null',
                'undefined',
                'object',
                'number',
            ]),
            'correct_answer' => 2,
            'order' => 1,
        ]);

        CourseQuizQuestion::create([
            'quiz_id' => $quiz3_1->id,
            'question' => 'Which is NOT a JavaScript primitive type?',
            'options' => json_encode([
                'string',
                'boolean',
                'array',
                'symbol',
            ]),
            'correct_answer' => 2,
            'order' => 2,
        ]);

        // Lesson 3.2
        $lesson3_2 = CourseLesson::create([
            'section_id' => $section3->id,
            'title' => 'Functions and Scope',
            'description' => 'Understanding functions, parameters, and variable scope in JavaScript.',
            'video_url' => 'https://sample-videos.com/video321/mp4/720/big_buck_bunny_720p_1mb.mp4',
            'duration_minutes' => 25,
            'order' => 1,
            'is_free_preview' => false,
        ]);

        // Lesson 3.2 Quiz
        $quiz3_2 = CourseQuiz::create([
            'course_id' => $course->id,
            'lesson_id' => $lesson3_2->id,
            'title' => 'Functions Quiz',
            'order' => 0,
        ]);

        CourseQuizQuestion::create([
            'quiz_id' => $quiz3_2->id,
            'question' => 'What is a closure in JavaScript?',
            'options' => json_encode([
                'A way to close the browser',
                'A function with access to its outer scope',
                'A method to end a loop',
                'A type of variable',
            ]),
            'correct_answer' => 1,
            'order' => 0,
        ]);

        CourseQuizQuestion::create([
            'quiz_id' => $quiz3_2->id,
            'question' => 'Which is an arrow function syntax?',
            'options' => json_encode([
                'function => ()',
                '() -> {}',
                '() => {}',
                '=> function()',
            ]),
            'correct_answer' => 2,
            'order' => 1,
        ]);

        // Final Assessment Quizzes (course-level, no lesson_id)
        $finalQuiz1 = CourseQuiz::create([
            'course_id' => $course->id,
            'lesson_id' => null,
            'title' => 'Final Assessment: HTML & CSS',
            'order' => 0,
        ]);

        CourseQuizQuestion::create([
            'quiz_id' => $finalQuiz1->id,
            'question' => 'Which HTML5 element is used for navigation links?',
            'options' => json_encode([
                '<navigation>',
                '<nav>',
                '<menu>',
                '<links>',
            ]),
            'correct_answer' => 1,
            'order' => 0,
        ]);

        CourseQuizQuestion::create([
            'quiz_id' => $finalQuiz1->id,
            'question' => 'Which CSS property creates space inside an element?',
            'options' => json_encode([
                'margin',
                'border',
                'padding',
                'spacing',
            ]),
            'correct_answer' => 2,
            'order' => 1,
        ]);

        CourseQuizQuestion::create([
            'quiz_id' => $finalQuiz1->id,
            'question' => 'What does the z-index property control?',
            'options' => json_encode([
                'Text size',
                'Stacking order of elements',
                'Zoom level',
                'Element opacity',
            ]),
            'correct_answer' => 1,
            'order' => 2,
        ]);

        CourseQuizQuestion::create([
            'quiz_id' => $finalQuiz1->id,
            'question' => 'Which CSS unit is relative to the viewport width?',
            'options' => json_encode([
                'px',
                'em',
                'rem',
                'vw',
            ]),
            'correct_answer' => 3,
            'order' => 3,
        ]);

        $finalQuiz2 = CourseQuiz::create([
            'course_id' => $course->id,
            'lesson_id' => null,
            'title' => 'Final Assessment: JavaScript',
            'order' => 1,
        ]);

        CourseQuizQuestion::create([
            'quiz_id' => $finalQuiz2->id,
            'question' => 'What does JSON stand for?',
            'options' => json_encode([
                'JavaScript Object Notation',
                'Java Standard Output Network',
                'JavaScript Oriented Naming',
                'Java Serialized Object Network',
            ]),
            'correct_answer' => 0,
            'order' => 0,
        ]);

        CourseQuizQuestion::create([
            'quiz_id' => $finalQuiz2->id,
            'question' => 'Which method converts a JSON string to a JavaScript object?',
            'options' => json_encode([
                'JSON.stringify()',
                'JSON.parse()',
                'JSON.toObject()',
                'JSON.convert()',
            ]),
            'correct_answer' => 1,
            'order' => 1,
        ]);

        CourseQuizQuestion::create([
            'quiz_id' => $finalQuiz2->id,
            'question' => 'What is the purpose of the "async" keyword?',
            'options' => json_encode([
                'To make a function run faster',
                'To declare an asynchronous function that returns a Promise',
                'To run code in parallel',
                'To stop execution until a condition is met',
            ]),
            'correct_answer' => 1,
            'order' => 2,
        ]);

        CourseQuizQuestion::create([
            'quiz_id' => $finalQuiz2->id,
            'question' => 'Which array method creates a new array with elements that pass a test?',
            'options' => json_encode([
                'map()',
                'forEach()',
                'filter()',
                'reduce()',
            ]),
            'correct_answer' => 2,
            'order' => 3,
        ]);

        echo "Test course created successfully!\n";
        echo "Course ID: {$course->id}\n";
        echo "Title: {$course->title}\n";
        echo "Sections: 3\n";
        echo "Lessons: 6\n";
        echo "Lesson Quizzes: 6\n";
        echo "Final Quizzes: 2\n";
    }
}
