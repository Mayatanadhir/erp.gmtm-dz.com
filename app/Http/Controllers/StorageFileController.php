<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StorageFileController extends Controller
{
    /**
     * Fallback file serving from public storage.
     *
     * Ensures images, avatars, and assets are reliably delivered in environments
     * like cPanel and shared hosting where physical symlinks ('storage:link')
     * are missing, broken, or restricted by server security policies.
     */
    public function show(string $path): BinaryFileResponse
    {
        $basePublicStorage = realpath(storage_path('app/public'));

        if (! $basePublicStorage) {
            abort(404);
        }

        $cleanPath = ltrim(rawurldecode($path), '/\\');
        $fullPath = realpath($basePublicStorage.DIRECTORY_SEPARATOR.$cleanPath);

        // Security: Prevent path traversal attacks
        if (! $fullPath || ! str_starts_with($fullPath, $basePublicStorage)) {
            abort(404);
        }

        if (! is_file($fullPath)) {
            abort(404);
        }

        $mimeType = File::mimeType($fullPath) ?: 'application/octet-stream';

        return response()->file($fullPath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }
}
