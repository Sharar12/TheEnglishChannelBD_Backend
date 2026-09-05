<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CourseProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourseProgressController extends Controller
{
    public function index(Request $request, $courseId)
    {
        $user = $request->user();
        $progress = CourseProgress::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->get();

        return response()->json($progress);
    }

    public function store(Request $request, $courseId)
    {
        $user = $request->user();

        // Check for bulk format (from frontend)
        $completedLessons = $request->input('completed_lessons', []);
        $watchedLessons = $request->input('watched_lessons', []);
        $videoProgress = $request->input('video_progress', []);
        $lessonQuizScores = $request->input('lesson_quiz_scores', []);
        $finalQuizScores = $request->input('final_quiz_scores', []);

        if (!empty($completedLessons) || !empty($watchedLessons) || !empty($videoProgress) || !empty($lessonQuizScores) || !empty($finalQuizScores)) {
            // Bulk format - save each completed lesson and quiz score
            DB::transaction(function () use ($user, $courseId, $completedLessons, $watchedLessons, $videoProgress, $lessonQuizScores, $finalQuizScores) {
                // Combine unique lesson IDs from all inputs
                $lessonIds = array_unique(array_merge($completedLessons, $watchedLessons, array_keys($videoProgress)));

                foreach ($lessonIds as $lessonId) {
                    CourseProgress::updateOrCreate(
                        ['user_id' => $user->id, 'course_id' => $courseId, 'lesson_id' => $lessonId],
                        [
                            'completed' => in_array($lessonId, $completedLessons),
                            'watched' => in_array($lessonId, $watchedLessons),
                            'video_seconds' => $videoProgress[$lessonId] ?? null,
                        ]
                    );
                }

                // Save lesson quiz scores
                foreach ($lessonQuizScores as $quizId => $score) {
                    CourseProgress::updateOrCreate(
                        ['user_id' => $user->id, 'course_id' => $courseId, 'quiz_id' => $quizId],
                        ['score' => $score, 'completed' => $score >= 70]
                    );
                }

                // Save final quiz scores
                foreach ($finalQuizScores as $quizId => $score) {
                    CourseProgress::updateOrCreate(
                        ['user_id' => $user->id, 'course_id' => $courseId, 'quiz_id' => $quizId],
                        ['score' => $score, 'completed' => $score >= 70]
                    );
                }
            });

            return response()->json(['message' => 'Progress saved']);
        }

        // Single record format (legacy)
        $validated = $request->validate([
            'lesson_id' => 'nullable|integer|exists:course_lessons,id',
            'quiz_id' => 'nullable|integer|exists:course_quizzes,id',
            'completed' => 'boolean',
            'score' => 'nullable|integer|min:0|max:100',
        ]);

        if ($validated['lesson_id'] && $validated['quiz_id']) {
            return response()->json(['error' => 'Cannot set both lesson and quiz'], 400);
        }

        $progress = CourseProgress::updateOrCreate(
            [
                'user_id' => $user->id,
                'course_id' => $courseId,
                'lesson_id' => $validated['lesson_id'] ?? null,
                'quiz_id' => $validated['quiz_id'] ?? null,
            ],
            [
                'completed' => $validated['completed'] ?? false,
                'score' => $validated['score'] ?? null,
            ]
        );

        return response()->json($progress);
    }
}
