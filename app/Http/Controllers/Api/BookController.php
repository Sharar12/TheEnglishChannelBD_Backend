<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BookController extends Controller
{
    public function index(Request $request)
    {
        return $this->buildBookQuery($request);
    }
    
    private function buildBookQuery($request)
    {
        $query = Book::select('id', 'title', 'author', 'description', 'price', 'pdf_price', 'pdf_path', 'stock', 'stock_threshold', 'image', 'preview_images', 'status', 'is_featured', 'category_id', 'average_rating', 'created_at')
            ->with(['category' => function($q) {
                $q->select('id', 'name', 'slug');
            }]);

        // Allow optional status filtering for debugging or admin use.
        // If no `status` param is provided, default to only approved books.
        if ($request->has('status')) {
            $statusParam = $request->status;
            if ($statusParam !== 'all') {
                $statusValues = is_array($statusParam) ? $statusParam : explode(',', $statusParam);
                $query->whereIn('status', $statusValues);
            }
        } else {
            $query->where('status', 'approved');
        }

        if ($request->has('category')) {
            $categoryIds = is_array($request->category)
                ? $request->category
                : explode(',', $request->category);
            $query->whereIn('category_id', $categoryIds);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('author', 'like', "%{$search}%");
            });
        }

        if ($request->has('featured')) {
            $query->where('is_featured', true);
        }

        if ($request->has('sort')) {
            switch ($request->sort) {
                case 'price_asc':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_desc':
                    $query->orderBy('price', 'desc');
                    break;
                case 'newest':
                    $query->orderBy('created_at', 'desc');
                    break;
                default:
                    $query->orderBy('title', 'asc');
            }
        }

        $books = $query->paginate(12);

        $books->getCollection()->transform(function ($book) {
            $book->average_rating = round($book->reviews()->where('is_approved', true)->avg('rating') ?? 0, 1);
            $book->reviews_count = $book->reviews()->where('is_approved', true)->count();
            $book->purchase_count = DB::table('order_items')
                ->where('book_id', $book->id)
                ->whereNotNull('book_id')
                ->count();
            if ($book->image) {
                // If image is already a full URL, leave it; otherwise prefix storage path.
                if (!preg_match('#^https?://#i', $book->image)) {
                    $book->image = asset('storage/' . ltrim($book->image, '/'));
                }
            }
            if ($book->preview_images && is_array($book->preview_images)) {
                $book->preview_images = array_map(function ($img) {
                    return asset('storage/' . ltrim($img, '/'));
                }, $book->preview_images);
            }
            return $book;
        });

        return response()->json($books)->header('Cache-Control', 'public, max-age=300');
    }

    public function show($id)
    {
        $book = Book::with('category')->where('id', (int)$id)->firstOrFail();
        $book->average_rating = round($book->reviews()->where('is_approved', true)->avg('rating') ?? 0, 1);
        $book->reviews_count = $book->reviews()->where('is_approved', true)->count();
        $book->purchase_count = DB::table('order_items')
            ->where('book_id', $book->id)
            ->whereNotNull('book_id')
            ->count();

        if ($book->image) {
            if (!preg_match('#^https?://#i', $book->image)) {
                $book->image = asset('storage/' . ltrim($book->image, '/'));
            }
        }
        if ($book->preview_images && is_array($book->preview_images)) {
            $book->preview_images = array_map(function ($img) {
                return asset('storage/' . ltrim($img, '/'));
            }, $book->preview_images);
        }

        return response()->json($book);
    }

    public function categories()
    {
        $categories = Category::withCount('books')->get();

        return response()->json($categories);
    }
}
