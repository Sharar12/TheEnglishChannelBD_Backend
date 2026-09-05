<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GalleryPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    // Public endpoint - get all active gallery photos
    public function index()
    {
        $photos = GalleryPhoto::where('is_active', true)
            ->orderBy('order')
            ->orderBy('created_at', 'desc')
            ->get();

        $photos->transform(function ($photo) {
            $photo->image = $photo->image_path 
                ? url('storage/' . $photo->image_path) 
                : null;
            return $photo;
        });

        return response()->json($photos);
    }

    // Staff endpoints
    public function staffIndex(Request $request)
    {
        $query = GalleryPhoto::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        $photos = $query->orderBy('order')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 20));

        $photos->getCollection()->transform(function ($photo) {
            $photo->image = $photo->image_path 
                ? url('storage/' . $photo->image_path) 
                : null;
            return $photo;
        });

        return response()->json($photos);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'order' => 'nullable|integer',
            'is_active' => 'nullable',
            'date' => 'nullable|date',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $fileName = uniqid() . '_' . time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('storage/gallery'), $fileName);
            $imagePath = 'gallery/' . $fileName;
        }

        $photo = GalleryPhoto::create([
            'title' => $request->title,
            'description' => $request->description,
            'image_path' => $imagePath,
            'order' => $request->order ?? 0,
            'is_active' => filter_var($request->is_active ?? 'true', FILTER_VALIDATE_BOOLEAN),
            'date' => $request->date,
        ]);

        return response()->json([
            'message' => 'Gallery photo created successfully',
            'data' => $photo
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $photo = GalleryPhoto::findOrFail($id);

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'order' => 'sometimes|integer',
            'is_active' => 'sometimes',
        ]);

        $data = $request->only(['title', 'description', 'order', 'date']);
        
        // Handle boolean conversion for is_active
        if ($request->has('is_active')) {
            $data['is_active'] = filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN);
        }

        if ($request->hasFile('image')) {
            // Delete old image
            if ($photo->image_path) {
                $oldPath = public_path('storage/' . $photo->image_path);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
            
            $image = $request->file('image');
            $fileName = uniqid() . '_' . time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('storage/gallery'), $fileName);
            $data['image_path'] = 'gallery/' . $fileName;
        }

        $photo->update($data);

        return response()->json([
            'message' => 'Gallery photo updated successfully',
            'data' => $photo
        ]);
    }

    public function destroy($id)
    {
        $photo = GalleryPhoto::findOrFail($id);

        // Delete image file
        if ($photo->image_path) {
            Storage::disk('public')->delete($photo->image_path);
        }

        $photo->delete();

        return response()->json([
            'message' => 'Gallery photo deleted successfully'
        ]);
    }

    // Upload image only (for reuse)
    public function uploadImage(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        $path = $request->file('file')->store('gallery', 'public');

        return response()->json([
            'path' => $path,
            'url' => asset('storage/' . $path)
        ]);
    }
}
