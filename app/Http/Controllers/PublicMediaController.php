<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicMediaController extends Controller
{
    public function show(string $path): BinaryFileResponse
    {
        $path = rawurldecode($path);
        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');

        if (
            $path === ''
            || str_contains($path, "\0")
            || preg_match('#(^|/)\.\.?(/|$)#', $path)
            || !preg_match('#^(media|articles)/#', $path)
        ) {
            abort(404);
        }

        $disk = Storage::disk('public');

        if (!$disk->exists($path)) {
            abort(404);
        }

        $mime = $disk->mimeType($path)
            ?: 'application/octet-stream';

        if (!str_starts_with($mime, 'image/')) {
            abort(404);
        }

        return response()->file(
            $disk->path($path),
            [
                'Content-Type' => $mime,
                'Cache-Control' => 'public, max-age=31536000, s-maxage=31536000, immutable',
                'Expires' => now()->addYear()->toRfc7231String(),
                'X-Content-Type-Options' => 'nosniff',
            ]
        );
    }
}
