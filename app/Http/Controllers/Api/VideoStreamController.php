<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VideoStreamController extends Controller
{
    public function stream(Request $request, $path)
    {
        $fullPath = storage_path('app/public/courses/videos/' . $path);

        if (!file_exists($fullPath)) {
            return response()->json(['message' => 'Video not found'], 404);
        }

        $fileSize = filesize($fullPath);
        $mime = mime_content_type($fullPath);

        $range = $request->header('Range');

        if (!$range) {
            return response()->file($fullPath, [
                'Content-Type' => $mime,
                'Accept-Ranges' => 'bytes',
                'Content-Length' => $fileSize,
            ]);
        }

        // Parse Range header (e.g., "bytes=0-")
        if (!preg_match('/bytes=(\d+)-(\d*)/', $range, $matches)) {
            return response('Invalid Range header', 416, [
                'Content-Range' => "bytes */{$fileSize}",
            ]);
        }

        $start = (int) $matches[1];
        $end = $matches[2] !== '' ? (int) $matches[2] : $fileSize - 1;

        // Ensure end doesn't exceed file size
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

        return new StreamedResponse($stream, 206, [
            'Content-Type' => $mime,
            'Accept-Ranges' => 'bytes',
            'Content-Range' => "bytes {$start}-{$end}/{$fileSize}",
            'Content-Length' => $contentLength,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
}
