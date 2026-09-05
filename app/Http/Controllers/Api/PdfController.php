<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PdfController extends Controller
{
    public function show(Request $request, $bookId)
    {
        $book = Book::find($bookId);
        if (!$book || !$book->pdf_path) {
            return response()->json(['message' => 'PDF not found'], 404);
        }

        $userId = $request->user()->id;

        $ownsBook = Order::where('user_id', $userId)
            ->whereIn('status', ['pending', 'processing', 'shipped', 'delivered', 'completed'])
            ->whereHas('items', function ($q) use ($bookId) {
                $q->where('book_id', $bookId);
            })
            ->exists();

        if (!$ownsBook) {
            return response()->json(['message' => 'You have not purchased this book'], 403);
        }

        $filename = basename($book->pdf_path);
        $fullPath = storage_path('app/private/book-pdfs/' . $filename);

        if (!file_exists($fullPath)) {
            return response()->json(['message' => 'PDF file not found on server'], 404);
        }

        $mime = mime_content_type($fullPath);
        $fileSize = filesize($fullPath);

        $range = $request->header('Range');

        if (!$range) {
            return response()->file($fullPath, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="' . $book->title . '.pdf"',
                'Accept-Ranges' => 'bytes',
                'Content-Length' => $fileSize,
                'Cache-Control' => 'no-store, private',
            ]);
        }

        if (!preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
            return response('Invalid Range header', 416, [
                'Content-Range' => "bytes */{$fileSize}",
            ]);
        }

        $start = (int) $matches[1];
        $end = $matches[2] !== '' ? (int) $matches[2] : $fileSize - 1;

        if ($end >= $fileSize) {
            $end = $fileSize - 1;
        }

        $contentLength = $end - $start + 1;

        $stream = function () use ($fullPath, $start, $contentLength) {
            $handle = fopen($fullPath, 'rb');
            fseek($handle, $start);
            $remaining = $contentLength;

            while ($remaining > 0 && !feof($handle)) {
                $buffer = fread($handle, min(8192, $remaining));
                echo $buffer;
                flush();
                $remaining -= strlen($buffer);
            }
            fclose($handle);
        };

        return new \Symfony\Component\HttpFoundation\StreamedResponse($stream, 206, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . $book->title . '.pdf"',
            'Accept-Ranges' => 'bytes',
            'Content-Range' => "bytes {$start}-{$end}/{$fileSize}",
            'Content-Length' => $contentLength,
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
