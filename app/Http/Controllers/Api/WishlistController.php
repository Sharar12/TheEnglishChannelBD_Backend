<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $perPage = 50; // Limit to 50 wishlist items per request
        $wishlistItems = Wishlist::where('user_id', $request->user()->id)
            ->with(['book' => function($query) {
                $query->select('id', 'title', 'author', 'price', 'image', 'category_id');
            }, 'book.category' => function($query) {
                $query->select('id', 'name', 'slug');
            }])
            ->select('id', 'user_id', 'book_id', 'created_at')
            ->orderBy('created_at', 'desc')
            ->limit($perPage)
            ->get();

        return response()->json($wishlistItems);
    }

    public function toggle(Request $request)
    {
        $validated = $request->validate([
            'book_id' => 'required|exists:books,id',
        ]);

        $userId = $request->user()->id;
        $bookId = $validated['book_id'];

        $existing = Wishlist::where('user_id', $userId)
            ->where('book_id', $bookId)
            ->first();

        if ($existing) {
            $existing->delete();
            return response()->json([
                'action' => 'removed',
                'message' => 'Removed from wishlist',
            ]);
        }

        $wishlist = Wishlist::create([
            'user_id' => $userId,
            'book_id' => $bookId,
        ]);

        return response()->json([
            'action' => 'added',
            'wishlist' => $wishlist,
            'message' => 'Added to wishlist',
        ], 201);
    }

    public function check(Request $request)
    {
        $bookId = $request->query('book_id');
        $user = $request->user();

        if (!$user) {
            return response()->json(['in_wishlist' => false]);
        }

        $exists = Wishlist::where('user_id', $user->id)
            ->where('book_id', $bookId)
            ->exists();

        return response()->json(['in_wishlist' => $exists]);
    }

    public function checkBatch(Request $request)
    {
        $bookIds = $request->input('book_ids', []);

        if (!is_array($bookIds) || empty($bookIds)) {
            return response()->json(['wishlist' => []]);
        }

        $idsArray = array_filter(array_map('intval', $bookIds));

        if (empty($idsArray)) {
            return response()->json(['wishlist' => []]);
        }

        $user = $request->user();

        // If no user, return all false
        if (!$user) {
            $result = [];
            foreach ($idsArray as $id) {
                $result[$id] = false;
            }
            return response()->json(['wishlist' => $result]);
        }

        $userId = $user->id;

        $wishlistItems = Wishlist::where('user_id', $userId)
            ->whereIn('book_id', $idsArray)
            ->pluck('book_id')
            ->toArray();

        $result = [];
        foreach ($idsArray as $id) {
            $result[$id] = in_array($id, $wishlistItems);
        }

        return response()->json(['wishlist' => $result]);
    }
}
