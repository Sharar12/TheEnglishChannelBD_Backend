<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['items' => [], 'total' => 0, 'count' => 0]);
        }

        $cartItems = DB::table('cart_items')
            ->where('user_id', $user->id)
            ->get();

        $items = [];
        $total = 0;

        foreach ($cartItems as $item) {
            if ($item->book_id) {
                $book = Book::find($item->book_id);
                if ($book) {
                    $subtotal = $book->price * $item->quantity;
                    $items[] = [
                        'type' => 'book',
                        'book' => $book,
                        'quantity' => $item->quantity,
                        'subtotal' => $subtotal,
                    ];
                    $total += $subtotal;
                }
            } elseif ($item->course_id) {
                $course = Course::find($item->course_id);
                if ($course) {
                    $items[] = [
                        'type' => 'course',
                        'course' => $course,
                        'quantity' => $item->quantity,
                        'subtotal' => $course->price,
                    ];
                    $total += $course->price;
                }
            }
        }

        return response()->json([
            'items' => $items,
            'total' => $total,
            'count' => count($items),
        ]);
    }

    public function add(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'book_id' => 'nullable|exists:books,id',
            'course_id' => 'nullable|exists:courses,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $bookId = $validated['book_id'] ?? null;
        $courseId = $validated['course_id'] ?? null;

        if ($bookId) {
            $book = Book::findOrFail($bookId);
            if ($book->stock < $validated['quantity']) {
                return response()->json(['message' => 'Not enough stock available'], 400);
            }
        }

        $existing = DB::table('cart_items')
            ->where('user_id', $user->id)
            ->where(function ($query) use ($bookId, $courseId) {
                if ($bookId) {
                    $query->where('book_id', $bookId);
                } elseif ($courseId) {
                    $query->where('course_id', $courseId);
                }
            })
            ->first();

        if ($existing) {
            DB::table('cart_items')
                ->where('id', $existing->id)
                ->update(['quantity' => $existing->quantity + $validated['quantity']]);
        } else {
            DB::table('cart_items')->insert([
                'user_id' => $user->id,
                'book_id' => $bookId,
                'course_id' => $courseId,
                'quantity' => $validated['quantity'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $count = DB::table('cart_items')->where('user_id', $user->id)->count();

        return response()->json([
            'message' => 'Item added to cart',
            'cart_count' => $count,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated = $request->validate([
            'book_id' => 'nullable|integer',
            'course_id' => 'nullable|integer',
            'quantity' => 'required|integer|min:0',
        ]);

        $bookId = $validated['book_id'] ?? null;
        $courseId = $validated['course_id'] ?? null;

        if ($validated['quantity'] === 0) {
            DB::table('cart_items')
                ->where('user_id', $user->id)
                ->where(function ($query) use ($bookId, $courseId) {
                    if ($bookId) {
                        $query->where('book_id', $bookId);
                    } elseif ($courseId) {
                        $query->where('course_id', $courseId);
                    }
                })
                ->delete();
        } else {
            DB::table('cart_items')
                ->where('user_id', $user->id)
                ->where(function ($query) use ($bookId, $courseId) {
                    if ($bookId) {
                        $query->where('book_id', $bookId);
                    } elseif ($courseId) {
                        $query->where('course_id', $courseId);
                    }
                })
                ->update(['quantity' => $validated['quantity']]);
        }

        return response()->json(['message' => 'Cart updated']);
    }

    public function remove(Request $request, $itemId)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        DB::table('cart_items')
            ->where('id', $itemId)
            ->where('user_id', $user->id)
            ->delete();

        return response()->json(['message' => 'Item removed from cart']);
    }

    public function clear(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        DB::table('cart_items')->where('user_id', $user->id)->delete();

        return response()->json(['message' => 'Cart cleared']);
    }
}
