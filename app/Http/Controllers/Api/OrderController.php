<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Book;
use App\Models\PromoCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        // Return all orders for the user (needed for profile to show all course enrollments)
        $orders = $request->user()->orders()
            ->select('id', 'order_number', 'user_id', 'total', 'status', 'tracking_number', 'payment_method', 'payment_mobile', 'transaction_id', 'discount_amount', 'cod_charge', 'shipping_address', 'city', 'state', 'postal_code', 'phone', 'notes', 'created_at', 'updated_at')
            ->with(['items' => function($query) {
                $query->select('id', 'order_id', 'book_id', 'course_id', 'quantity', 'price', 'format', 'isbn');
            }, 'items.book' => function($query) {
                $query->select('id', 'title', 'author', 'price', 'pdf_price', 'pdf_path', 'image');
            }, 'items.course' => function($query) {
                $query->select('id', 'title', 'slug', 'instructor', 'price', 'image');
            }])
            ->orderBy('created_at', 'desc')
            ->get();
        
        foreach ($orders as $order) {
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
        }
        
        return response()->json($orders)->header('Cache-Control', 'public, max-age=60');
    }

    public function store(Request $request)
    {
        // Validate for course enrollments (simplified - no shipping needed)
        $validated = $request->validate([
            'payment_method' => 'required|string',
            'payment_mobile' => 'nullable|string|max:20',
            'transaction_id' => 'nullable|string|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'cod_charge' => 'nullable|numeric|min:0',
            'promo_code_id' => 'nullable|integer|exists:promo_codes,id',
            'promo_discount_amount' => 'nullable|numeric|min:0',
            'items' => 'required|array',
            'items.*.type' => 'required|string|in:book,course',
            'items.*.book_id' => 'nullable|integer',
            'items.*.course_id' => 'nullable|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.format' => 'nullable|string|in:physical,pdf',
            // Shipping fields (optional for course-only orders)
            'shipping_address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction();

            $total = 0;
            $orderItems = [];
            $hasOnlyCourses = collect($validated['items'])->every(fn($item) => $item['type'] === 'course');
            $userId = $request->user()->id;

            // Check for duplicate course enrollments
            foreach ($validated['items'] as $item) {
                if ($item['type'] === 'course') {
                    $exists = OrderItem::where('course_id', $item['course_id'])
                        ->join('orders', 'order_items.order_id', '=', 'orders.id')
                        ->where('orders.user_id', $userId)
                        ->whereIn('orders.status', ['delivered', 'completed'])
                        ->exists();
                    
                    if ($exists) {
                        $course = \App\Models\Course::find($item['course_id']);
                        throw new \Exception("You already own '{$course->title}'");
                    }
                }
            }

            foreach ($validated['items'] as $item) {
                if ($item['type'] === 'course') {
                    // Course enrollment - no stock check needed
                    $course = \App\Models\Course::find($item['course_id']);
                    if (!$course) {
                        throw new \Exception("Course not found: {$item['course_id']}");
                    }

                    $subtotal = $item['price'] * $item['quantity'];
                    $total += $subtotal;

                    $orderItems[] = [
                        'course_id' => $item['course_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'format' => 'pdf',
                    ];
                } else {
                    // Book purchase - check stock (skip for PDF)
                    $isPdf = ($item['format'] ?? 'physical') === 'pdf';
                    $book = $isPdf ? Book::find($item['book_id']) : Book::lockForUpdate()->find($item['book_id']);
                    if (!$book) {
                        throw new \Exception("Book not found: {$item['book_id']}");
                    }

                    if (!$isPdf && $book->stock < $item['quantity']) {
                        throw new \Exception("Not enough stock for: {$book->title}");
                    }

                    $subtotal = $item['price'] * $item['quantity'];
                    $total += $subtotal;

                    $orderItems[] = [
                        'book_id' => $item['book_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'format' => $isPdf ? 'pdf' : 'physical',
                    ];

                    if (!$isPdf) {
                        $book->decrement('stock', $item['quantity']);
                    }
                }
            }

            // Clear book cache after stock update
            Cache::flush();

            // Determine status based on order type
            $status = $hasOnlyCourses ? 'completed' : 'pending';

            do {
                $orderNumber = 'ORD-' . strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 10));
            } while (Order::where('order_number', $orderNumber)->exists());

            $order = Order::create([
                'order_number' => $orderNumber,
                'user_id' => $request->user()->id,
                'total' => $total,
                'status' => $status,
                'payment_method' => $validated['payment_method'],
                'payment_mobile' => $validated['payment_mobile'] ?? null,
                'transaction_id' => $validated['transaction_id'] ?? null,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'cod_charge' => $validated['cod_charge'] ?? 0,
                'shipping_address' => $validated['shipping_address'] ?? null,
                'city' => $validated['city'] ?? null,
                'state' => $validated['state'] ?? null,
                'postal_code' => $validated['postal_code'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($orderItems as $item) {
                $item['order_id'] = $order->id;
                OrderItem::create($item);
            }

            // Record promo code usage if provided
            if (!empty($validated['promo_code_id'])) {
                $promoCode = PromoCode::find($validated['promo_code_id']);
                if ($promoCode) {
                    $promoCode->recordUsage(
                        $request->user()->id,
                        $order->id,
                        $validated['promo_discount_amount'] ?? $validated['discount_amount'] ?? 0
                    );
                }
            }

            DB::commit();

            return response()->json([
                'order' => $order->load(['items.book', 'items.course']),
                'message' => 'Order placed successfully',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Order creation failed', [
                'user_id' => $request->user()?->id,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function show(Request $request, $id)
    {
        $order = $request->user()->orders()
            ->select('id', 'order_number', 'user_id', 'total', 'status', 'payment_method', 'payment_mobile', 'transaction_id', 'discount_amount', 'cod_charge', 'shipping_address', 'city', 'state', 'postal_code', 'phone', 'notes', 'created_at', 'updated_at')
            ->with(['items.book', 'items.course'])
            ->findOrFail($id);

        return response()->json($order);
    }
}
