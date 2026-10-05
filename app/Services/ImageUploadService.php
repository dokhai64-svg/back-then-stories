<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ImageUploadService
{
    private const MAX_DIMENSION = 1600;
    private const WEBP_QUALITY = 82;
    private const MAX_PROCESS_PIXELS = 40000000;

    public function store(
        UploadedFile $file,
        string $folder = 'media'
    ): array {
        $bytes = file_get_contents(
            $file->getRealPath()
        );

        if ($bytes === false || $bytes === '') {
            throw new RuntimeException(
                'Could not read uploaded image.'
            );
        }

        return $this->storeBytes(
            $bytes,
            $folder,
            $file->getClientOriginalName(),
            $file->getMimeType()
        );
    }

    public function storeBytes(
        string $bytes,
        string $folder = 'media',
        string $originalName = 'image',
        ?string $mimeHint = null
    ): array {
        if ($bytes === '') {
            throw new RuntimeException(
                'Image data is empty.'
            );
        }

        $disk = 'public';
        $folder = trim($folder, '/');
        $name = Str::uuid()->toString();

        $info = @getimagesizefromstring($bytes);

        if (is_array($info)) {
            $width = (int) ($info[0] ?? 0);
            $height = (int) ($info[1] ?? 0);
            $detectedMime = (string) ($info['mime'] ?? '');

            if ($detectedMime !== '') {
                $mimeHint = $detectedMime;
            }

            $pixels = $width > 0 && $height > 0
                ? $width * $height
                : 0;

            $canUseGd =
                function_exists('imagecreatefromstring')
                && function_exists('imagewebp')
                && $pixels > 0
                && $pixels <= self::MAX_PROCESS_PIXELS
                && $mimeHint !== 'image/gif';

            if ($canUseGd) {
                $optimized = $this->toOptimizedWebp(
                    $bytes,
                    $width,
                    $height
                );

                if ($optimized !== null) {
                    $path = $folder
                        . '/'
                        . $name
                        . '.webp';

                    Storage::disk($disk)->put(
                        $path,
                        $optimized
                    );

                    return [
                        'disk' => $disk,
                        'path' => $path,
                        'filename' => $originalName,
                        'mime_type' => 'image/webp',
                        'size' => strlen($optimized),
                    ];
                }
            }
        }

        $extension = $this->extensionForMime(
            $mimeHint,
            $originalName
        );

        $path = $folder
            . '/'
            . $name
            . '.'
            . $extension;

        Storage::disk($disk)->put(
            $path,
            $bytes
        );

        return [
            'disk' => $disk,
            'path' => $path,
            'filename' => $originalName,
            'mime_type' => $mimeHint ?: 'application/octet-stream',
            'size' => strlen($bytes),
        ];
    }

    private function toOptimizedWebp(
        string $bytes,
        int $sourceWidth,
        int $sourceHeight
    ): ?string {
        $source = @imagecreatefromstring($bytes);

        if ($source === false) {
            return null;
        }

        if (function_exists('imagepalettetotruecolor')) {
            @imagepalettetotruecolor($source);
        }

        $largestSide = max(
            $sourceWidth,
            $sourceHeight
        );

        $scale = $largestSide > self::MAX_DIMENSION
            ? self::MAX_DIMENSION / $largestSide
            : 1.0;

        $targetWidth = max(
            1,
            (int) round($sourceWidth * $scale)
        );

        $targetHeight = max(
            1,
            (int) round($sourceHeight * $scale)
        );

        $target = $source;

        if (
            $targetWidth !== $sourceWidth
            || $targetHeight !== $sourceHeight
        ) {
            $target = imagecreatetruecolor(
                $targetWidth,
                $targetHeight
            );

            if ($target === false) {
                imagedestroy($source);
                return null;
            }

            imagealphablending($target, false);
            imagesavealpha($target, true);

            $transparent = imagecolorallocatealpha(
                $target,
                0,
                0,
                0,
                127
            );

            imagefill(
                $target,
                0,
                0,
                $transparent
            );

            $copied = imagecopyresampled(
                $target,
                $source,
                0,
                0,
                0,
                0,
                $targetWidth,
                $targetHeight,
                $sourceWidth,
                $sourceHeight
            );

            imagedestroy($source);

            if (!$copied) {
                imagedestroy($target);
                return null;
            }
        }

        ob_start();
        $ok = imagewebp(
            $target,
            null,
            self::WEBP_QUALITY
        );
        $webp = ob_get_clean();

        imagedestroy($target);

        if (!$ok || !is_string($webp) || $webp === '') {
            return null;
        }

        return $webp;
    }

    private function extensionForMime(
        ?string $mime,
        string $originalName
    ): string {
        $map = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/avif' => 'avif',
        ];

        $mime = strtolower(
            trim((string) $mime)
        );

        if (isset($map[$mime])) {
            return $map[$mime];
        }

        $extension = strtolower(
            pathinfo(
                $originalName,
                PATHINFO_EXTENSION
            )
        );

        return in_array(
            $extension,
            ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'],
            true
        )
            ? $extension
            : 'jpg';
    }
}
