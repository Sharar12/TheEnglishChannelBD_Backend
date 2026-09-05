<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
        $query = Course::where('is_active', true);

        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        if ($request->has('level') && $request->level !== 'all') {
            $query->where('level', $request->level);
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('instructor', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('featured') && $request->featured === 'true') {
            $query->where('is_featured', true);
        }

        $sortBy = $request->get('sort', 'newest');
        switch ($sortBy) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'featured':
                $query->orderBy('is_featured', 'desc')->orderBy('created_at', 'desc');
                break;
            case 'price_low':
                $query->orderByRaw('CAST(price AS DECIMAL) ASC');
                break;
            case 'price_high':
                $query->orderByRaw('CAST(price AS DECIMAL) DESC');
                break;
            case 'popular':
                $query->orderByDesc(\DB::raw('(SELECT COUNT(*) FROM order_items WHERE order_items.course_id = courses.id)'));
                break;
            case 'rating':
                $query->orderByDesc(\DB::raw('(SELECT AVG(rating) FROM course_reviews WHERE course_reviews.course_id = courses.id)'));
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy('is_featured', 'desc')->orderBy('created_at', 'desc');
        }

        $perPage = min((int) $request->query('per_page', 100), 200);
        $courses = $query->paginate($perPage);

        // Add review stats and enrollment count
        $courses->getCollection()->transform(function ($course) {
            $course->average_rating = round($course->reviews()->where('is_approved', true)->avg('rating') ?? 0, 1);
            $course->reviews_count = $course->reviews()->where('is_approved', true)->count();
            $course->enrolled_count = \App\Models\OrderItem::where('course_id', $course->id)
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->whereIn('orders.status', ['delivered', 'completed'])
                ->count();
            return $course;
        });

        return response()->json($courses);
    }

    public function show($slug)
    {
        $course = Course::with([
            'sections.lessons.resources',
            'sections.lessons.quizzes.questions',
            'quizzes.questions',
        ])->where('slug', $slug)->where('is_active', true)->firstOrFail();

        // Add review stats and enrollment count
        $course->average_rating = round($course->reviews()->where('is_approved', true)->avg('rating') ?? 0, 1);
        $course->reviews_count = $course->reviews()->where('is_approved', true)->count();
        $course->enrolled_count = \App\Models\OrderItem::where('course_id', $course->id)
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->whereIn('orders.status', ['delivered', 'completed'])
            ->count();

        return response()->json($course);
    }

    public function categories()
    {
        $categories = \App\Models\Category::where('type', 'course')
            ->withCount(['courses' => function ($q) {
                $q->where('is_active', true);
            }])
            ->orderBy('name')
            ->get()
            ->map(function ($cat) {
                return [
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'count' => $cat->courses_count,
                ];
            });

        return response()->json($categories);
    }

    public function levels()
    {
        $levels = \App\Models\CourseLevel::orderBy('order', 'asc')->get(['id', 'name', 'slug']);
        return response()->json($levels);
    }
}
