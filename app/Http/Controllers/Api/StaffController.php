<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Course;
use App\Models\Book;
use App\Models\User;
use App\Models\Category;
use App\Models\CourseLevel;
use App\Models\BookCourseAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StaffController extends Controller
{
    public function dashboard()
    {
        try {
            $totalRevenue = Order::sum('total') ?? 0;
            $totalOrders = Order::count() ?? 0;
            $totalBooks = Book::count() ?? 0;
            $totalCustomers = User::where('role', 'customer')->count() ?? 0;
            $lowStockBooks = Book::where('stock', '<', 10)->count() ?? 0;
            
            // Additional book metrics
            $warehouseStock = Book::sum('stock') ?? 0;
            $bookCategories = Category::where('type', 'book')->count() ?? 0;
            
        // Courses overview data for staff dashboard
        $totalCourses = Course::count();
        // Count actual lessons from course_lessons table
        $totalVideos = \App\Models\CourseLesson::count();
        // Count course categories from categories table
        $courseCategories = Category::where('type', 'course')->count();
        // Course revenue from order_items linked to courses
        $courseRevenue = \App\Models\OrderItem::whereNotNull('course_id')->sum('price');
        $recentCourses = Course::orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'title', 'price', 'level', 'is_featured', 'created_at']);
    
            $recentOrders = Order::with('user')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
    
            $ordersByStatus = Order::selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->get();
    
            $revenueByMonth = Order::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(total) as revenue')
                ->groupBy('month')
                ->orderBy('month', 'desc')
                ->limit(6)
                ->get();
    
            return response()->json([
                'stats' => [
                    'total_revenue' => $totalRevenue,
                    'total_orders' => $totalOrders,
                    'total_books' => $totalBooks,
                    'total_customers' => $totalCustomers,
                    'low_stock_books' => $lowStockBooks,
                    'warehouse_stock' => $warehouseStock,
                    'book_categories' => $bookCategories,
                ],
                'recent_orders' => $recentOrders,
                'orders_by_status' => $ordersByStatus,
                'revenue_by_month' => $revenueByMonth,
                'courses' => [
                    'total' => $totalCourses,
                    'total_videos' => $totalVideos,
                    'categories' => $courseCategories,
                    'revenue' => $courseRevenue,
                    'recent' => $recentCourses,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Dashboard error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function orders()
    {
        $orders = Order::select('id', 'order_number', 'user_id', 'total', 'status', 'tracking_number', 'payment_method', 'payment_mobile', 'transaction_id', 'discount_amount', 'cod_charge', 'shipping_address', 'city', 'state', 'postal_code', 'phone', 'notes', 'created_at', 'updated_at')
            ->with(['user', 'items' => function($query) {
                $query->select('id', 'order_id', 'book_id', 'course_id', 'quantity', 'price', 'isbn', 'tra_number');
            }, 'items.book' => function($query) {
                $query->select('id', 'title', 'author', 'price', 'image');
            }, 'items.course' => function($query) {
                $query->select('id', 'title', 'instructor', 'image');
            }])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $orders->getCollection()->transform(function ($order) {
            foreach ($order->items as $item) {
                if ($item->book && $item->book->image) {
                    if (!preg_match('#^https?://#i', $item->book->image)) {
                        $item->book->image = asset('storage/' . ltrim($item->book->image, '/'));
                    }
                }
                if ($item->course && $item->course->image) {
                    if (!preg_match('#^https?://#i', $item->course->image)) {
                        $item->course->image = asset('storage/' . ltrim($item->course->image, '/'));
                    }
                }
            }
            return $order;
        });

        return response()->json($orders);
    }

    public function updateOrderStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'nullable|in:pending,processing,shipped,delivered,cancelled',
            'tracking_number' => 'nullable|string|max:100',
            'generate_tracking' => 'nullable|boolean',
            'items' => 'nullable|array',
            'items.*.item_id' => 'required_with:items|integer',
            'items.*.isbn' => 'nullable|string|max:50',
        ]);

        $order = Order::findOrFail($id);
        
        $newStatus = $validated['status'] ?? null;
        
        if ($newStatus && $newStatus === 'shipped') {
            $orderItems = \App\Models\OrderItem::where('order_id', $id)->get();
            
            $hasBookItems = $orderItems->whereNotNull('book_id')->isNotEmpty();
            
            if ($hasBookItems) {
                $bookItemsWithoutISBN = $orderItems->whereNotNull('book_id')->filter(function ($item) {
                    return empty($item->isbn);
                });
                
                if ($bookItemsWithoutISBN->isNotEmpty()) {
                    $bookTitles = $bookItemsWithoutISBN->map(function ($item) {
                        return $item->book->title ?? 'Unknown Book';
                    })->toArray();
                    
                    return response()->json([
                        'error' => 'ISBN numbers are required for book items before updating order status.',
                        'missing_isbn_books' => $bookTitles,
                    ], 422);
                }
            }
            
            if (empty($order->tracking_number)) {
                return response()->json([
                    'error' => 'Tracking number is required before updating order status.',
                ], 422);
            }
        }
        
        $updateData = [];
        
        if (isset($validated['status']) && !empty($validated['status'])) {
            $updateData['status'] = $validated['status'];
        }
        
        if (isset($validated['tracking_number']) && !empty($validated['tracking_number'])) {
            $updateData['tracking_number'] = $validated['tracking_number'];
        }
        
        if (!empty($updateData)) {
            $order->update($updateData);
        }

        if (isset($validated['items'])) {
            foreach ($validated['items'] as $itemUpdate) {
                $orderItem = \App\Models\OrderItem::where('id', $itemUpdate['item_id'])
                    ->where('order_id', $id)
                    ->first();
                
                if ($orderItem && isset($itemUpdate['isbn'])) {
                    $orderItem->update(['isbn' => $itemUpdate['isbn']]);
                }
            }
        }

        return response()->json([
            'order' => $order->fresh(['items']),
            'message' => 'Order updated',
        ]);
    }

    private function generateTrackingNumber(): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $length = 10;
        
        do {
            $trackingNumber = '';
            for ($i = 0; $i < $length; $i++) {
                $trackingNumber .= $characters[random_int(0, strlen($characters) - 1)];
            }
            $trackingNumber = 'TRK-' . $trackingNumber;
        } while (\App\Models\Order::where('tracking_number', $trackingNumber)->exists());
        
        return $trackingNumber;
    }

    public function generateTrackingForOrder(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        
        if ($order->tracking_number) {
            return response()->json([
                'tracking_number' => $order->tracking_number,
                'message' => 'Tracking number already exists',
            ]);
        }
        
        $trackingNumber = $this->generateTrackingNumber();
        $order->update(['tracking_number' => $trackingNumber]);
        
        return response()->json([
            'tracking_number' => $trackingNumber,
            'message' => 'Tracking number generated',
        ]);
    }

    public function books()
    {
        $perPage = min((int) request()->query('per_page', 50), 200);
        $page = (int) request()->query('page', 1);
        $search = request()->query('search', '');
        $stockFilter = request()->query('stock_filter', 'all');
        $sort = request()->query('sort', 'created_at');
        $status = request()->query('status', '');

        $query = Book::with('category');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%");
            });
        }

        if ($status && in_array($status, ['draft', 'approved'])) {
            $query->where('status', $status);
        }

        $categoriesParam = request()->query('categories', '');
        $categories = [];
        if ($categoriesParam) {
            $categories = is_array($categoriesParam) ? $categoriesParam : json_decode($categoriesParam, true) ?? [];
        }

        if (!empty($categories)) {
            $query->whereHas('category', function ($q) use ($categories) {
                $q->whereIn('name', $categories);
            });
        }

        if ($stockFilter === 'out') {
            $query->where('stock', 0);
        } elseif ($stockFilter === 'low') {
            $query->where('stock', '>', 0)->where('stock', '<', 10);
        } elseif ($stockFilter === 'in') {
            $query->where('stock', '>=', 10);
        }

        switch ($sort) {
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'title':
                $query->orderBy('title', 'asc');
                break;
            case 'stock-low':
                $query->orderBy('stock', 'asc');
                break;
            case 'stock-high':
                $query->orderBy('stock', 'desc');
                break;
            case 'price-low':
                $query->orderBy('price', 'asc');
                break;
            case 'price-high':
                $query->orderBy('price', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }

        $books = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json($books);
    }

    public function batchBooks()
    {
        $books = Book::with('category')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($book) {
                $reviews = $book->reviews()->where('is_approved', true);
                $book->average_rating = round($reviews->avg('rating') ?? 0, 1);
                $book->reviews_count = $reviews->count();
                $book->purchase_count = DB::table('order_items')
                    ->where('book_id', $book->id)
                    ->count();
                return $book;
            });

        return response()->json([
            'data' => $books,
            'total' => $books->count(),
        ]);
    }

    public function storeBook(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'author' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'category_id' => 'sometimes|exists:categories,id',
            'price' => 'sometimes|numeric|min:0',
            'pdf_price' => 'nullable|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'stock_threshold' => 'sometimes|integer|min:0',
            'image' => 'nullable|string',
            'isbn' => 'nullable|string',
            'publisher' => 'nullable|string',
            'pages' => 'nullable|integer',
            'language' => 'nullable|string',
            'format' => 'nullable|string',
            'is_featured' => 'sometimes|boolean',
            'status' => 'sometimes|string|in:draft,approved',
            'preview_content' => 'nullable|array',
            'preview_images' => 'nullable|array',
            'pdf_path' => 'nullable|string',
        ]);

        // Only set default status if not provided in request
        if (!$request->has('status')) {
            $validated['status'] = 'approved';
        }

        // Normalize preview_images to store paths only (not URLs)
        if (!empty($validated['preview_images']) && is_array($validated['preview_images'])) {
            $validated['preview_images'] = array_map(function ($img) {
                if (str_starts_with($img, 'http')) {
                    $parsed = parse_url($img);
                    $pathParts = explode('/', ltrim($parsed['path'] ?? '', '/'));
                    $storageIndex = array_search('storage', $pathParts);
                    if ($storageIndex !== false) {
                        return implode('/', array_slice($pathParts, $storageIndex + 1));
                    }
                }
                return $img;
            }, $validated['preview_images']);
        }

        if (!empty($validated['image']) && str_starts_with($validated['image'], 'http')) {
            $parsed = parse_url($validated['image']);
            $pathParts = explode('/', ltrim($parsed['path'] ?? '', '/'));
            $storageIndex = array_search('storage', $pathParts);
            if ($storageIndex !== false) {
                $validated['image'] = implode('/', array_slice($pathParts, $storageIndex + 1));
            }
        }

        $book = Book::create($validated);

        // Clear staff books cache if the cache store supports key listing
        $cacheStore = cache()->getStore();
        if (method_exists($cacheStore, 'keys')) {
            foreach (cache()->keys('staff_books_*') as $key) {
                cache()->forget($key);
            }
        }

        return response()->json([
            'book' => $book,
            'message' => 'Book created successfully',
        ], 201);
    }

    public function updateBook(Request $request, $id)
    {
        $book = Book::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'author' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'category_id' => 'sometimes|exists:categories,id',
            'price' => 'sometimes|numeric|min:0',
            'pdf_price' => 'nullable|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'stock_threshold' => 'sometimes|integer|min:0',
            'image' => 'nullable|string',
            'isbn' => 'nullable|string',
            'publisher' => 'nullable|string',
            'pages' => 'nullable|integer',
            'language' => 'nullable|string',
            'format' => 'nullable|string',
            'is_featured' => 'sometimes|boolean',
            'status' => 'sometimes|string|in:draft,approved',
            'preview_content' => 'nullable|array',
            'preview_images' => 'nullable|array',
            'pdf_path' => 'nullable|string',
        ]);

        // Normalize image path if it's an absolute URL provided by the client
        if (isset($validated['image']) && str_starts_with($validated['image'], 'http')) {
            $parsed = parse_url($validated['image']);
            $pathParts = explode('/', ltrim($parsed['path'] ?? '', '/'));
            $storageIndex = array_search('storage', $pathParts);
            if ($storageIndex !== false) {
                $validated['image'] = implode('/', array_slice($pathParts, $storageIndex + 1));
            }
        }

        // Normalize preview_images to store paths only (not URLs)
        if (!empty($validated['preview_images']) && is_array($validated['preview_images'])) {
            $validated['preview_images'] = array_map(function ($img) {
                if (str_starts_with($img, 'http')) {
                    $parsed = parse_url($img);
                    $pathParts = explode('/', ltrim($parsed['path'] ?? '', '/'));
                    $storageIndex = array_search('storage', $pathParts);
                    if ($storageIndex !== false) {
                        return implode('/', array_slice($pathParts, $storageIndex + 1));
                    }
                }
                return $img;
            }, $validated['preview_images']);
        }

        $book->update($validated);

        $cacheStore = cache()->getStore();
        if (method_exists($cacheStore, 'keys')) {
            foreach (cache()->keys('staff_books_*') as $key) {
                cache()->forget($key);
            }
        }

        return response()->json([
            'book' => $book,
            'message' => 'Book updated successfully',
        ]);
    }

    public function deleteBook($id)
    {
        $book = Book::findOrFail($id);
        $book->delete();

        // Guard against cache stores that do not implement keys()
        $cacheStore = cache()->getStore();
        if (method_exists($cacheStore, 'keys')) {
            foreach (cache()->keys('staff_books_*') as $key) {
                cache()->forget($key);
            }
        }

        return response()->json([
            'message' => 'Book deleted successfully',
        ]);
    }

    public function uploadCover(Request $request)
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp',
        ]);

        $path = $request->file('image')->store('book-covers', 'public');

        return response()->json([
            'path' => $path,
            'url' => asset('storage/' . $path),
            'message' => 'Cover uploaded successfully',
        ]);
    }

    public function uploadPreviewImage(Request $request)
    {
        $validated = $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp',
        ]);

        $path = $request->file('image')->store('book-previews', 'public');

        return response()->json([
            'path' => $path,
            'message' => 'Preview image uploaded successfully',
        ]);
    }

    public function uploadPdf(Request $request)
    {
        $validated = $request->validate([
            'pdf' => 'required|file|mimes:pdf|max:102400',
            'preview_pages' => 'sometimes|integer|min:1|max:50',
        ]);

        $file = $request->file('pdf');
        $previewPages = (int) ($request->input('preview_pages', 5));

        // Store PDF
        $pdfName = \Illuminate\Support\Str::random(20) . '.pdf';
        $pdfPath = $file->storeAs('book-pdfs', $pdfName, 'public');

        // Extract pages as preview images using Python
        $extractedImages = [];
        $pythonScript = base_path('extract_pdf_pages.py');
        $fullPdfPath = storage_path('app/public/' . $pdfPath);
        $outputDir = storage_path('app/public/book-previews');

        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        try {
            $escapedPdf = escapeshellarg($fullPdfPath);
            $escapedOut = escapeshellarg($outputDir);
            $command = "python \"{$pythonScript}\" {$escapedPdf} {$escapedOut} {$previewPages} 2>&1";
            $output = shell_exec($command);
            $decoded = json_decode($output, true);
            if (is_array($decoded) && isset($decoded['images'])) {
                $extractedImages = $decoded['images'];
                $totalPages = $decoded['total_pages'] ?? count($extractedImages);
            } elseif (is_array($decoded)) {
                $extractedImages = $decoded;
                $totalPages = count($extractedImages);
            } else {
                \Log::warning('PDF extraction failed, output: ' . ($output ?? 'null'));
            }
        } catch (\Exception $e) {
            \Log::error('PDF extraction error: ' . $e->getMessage());
        }

        return response()->json([
            'pdf_path' => $pdfPath,
            'pdf_url' => asset('storage/' . $pdfPath),
            'total_pages' => $totalPages ?? 0,
            'preview_images' => $extractedImages,
            'preview_image_urls' => array_map(function ($img) {
                return asset('storage/' . $img);
            }, $extractedImages),
            'message' => 'PDF uploaded successfully',
        ]);
    }

    public function extractPreviewPages(Request $request)
    {
        $validated = $request->validate([
            'pdf_path' => 'required|string',
            'preview_pages' => 'required|integer|min:1|max:50',
        ]);

        $fullPdfPath = storage_path('app/public/' . $validated['pdf_path']);
        if (!file_exists($fullPdfPath)) {
            return response()->json(['error' => 'PDF file not found'], 404);
        }

        $previewPages = (int) $validated['preview_pages'];
        $pythonScript = base_path('extract_pdf_pages.py');
        $outputDir = storage_path('app/public/book-previews');
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $extractedImages = [];
        $totalPages = 0;
        try {
            $escapedPdf = escapeshellarg($fullPdfPath);
            $escapedOut = escapeshellarg($outputDir);
            $command = "python \"{$pythonScript}\" {$escapedPdf} {$escapedOut} {$previewPages} 2>&1";
            $output = shell_exec($command);
            $decoded = json_decode($output, true);
            if (is_array($decoded) && isset($decoded['images'])) {
                $extractedImages = $decoded['images'];
                $totalPages = $decoded['total_pages'] ?? count($extractedImages);
            } elseif (is_array($decoded)) {
                $extractedImages = $decoded;
                $totalPages = count($extractedImages);
            } else {
                \Log::warning('PDF re-extraction failed, output: ' . ($output ?? 'null'));
            }
        } catch (\Exception $e) {
            \Log::error('PDF re-extraction error: ' . $e->getMessage());
        }

        return response()->json([
            'total_pages' => $totalPages ?? 0,
            'preview_images' => $extractedImages,
            'preview_image_urls' => array_map(function ($img) {
                return asset('storage/' . $img);
            }, $extractedImages),
            'message' => count($extractedImages) . ' preview pages extracted',
        ]);
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|unique:courses,slug',
            'instructor' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'duration_hours' => 'required|integer|min:0',
            'lessons_count' => 'required|integer|min:0',
            'level' => 'required|string|in:beginner,intermediate,advanced',
            'image' => 'nullable|string',
            'preview_video' => 'nullable|string',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'status' => 'string|in:draft,published',
            'category' => 'required|string',
            'language' => 'nullable|string',
            'access_time' => 'nullable|string',
            'sections' => 'nullable|array',
            'sections.*.title' => 'required|string',
            'sections.*.lessons' => 'nullable|array',
            'sections.*.lessons.*.title' => 'required|string',
            'sections.*.lessons.*.description' => 'nullable|string',
            'sections.*.lessons.*.video_url' => 'nullable|string',
            'sections.*.lessons.*.duration_minutes' => 'integer|min:0',
            'sections.*.lessons.*.is_free_preview' => 'boolean',
            'sections.*.lessons.*.resources' => 'nullable|array',
            'sections.*.lessons.*.resources.*.title' => 'required|string',
            'sections.*.lessons.*.resources.*.file_path' => 'required|string',
            'sections.*.lessons.*.resources.*.file_type' => 'required|string',
            'sections.*.lessons.*.resources.*.file_size' => 'integer',
            'sections.*.lessons.*.quizzes' => 'nullable|array',
            'sections.*.lessons.*.quizzes.*.title' => 'required|string',
            'sections.*.lessons.*.quizzes.*.questions' => 'nullable|array',
            'sections.*.lessons.*.quizzes.*.questions.*.question' => 'required|string',
            'sections.*.lessons.*.quizzes.*.questions.*.options' => 'required|array|min:2',
            'sections.*.lessons.*.quizzes.*.questions.*.correct_answer' => 'required|integer|min:0',
            'quizzes' => 'nullable|array',
            'quizzes.*.title' => 'required|string',
            'quizzes.*.questions' => 'nullable|array',
            'quizzes.*.questions.*.question' => 'required|string',
            'quizzes.*.questions.*.options' => 'required|array|min:2',
            'quizzes.*.questions.*.correct_answer' => 'required|integer|min:0',
        ]);

        \DB::beginTransaction();
        try {
            $course = \App\Models\Course::create([
                'title' => $validated['title'],
                'slug' => $validated['slug'],
                'instructor' => $validated['instructor'],
                'description' => $validated['description'],
                'price' => $validated['price'],
                'duration_hours' => $validated['duration_hours'],
                'lessons_count' => $validated['lessons_count'],
                'level' => $validated['level'],
                'image' => $validated['image'] ?? null,
                'preview_video' => $validated['preview_video'] ?? null,
                'is_featured' => $validated['is_featured'] ?? false,
                'is_active' => $validated['is_active'] ?? true,
                'status' => $validated['status'] ?? 'published',
                'category' => $validated['category'],
                'language' => $validated['language'] ?? 'English',
                'access_time' => $validated['access_time'] ?? 'Lifetime',
            ]);

            if (!empty($validated['sections'])) {
                \Log::info('Creating ' . count($validated['sections']) . ' sections for course ' . $course->id);
                foreach ($validated['sections'] as $sectionIdx => $sectionData) {
                    $section = $course->sections()->create([
                        'title' => $sectionData['title'],
                        'order' => $sectionIdx,
                    ]);

                    if (!empty($sectionData['lessons'])) {
                        foreach ($sectionData['lessons'] as $lessonIdx => $lessonData) {
                            $lesson = $section->lessons()->create([
                                'title' => $lessonData['title'],
                                'description' => $lessonData['description'] ?? null,
                                'video_url' => $lessonData['video_url'] ?? null,
                                'duration_minutes' => $lessonData['duration_minutes'] ?? 0,
                                'is_free_preview' => $lessonData['is_free_preview'] ?? false,
                                'type' => 'video',
                                'order' => $lessonIdx,
                            ]);

                            if (!empty($lessonData['resources'])) {
                                foreach ($lessonData['resources'] as $resIdx => $resData) {
                                    \App\Models\CourseResource::create([
                                        'course_id' => $course->id,
                                        'lesson_id' => $lesson->id,
                                        'title' => $resData['title'],
                                        'file_path' => $resData['file_path'],
                                        'file_type' => $resData['file_type'],
                                        'file_size' => $resData['file_size'] ?? 0,
                                    ]);
                                }
                            }

                            if (!empty($lessonData['quizzes'])) {
                                foreach ($lessonData['quizzes'] as $qIdx => $quizData) {
                                    $quiz = $course->quizzes()->create([
                                        'title' => $quizData['title'],
                                        'lesson_id' => $lesson->id,
                                        'order' => $qIdx,
                                    ]);

                                    if (!empty($quizData['questions'])) {
                                        foreach ($quizData['questions'] as $qqIdx => $qqData) {
                                            $quiz->questions()->create([
                                                'question' => $qqData['question'],
                                                'options' => json_encode($qqData['options']),
                                                'correct_answer' => $qqData['correct_answer'],
                                                'order' => $qqIdx,
                                            ]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if (!empty($validated['quizzes'])) {
                foreach ($validated['quizzes'] as $quizIdx => $quizData) {
                    $quiz = $course->quizzes()->create([
                        'title' => $quizData['title'],
                        'order' => $quizIdx,
                    ]);

                    if (!empty($quizData['questions'])) {
                        foreach ($quizData['questions'] as $qIdx => $qData) {
                            $quiz->questions()->create([
                                'question' => $qData['question'],
                                'options' => json_encode($qData['options']),
                                'correct_answer' => $qData['correct_answer'],
                                'order' => $qIdx,
                            ]);
                        }
                    }
                }
            }

            \DB::commit();
            return response()->json($course->load(['sections.lessons', 'quizzes.questions']), 201);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['message' => 'Failed to create course: ' . $e->getMessage()], 400);
        }
    }

    public function updateCourse(Request $request, $id)
    {
        $course = \App\Models\Course::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'slug' => 'sometimes|string|unique:courses,slug,' . $id,
            'instructor' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'duration_hours' => 'sometimes|integer|min:0',
            'lessons_count' => 'sometimes|integer|min:0',
            'level' => 'sometimes|string|in:beginner,intermediate,advanced',
            'image' => 'nullable|string',
            'preview_video' => 'nullable|string',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'status' => 'string|in:draft,published',
            'category' => 'sometimes|string',
            'sections' => 'sometimes|array',
            'sections.*.id' => 'nullable|integer',
            'sections.*.title' => 'required_with:sections|string|max:255',
            'sections.*.order' => 'integer',
            'sections.*.lessons' => 'sometimes|array',
            'sections.*.lessons.*.id' => 'nullable|integer',
            'sections.*.lessons.*.title' => 'required|string|max:255',
            'sections.*.lessons.*.description' => 'nullable|string',
            'sections.*.lessons.*.video_url' => 'nullable|string',
            'sections.*.lessons.*.duration_minutes' => 'integer|min:0',
            'sections.*.lessons.*.is_free_preview' => 'boolean',
            'sections.*.lessons.*.order' => 'integer',
            'sections.*.lessons.*.resources' => 'sometimes|array',
            'sections.*.lessons.*.resources.*.id' => 'nullable|integer',
            'sections.*.lessons.*.resources.*.title' => 'required|string',
            'sections.*.lessons.*.resources.*.file_path' => 'required|string',
            'sections.*.lessons.*.resources.*.file_type' => 'string',
            'sections.*.lessons.*.resources.*.file_size' => 'integer',
            'sections.*.lessons.*.quizzes' => 'sometimes|array',
            'sections.*.lessons.*.quizzes.*.id' => 'nullable|integer',
            'sections.*.lessons.*.quizzes.*.title' => 'required|string',
            'sections.*.lessons.*.quizzes.*.order' => 'integer',
            'sections.*.lessons.*.quizzes.*.questions' => 'sometimes|array',
            'sections.*.lessons.*.quizzes.*.questions.*.id' => 'nullable|integer',
            'sections.*.lessons.*.quizzes.*.questions.*.question' => 'required|string',
            'sections.*.lessons.*.quizzes.*.questions.*.options' => 'required|array',
            'sections.*.lessons.*.quizzes.*.questions.*.correct_answer' => 'required|integer',
            'sections.*.lessons.*.quizzes.*.questions.*.order' => 'integer',
            'quizzes' => 'sometimes|array',
            'quizzes.*.id' => 'nullable|integer',
            'quizzes.*.title' => 'required|string',
            'quizzes.*.order' => 'integer',
            'quizzes.*.questions' => 'sometimes|array',
            'quizzes.*.questions.*.id' => 'nullable|integer',
            'quizzes.*.questions.*.question' => 'required|string',
            'quizzes.*.questions.*.options' => 'required|array',
            'quizzes.*.questions.*.correct_answer' => 'required|integer',
            'quizzes.*.questions.*.order' => 'integer',
        ]);

        \DB::beginTransaction();
        try {
            // Update basic course fields
            $basicFields = ['title', 'slug', 'instructor', 'description', 'price', 'duration_hours', 'lessons_count', 'level', 'image', 'preview_video', 'is_featured', 'is_active', 'category'];
            foreach ($basicFields as $field) {
                if (isset($validated[$field])) {
                    $course->$field = $validated[$field];
                }
            }
            $course->save();

            // Update sections
            if (isset($validated['sections'])) {
                $sectionIds = [];
                foreach ($validated['sections'] as $sIdx => $sectionData) {
                    $section = null;
                    if (!empty($sectionData['id'])) {
                        $section = \App\Models\CourseSection::find($sectionData['id']);
                    }
                    if (!$section) {
                        $section = new \App\Models\CourseSection();
                        $section->course_id = $course->id;
                    }
                    $section->title = $sectionData['title'];
                    $section->order = $sectionData['order'] ?? $sIdx;
                    $section->save();
                    $sectionIds[] = $section->id;

                    // Update lessons
                    if (isset($sectionData['lessons'])) {
                        $lessonIds = [];
                        foreach ($sectionData['lessons'] as $lIdx => $lessonData) {
                            $lesson = null;
                            if (!empty($lessonData['id'])) {
                                $lesson = \App\Models\CourseLesson::find($lessonData['id']);
                            }
                            if (!$lesson) {
                                $lesson = new \App\Models\CourseLesson();
                                $lesson->section_id = $section->id;
                            }
                            $lesson->title = $lessonData['title'];
                            $lesson->description = $lessonData['description'] ?? null;
                            $lesson->video_url = $lessonData['video_url'] ?? null;
                            $lesson->duration_minutes = $lessonData['duration_minutes'] ?? 0;
                            $lesson->is_free_preview = $lessonData['is_free_preview'] ?? false;
                            $lesson->order = $lessonData['order'] ?? $lIdx;
                            $lesson->save();
                            $lessonIds[] = $lesson->id;

                            // Update lesson resources
                            if (isset($lessonData['resources'])) {
                                $resourceIds = [];
                                foreach ($lessonData['resources'] as $rIdx => $resData) {
                                    $resource = null;
                                    if (!empty($resData['id'])) {
                                        $resource = \App\Models\CourseResource::find($resData['id']);
                                    }
                                    if (!$resource) {
                                        $resource = new \App\Models\CourseResource();
                                        $resource->lesson_id = $lesson->id;
                                        $resource->course_id = $course->id;
                                    }
                                    $resource->title = $resData['title'];
                                    $resource->file_path = $resData['file_path'];
                                    $resource->file_type = $resData['file_type'] ?? 'document';
                                    $resource->file_size = $resData['file_size'] ?? 0;
                                    $resource->save();
                                    $resourceIds[] = $resource->id;
                                }
                                // Delete removed resources
                                \App\Models\CourseResource::where('lesson_id', $lesson->id)
                                    ->whereNotIn('id', $resourceIds)->delete();
                            }

                            // Update lesson quizzes
                            if (isset($lessonData['quizzes'])) {
                                $lQuizIds = [];
                                foreach ($lessonData['quizzes'] as $qIdx => $quizData) {
                                    $quiz = null;
                                    if (!empty($quizData['id'])) {
                                        $quiz = \App\Models\CourseQuiz::find($quizData['id']);
                                    }
                                    if (!$quiz) {
                                        $quiz = new \App\Models\CourseQuiz();
                                        $quiz->lesson_id = $lesson->id;
                                    }
                                    $quiz->title = $quizData['title'];
                                    $quiz->order = $quizData['order'] ?? $qIdx;
                                    $quiz->save();
                                    $lQuizIds[] = $quiz->id;

                                    // Update quiz questions
                                    if (isset($quizData['questions'])) {
                                        $questionIds = [];
                                        foreach ($quizData['questions'] as $qqIdx => $qData) {
                                            $question = null;
                                            if (!empty($qData['id'])) {
                                                $question = \App\Models\CourseQuizQuestion::find($qData['id']);
                                            }
                                            if (!$question) {
                                                $question = new \App\Models\CourseQuizQuestion();
                                                $question->quiz_id = $quiz->id;
                                            }
                                            $question->question = $qData['question'];
                                            $question->options = $qData['options'];
                                            $question->correct_answer = $qData['correct_answer'];
                                            $question->order = $qData['order'] ?? $qqIdx;
                                            $question->save();
                                            $questionIds[] = $question->id;
                                        }
                                        \App\Models\CourseQuizQuestion::where('quiz_id', $quiz->id)
                                            ->whereNotIn('id', $questionIds)->delete();
                                    }
                                }
                                \App\Models\CourseQuiz::where('lesson_id', $lesson->id)
                                    ->whereNotIn('id', $lQuizIds)->delete();
                            }
                        }
                        \App\Models\CourseLesson::where('section_id', $section->id)
                            ->whereNotIn('id', $lessonIds)->delete();
                    }
                }
                // Delete removed sections
                \App\Models\CourseSection::where('course_id', $course->id)
                    ->whereNotIn('id', $sectionIds)->delete();
            }

            // Update course-level quizzes
            if (isset($validated['quizzes'])) {
                $quizIds = [];
                foreach ($validated['quizzes'] as $qIdx => $quizData) {
                    $quiz = null;
                    if (!empty($quizData['id'])) {
                        $quiz = \App\Models\CourseQuiz::find($quizData['id']);
                    }
                    if (!$quiz) {
                        $quiz = new \App\Models\CourseQuiz();
                        $quiz->course_id = $course->id;
                    }
                    $quiz->title = $quizData['title'];
                    $quiz->order = $quizData['order'] ?? $qIdx;
                    $quiz->save();
                    $quizIds[] = $quiz->id;

                    // Update quiz questions
                    if (isset($quizData['questions'])) {
                        $questionIds = [];
                        foreach ($quizData['questions'] as $qqIdx => $qData) {
                            $question = null;
                            if (!empty($qData['id'])) {
                                $question = \App\Models\CourseQuizQuestion::find($qData['id']);
                            }
                            if (!$question) {
                                $question = new \App\Models\CourseQuizQuestion();
                                $question->quiz_id = $quiz->id;
                            }
                            $question->question = $qData['question'];
                            $question->options = $qData['options'];
                            $question->correct_answer = $qData['correct_answer'];
                            $question->order = $qData['order'] ?? $qqIdx;
                            $question->save();
                            $questionIds[] = $question->id;
                        }
                        \App\Models\CourseQuizQuestion::where('quiz_id', $quiz->id)
                            ->whereNotIn('id', $questionIds)->delete();
                    }
                }
                // Delete removed course-level quizzes
                \App\Models\CourseQuiz::where('course_id', $course->id)
                    ->whereNull('lesson_id')
                    ->whereNotIn('id', $quizIds)->delete();
            }

            // Recalculate lessons_count and duration_hours
            $sectionIds = \App\Models\CourseSection::where('course_id', $course->id)->pluck('id');
            $totalLessons = \App\Models\CourseLesson::whereIn('section_id', $sectionIds)->count();
            $totalMinutes = \App\Models\CourseLesson::whereIn('section_id', $sectionIds)->sum('duration_minutes');
            $course->lessons_count = $totalLessons;
            $course->duration_hours = $totalMinutes > 0 ? (int) ceil($totalMinutes / 60) : 0;
            $course->save();

            \DB::commit();
            return response()->json($course->load(['sections.lessons', 'quizzes.questions']));
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['message' => 'Failed to update course', 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteCourse($id)
    {
        $course = \App\Models\Course::findOrFail($id);
        $course->delete();
        return response()->json(['message' => 'Course deleted successfully']);
    }

    public function uploadCourseFile(Request $request)
    {
        \Log::info('Upload Course File Request:', [
            'has_file' => $request->hasFile('file'),
            'file_info' => $request->file('file') ? [
                'original_name' => $request->file('file')->getClientOriginalName(),
                'mime_type' => $request->file('file')->getMimeType(),
                'size' => $request->file('file')->getSize(),
                'extension' => $request->file('file')->getClientOriginalExtension(),
            ] : null,
            'type' => $request->input('type'),
        ]);

        $validated = $request->validate([
            'file' => 'required|file',
            'type' => 'required|in:video,document,image',
        ]);

        $file = $request->file('file');
        $type = $request->input('type');

        // Additional validation for images
        if ($type === 'image') {
            $request->validate([
                'file' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB max for images
            ]);
        }

        $directory = 'courses/misc';
        if ($type === 'video') {
            $directory = 'courses/videos';
        } elseif ($type === 'document') {
            $directory = 'courses/documents';
        } elseif ($type === 'image') {
            $directory = 'courses/thumbnails';
        }

        $extension = $file->getClientOriginalExtension();
        if (!$extension) {
            $extension = $type === 'image' ? 'jpg' : ($type === 'video' ? 'mp4' : 'pdf');
        }
        $shortName = \Illuminate\Support\Str::random(20) . '.' . $extension;

        $path = $file->storeAs($directory, $shortName, 'public');

        $url = url('storage/' . $path);
        $assetUrl = asset('storage/' . $path);

        // Try different URL formats to ensure accessibility
        $directUrl = config('app.url') . '/storage/' . $path;

        \Log::info('File uploaded successfully:', [
            'path' => $path,
            'url' => $url,
            'asset_url' => $assetUrl,
            'direct_url' => $directUrl,
            'full_path' => storage_path('app/public/' . $path),
            'file_exists' => file_exists(storage_path('app/public/' . $path)),
            'app_url' => config('app.url'),
        ]);

        // Return URL that works with the storage API route
        // The route is at /api/storage/{path} (defined in api.php outside auth middleware)
        $baseUrl = rtrim(config('app.url'), '/');
        $url = $baseUrl . '/api/storage/' . $path;

        \Log::info('Returning URL to frontend:', [
            'url' => $url,
            'base_url' => $baseUrl,
            'path' => $path,
        ]);

        return response()->json([
            'url' => $url,
            'path' => $path,
            'size' => $file->getSize(),
            'name' => $shortName,
        ]);
    }

    public function courseCategories()
    {
        $categories = \App\Models\Category::where('type', 'course')
            ->withCount('books')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
        return response()->json($categories);
    }

    public function storeCourseCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category = \App\Models\Category::create([
            'name' => $validated['name'],
            'slug' => \Illuminate\Support\Str::slug($validated['name']),
            'type' => 'course',
        ]);

        return response()->json($category, 201);
    }

    public function deleteCourseCategory($id)
    {
        $category = \App\Models\Category::where('type', 'course')->findOrFail($id);
        $category->delete();
        return response()->json(['message' => 'Course category deleted']);
    }

    public function bookCourseAccess()
    {
        $access = BookCourseAccess::with(['book:id,title,image', 'course:id,title,image'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($access);
    }

    public function storeBookCourseAccess(Request $request)
    {
        $validated = $request->validate([
            'book_id' => 'required|exists:books,id',
            'course_id' => 'required|exists:courses,id',
            'access_type' => 'required|in:free,discount',
            'discount_percent' => 'nullable|integer|min:1|max:100|required_if:access_type,discount',
        ]);

        $access = BookCourseAccess::updateOrCreate(
            ['book_id' => $validated['book_id'], 'course_id' => $validated['course_id']],
            $validated
        );

        $access->load(['book:id,title,image', 'course:id,title,image']);

        return response()->json($access, 201);
    }

    public function deleteBookCourseAccess($id)
    {
        $access = BookCourseAccess::findOrFail($id);
        $access->delete();
        return response()->json(['message' => 'Access rule deleted']);
    }

    public function courses()
    {
        $courses = Course::select('id', 'title', 'image', 'price', 'is_active', 'is_featured', 'category', 'instructor', 'level', 'duration_hours', 'lessons_count')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($courses);
    }

    public function publishCourse($id)
    {
        $course = Course::findOrFail($id);
        $course->update(['status' => 'published']);
        return response()->json(['message' => 'Course published']);
    }

    public function draftCourses()
    {
        $courses = Course::where('status', 'draft')
            ->orWhereNull('status')
            ->select('id', 'title', 'image', 'price', 'instructor', 'level', 'duration_hours', 'lessons_count', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($courses);
    }
}
