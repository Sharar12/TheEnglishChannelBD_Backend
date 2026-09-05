<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\AboutController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\CourseReviewController;
use App\Http\Controllers\Api\CourseQuestionController;
use App\Http\Controllers\Api\VideoStreamController;
use App\Http\Controllers\Api\GalleryController;
use App\Http\Controllers\Api\SiteSettingsController;
use App\Http\Controllers\Api\PromoCodeController;
use App\Http\Controllers\Api\CourseProgressController;
use App\Http\Controllers\Api\PdfController;

Route::get('/test', function () {
    return response()->json(['message' => 'The English Channel BD API is running!']);
});

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::get('/books', [BookController::class, 'index']);

Route::get('/books/{id}', [BookController::class, 'show']);

Route::prefix('books')->group(function () {
    Route::get('/{id}/reviews', [ReviewController::class, 'index']);
    Route::post('/{id}/reviews', [ReviewController::class, 'store']);
    Route::get('/{id}/questions', [QuestionController::class, 'index']);
    Route::post('/{id}/questions', [QuestionController::class, 'store']);
});

Route::get('/categories', [BookController::class, 'categories']);

Route::get('/about', [AboutController::class, 'show']);

Route::get('/stats', function() {
    $totalBooks = \DB::table('books')->where('status', 'approved')->count();
    $totalCourses = \DB::table('courses')->where('is_active', true)->count();
    $totalStudents = \DB::table('orders')
        ->join('order_items', 'orders.id', '=', 'order_items.order_id')
        ->whereIn('orders.status', ['delivered', 'completed'])
        ->distinct('orders.user_id')
        ->count('orders.user_id');
    $bookAvgRating = \DB::table('reviews')
        ->where('is_approved', true)
        ->avg('rating') ?? 0;
    $courseAvgRating = \DB::table('course_reviews')
        ->where('is_approved', true)
        ->avg('rating') ?? 0;
    
    return response()->json([
        'total_books' => $totalBooks,
        'total_courses' => $totalCourses,
        'total_students' => $totalStudents,
        'book_average_rating' => round($bookAvgRating, 1),
        'course_average_rating' => round($courseAvgRating, 1),
    ]);
});

Route::get('/site-settings', [SiteSettingsController::class, 'show']);

Route::get('/courses', [CourseController::class, 'index']);
Route::get('/courses/categories', [CourseController::class, 'categories']);
Route::get('/courses/levels', [CourseController::class, 'levels']);
Route::get('/courses/{slug}', [CourseController::class, 'show']);
Route::get('/courses/{slug}/reviews', [CourseReviewController::class, 'index']);
Route::post('/courses/{slug}/reviews', [CourseReviewController::class, 'store']);
Route::put('/reviews/{id}', [CourseReviewController::class, 'update']);
Route::get('/courses/{slug}/questions', [CourseQuestionController::class, 'index']);
Route::post('/courses/{slug}/questions', [CourseQuestionController::class, 'store']);

Route::get('/gallery', [GalleryController::class, 'index']);

Route::get('/video/stream/{path}', [VideoStreamController::class, 'stream'])->where('path', '.*');

// Public storage file serving (no auth required)
Route::get('/storage/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);
    if (!file_exists($fullPath)) {
        return response()->json(['error' => 'File not found'], 404);
    }
    return response()->file($fullPath);
})->where('path', '.*');

// Wishlist routes (public - works for guests too)
Route::prefix('wishlist')->group(function () {
    Route::get('/check', [WishlistController::class, 'check']);
});

// Debug route - no auth required
Route::get('/debug/dashboard', function() {
    try {
        $totalBooks = \DB::table('books')->count();
        $warehouseStock = \DB::table('books')->sum('stock');
        $bookCategories = \DB::table('categories')->count();
        $totalRevenue = \DB::table('orders')->sum('total') ?? 0;
        $totalCourses = \DB::table('courses')->count();
        
        return response()->json([
            'stats' => [
                'total_books' => $totalBooks,
                'warehouse_stock' => $warehouseStock,
                'book_categories' => $bookCategories,
                'total_revenue' => $totalRevenue,
            ],
            'courses' => [
                'total' => $totalCourses,
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
        Route::post('/update-profile', [AuthController::class, 'updateProfile']);
    });

    Route::post('/about', [AboutController::class, 'update']);

    // Promo code validation (public - requires order_total)
    Route::post('/promo-codes/validate', [PromoCodeController::class, 'validate']);

    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::get('/{id}', [OrderController::class, 'show']);
    });

    Route::get('/pdf/{bookId}', [PdfController::class, 'show']);

    Route::prefix('wishlist')->group(function () {
        Route::get('/', [WishlistController::class, 'index']);
        Route::post('/toggle', [WishlistController::class, 'toggle']);
        Route::post('/check-batch', [WishlistController::class, 'checkBatch']);
    });

    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/add', [CartController::class, 'add']);
        Route::post('/update', [CartController::class, 'update']);
        Route::delete('/{itemId}', [CartController::class, 'remove']);
        Route::delete('/', [CartController::class, 'clear']);
    });

    Route::put('/books/{id}/reviews/{reviewId}', [ReviewController::class, 'update']);

    Route::prefix('courses')->group(function () {
        Route::get('/{id}/progress', [CourseProgressController::class, 'index'])->middleware('auth:sanctum');
        Route::post('/{id}/progress', [CourseProgressController::class, 'store'])->middleware('auth:sanctum');
    });

    Route::prefix('staff')->group(function () {
    // Test upload - no auth for debugging
    Route::post('/test-upload', function(Request $request) {
        $file = $request->file('file');
        if (!$file) {
            return response()->json(['error' => 'No file provided'], 400);
        }
        $path = $file->store('courses/misc', 'public');
        return response()->json([
            'url' => asset('storage/' . $path),
            'path' => $path,
        ]);
    });
        Route::put('/questions/{id}/answer', [QuestionController::class, 'answer']);
        Route::get('/dashboard', [StaffController::class, 'dashboard']);
        Route::get('/orders', [StaffController::class, 'orders']);
        Route::put('/orders/{id}', [StaffController::class, 'updateOrderStatus']);
        Route::post('/orders/{id}/generate-tracking', [StaffController::class, 'generateTrackingForOrder']);
        Route::get('/books', [StaffController::class, 'books']);
        Route::get('/books/batch', [StaffController::class, 'batchBooks']);
        Route::post('/books', [StaffController::class, 'storeBook']);
        Route::put('/books/{id}', [StaffController::class, 'updateBook']);
        Route::delete('/books/{id}', [StaffController::class, 'deleteBook']);
        Route::post('/books/upload-cover', [StaffController::class, 'uploadCover']);
        Route::post('/books/upload-preview', [StaffController::class, 'uploadPreviewImage']);
        Route::post('/books/upload-pdf', [StaffController::class, 'uploadPdf']);
        Route::post('/books/extract-preview-pages', [StaffController::class, 'extractPreviewPages']);
        Route::post('/categories', [StaffController::class, 'storeCategory']);
        Route::delete('/categories/{id}', [StaffController::class, 'deleteCategory']);
        Route::get('/course-categories', [StaffController::class, 'courseCategories']);
        Route::post('/course-categories', [StaffController::class, 'storeCourseCategory']);
        Route::delete('/course-categories/{id}', [StaffController::class, 'deleteCourseCategory']);
        
        // Course Levels
        Route::get('/course-levels', [StaffController::class, 'courseLevels']);
        Route::post('/course-levels', [StaffController::class, 'storeCourseLevel']);
        Route::delete('/course-levels/{id}', [StaffController::class, 'deleteCourseLevel']);

        Route::get('/all-questions', [StaffController::class, 'allQuestions']);
        Route::put('/course-questions/{id}/answer', [CourseQuestionController::class, 'answer']);
        Route::get('/all-course-questions', [StaffController::class, 'allCourseQuestions']);

        Route::get('/courses', [StaffController::class, 'courses']);
        Route::post('/courses', [StaffController::class, 'storeCourse']);
        Route::put('/courses/{id}', [StaffController::class, 'updateCourse']);
        Route::delete('/courses/{id}', [StaffController::class, 'deleteCourse']);
        Route::post('/courses/upload-file', [StaffController::class, 'uploadCourseFile']);
        Route::get('/courses/drafts', [StaffController::class, 'draftCourses']);
        Route::put('/courses/{id}/publish', [StaffController::class, 'publishCourse']);

        // Gallery management
        Route::get('/gallery', [GalleryController::class, 'staffIndex']);
        Route::post('/gallery', [GalleryController::class, 'store']);
        Route::post('/gallery/upload-image', [GalleryController::class, 'uploadImage']);
        Route::put('/gallery/{id}', [GalleryController::class, 'update']);
        Route::delete('/gallery/{id}', [GalleryController::class, 'destroy']);

        // Payment settings
        Route::put('/site-settings', [SiteSettingsController::class, 'update']);

        // Promo codes management
        Route::get('/promo-codes', [PromoCodeController::class, 'index']);
        Route::post('/promo-codes', [PromoCodeController::class, 'store']);
        Route::put('/promo-codes/{id}', [PromoCodeController::class, 'update']);
        Route::delete('/promo-codes/{id}', [PromoCodeController::class, 'destroy']);
        Route::post('/promo-codes/{id}/reset-user', [PromoCodeController::class, 'resetUserUsage']);
        Route::post('/promo-codes/{id}/toggle', [PromoCodeController::class, 'toggleStatus']);

        // Book-Course access linking
        Route::get('/book-course-access', [StaffController::class, 'bookCourseAccess']);
        Route::post('/book-course-access', [StaffController::class, 'storeBookCourseAccess']);
        Route::delete('/book-course-access/{id}', [StaffController::class, 'deleteBookCourseAccess']);
    });
});
