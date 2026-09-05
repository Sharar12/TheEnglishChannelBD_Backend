<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseQuestion;
use Illuminate\Http\Request;

class CourseQuestionController extends Controller
{
    public function index($slug)
    {
        $course = \App\Models\Course::where('slug', $slug)->firstOrFail();
        
        $questions = CourseQuestion::with('user')
            ->where('course_id', $course->id)
            ->where('is_approved', true)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($question) {
                return [
                    'id' => $question->id,
                    'course_id' => $question->course_id,
                    'user_id' => $question->user_id,
                    'user_name' => $question->user ? $question->user->name : $question->user_name,
                    'user_email' => $question->user ? $question->user->email : $question->user_email,
                    'question' => $question->question,
                    'answer' => $question->answer,
                    'is_answered' => $question->is_answered,
                    'is_approved' => $question->is_approved,
                    'created_at' => $question->created_at,
                ];
            });

        return response()->json([
            'questions' => $questions,
            'total' => $questions->count(),
        ]);
    }

    public function store(Request $request, $courseSlug)
    {
        $course = \App\Models\Course::where('slug', $courseSlug)->firstOrFail();

        $validated = $request->validate([
            'user_name' => 'sometimes|string|max:255',
            'user_email' => 'nullable|email|max:255',
            'question' => 'required|string|max:1000',
        ]);

        $user = $request->user();

        $question = CourseQuestion::create([
            'course_id' => $course->id,
            'user_id' => $user?->id,
            'user_name' => $user ? $user->name : ($validated['user_name'] ?? 'Anonymous'),
            'user_email' => $user ? $user->email : ($validated['user_email'] ?? null),
            'question' => $validated['question'],
            'is_answered' => false,
            'is_approved' => true,
        ]);

        return response()->json([
            'question' => $question,
            'message' => 'Question submitted successfully',
        ], 201);
    }

    public function answer(Request $request, $id)
    {
        $validated = $request->validate([
            'answer' => 'required|string|max:2000',
        ]);

        $question = CourseQuestion::findOrFail($id);
        $question->update([
            'answer' => $validated['answer'],
            'is_answered' => true,
        ]);

        return response()->json([
            'question' => $question,
            'message' => 'Answer submitted successfully',
        ]);
    }
}
