<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\ArticleChapter;
use App\Models\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class OptimizeExistingMedia extends Command
{
    protected $signature = 'media:optimize-existing
        {--apply : Actually write optimized files and update database references}
        {--max-width=1600 : Maximum output width in pixels}
        {--quality=82 : WebP quality 1-100}
        {--limit=0 : Maximum Media records to process; 0 means all}
        {--include-featured : Also optimize Article featured_image files}
        {--delete-originals : Delete originals only after successful DB update (NOT recommended on first run)}';

    protected $description = 'Safely convert existing local images to resized WebP while preserving references and rollback data.';

    private array $mapping = [];
    private int $converted = 0;
    private int $skipped = 0;
    private int $failed = 0;

    public function handle(): int
    {
        if (!function_exists('imagecreatefromstring') || !function_exists('imagewebp')) {
            $this->error('PHP GD/WebP is not available. ext-gd/imagewebp is required.');
            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $maxWidth = max(320, min(4096, (int) $this->option('max-width')));
        $quality = max(50, min(95, (int) $this->option('quality')));
        $limit = max(0, (int) $this->option('limit'));
        $deleteOriginals = (bool) $this->option('delete-originals');

        $this->newLine();
        $this->info('Back Then Stories - Existing Media Optimizer');
        $this->line('Mode: ' . ($apply ? 'APPLY' : 'DRY RUN'));
        $this->line("Max width: {$maxWidth}px | WebP quality: {$quality}");
        $this->line('Original files: ' . ($deleteOriginals ? 'DELETE after successful migration' : 'KEEP for rollback'));
        $this->newLine();

        if (!$apply) {
            $this->warn('DRY RUN only. No file or database changes will be made.');
        } elseif ($deleteOriginals) {
            $this->warn('WARNING: --delete-originals is enabled. First run is safer WITHOUT this option.');
        }

        $mediaQuery = Media::query()->orderBy('id');
        if ($limit > 0) {
            $mediaQuery->limit($limit);
        }

        $media = $mediaQuery->get();
        $this->info('Media Library records found: ' . $media->count());

        foreach ($media as $medium) {
            $this->processMediaRecord($medium, $apply, $maxWidth, $quality, $deleteOriginals);
        }

        if ((bool) $this->option('include-featured')) {
            $this->newLine();
            $this->info('Scanning Article featured images...');
            Article::query()
                ->whereNotNull('featured_image')
                ->where('featured_image', '<>', '')
                ->orderBy('id')
                ->chunkById(100, function ($articles) use ($apply, $maxWidth, $quality, $deleteOriginals) {
                    foreach ($articles as $article) {
                        $this->processFeaturedImage(
                            $article,
                            $apply,
                            $maxWidth,
                            $quality,
                            $deleteOriginals
                        );
                    }
                });
        }

        if ($apply && $this->mapping) {
            $this->writeBackupManifest();
        }

        $this->newLine();
        $this->table(
            ['Result', 'Count'],
            [
                ['Converted', $this->converted],
                ['Skipped', $this->skipped],
                ['Failed', $this->failed],
            ]
        );

        if ($apply && $this->mapping) {
            $this->info('Rollback manifest written under storage/app/media-migration-backups/.');
        }

        return $this->failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function processMediaRecord(
        Media $medium,
        bool $apply,
        int $maxWidth,
        int $quality,
        bool $deleteOriginals
    ): void {
        $disk = $medium->disk ?: 'public';
        $oldPath = trim((string) $medium->path);

        if ($oldPath === '' || !Storage::disk($disk)->exists($oldPath)) {
            $this->warn("Media #{$medium->id}: missing file {$oldPath}");
            $this->failed++;
            return;
        }

        $result = $this->buildOptimizedWebp(
            $disk,
            $oldPath,
            'media',
            $maxWidth,
            $quality,
            $apply
        );

        if ($result['status'] === 'skip') {
            $this->line("SKIP Media #{$medium->id}: {$result['reason']}");
            $this->skipped++;
            return;
        }

        if ($result['status'] === 'fail') {
            $this->warn("FAIL Media #{$medium->id}: {$result['reason']}");
            $this->failed++;
            return;
        }

        $newPath = $result['path'];
        $oldSize = (int) ($medium->size ?: Storage::disk($disk)->size($oldPath));
        $newSize = (int) $result['size'];

        $this->line(sprintf(
            '%s Media #%d: %s -> %s | %s -> %s',
            $apply ? 'APPLY' : 'WOULD',
            $medium->id,
            $oldPath,
            $newPath,
            $this->formatBytes($oldSize),
            $this->formatBytes($newSize)
        ));

        if (!$apply) {
            $this->converted++;
            return;
        }

        try {
            DB::transaction(function () use ($medium, $oldPath, $newPath, $newSize) {
                $medium->update([
                    'path' => $newPath,
                    'mime_type' => 'image/webp',
                    'size' => $newSize,
                ]);

                $this->replaceBodyReferences($oldPath, $newPath);
            });

            $this->mapping[] = [
                'type' => 'media',
                'id' => $medium->id,
                'disk' => $disk,
                'old_path' => $oldPath,
                'new_path' => $newPath,
                'old_size' => $oldSize,
                'new_size' => $newSize,
            ];

            if ($deleteOriginals && $oldPath !== $newPath) {
                Storage::disk($disk)->delete($oldPath);
            }

            $this->converted++;
        } catch (Throwable $e) {
            Storage::disk($disk)->delete($newPath);
            $this->warn("FAIL Media #{$medium->id}: {$e->getMessage()}");
            $this->failed++;
        }
    }

    private function processFeaturedImage(
        Article $article,
        bool $apply,
        int $maxWidth,
        int $quality,
        bool $deleteOriginals
    ): void {
        $disk = 'public';
        $oldPath = trim((string) $article->featured_image);

        if ($oldPath === '' || !Storage::disk($disk)->exists($oldPath)) {
            $this->warn("Article #{$article->id}: featured image missing: {$oldPath}");
            $this->failed++;
            return;
        }

        $folder = trim(dirname($oldPath), './');
        if ($folder === '') {
            $folder = 'articles';
        }

        $result = $this->buildOptimizedWebp(
            $disk,
            $oldPath,
            $folder,
            $maxWidth,
            $quality,
            $apply
        );

        if ($result['status'] === 'skip') {
            $this->line("SKIP Article #{$article->id}: {$result['reason']}");
            $this->skipped++;
            return;
        }

        if ($result['status'] === 'fail') {
            $this->warn("FAIL Article #{$article->id}: {$result['reason']}");
            $this->failed++;
            return;
        }

        $newPath = $result['path'];
        $oldSize = (int) Storage::disk($disk)->size($oldPath);
        $newSize = (int) $result['size'];

        $this->line(sprintf(
            '%s Article #%d featured: %s -> %s | %s -> %s',
            $apply ? 'APPLY' : 'WOULD',
            $article->id,
            $oldPath,
            $newPath,
            $this->formatBytes($oldSize),
            $this->formatBytes($newSize)
        ));

        if (!$apply) {
            $this->converted++;
            return;
        }

        try {
            $article->update(['featured_image' => $newPath]);

            $this->mapping[] = [
                'type' => 'article_featured',
                'id' => $article->id,
                'disk' => $disk,
                'old_path' => $oldPath,
                'new_path' => $newPath,
                'old_size' => $oldSize,
                'new_size' => $newSize,
            ];

            if ($deleteOriginals && $oldPath !== $newPath) {
                Storage::disk($disk)->delete($oldPath);
            }

            $this->converted++;
        } catch (Throwable $e) {
            Storage::disk($disk)->delete($newPath);
            $this->warn("FAIL Article #{$article->id}: {$e->getMessage()}");
            $this->failed++;
        }
    }

    private function buildOptimizedWebp(
        string $disk,
        string $oldPath,
        string $targetFolder,
        int $maxWidth,
        int $quality,
        bool $apply
    ): array {
        try {
            $contents = Storage::disk($disk)->get($oldPath);

            if ($contents === '' || strlen($contents) > 20 * 1024 * 1024) {
                return ['status' => 'fail', 'reason' => 'file is empty or larger than 20 MB'];
            }

            $image = @imagecreatefromstring($contents);
            if ($image === false) {
                return ['status' => 'skip', 'reason' => 'unsupported/animated/non-raster image'];
            }

            $width = imagesx($image);
            $height = imagesy($image);

            if ($width < 1 || $height < 1) {
                imagedestroy($image);
                return ['status' => 'fail', 'reason' => 'invalid dimensions'];
            }

            $targetWidth = min($width, $maxWidth);
            $targetHeight = (int) max(1, round($height * ($targetWidth / $width)));

            $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
            if ($canvas === false) {
                imagedestroy($image);
                return ['status' => 'fail', 'reason' => 'cannot create resize canvas'];
            }

            imagealphablending($canvas, false);
            imagesavealpha($canvas, true);
            $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $transparent);

            if (!imagecopyresampled(
                $canvas,
                $image,
                0,
                0,
                0,
                0,
                $targetWidth,
                $targetHeight,
                $width,
                $height
            )) {
                imagedestroy($canvas);
                imagedestroy($image);
                return ['status' => 'fail', 'reason' => 'resize failed'];
            }

            ob_start();
            $ok = imagewebp($canvas, null, $quality);
            $webp = ob_get_clean();

            imagedestroy($canvas);
            imagedestroy($image);

            if (!$ok || !is_string($webp) || $webp === '') {
                return ['status' => 'fail', 'reason' => 'WebP encoding failed'];
            }

            $oldSize = strlen($contents);
            $newSize = strlen($webp);
            $oldExt = strtolower(pathinfo($oldPath, PATHINFO_EXTENSION));

            // Already-WebP files that are small and do not need resizing are left alone.
            if (
                $oldExt === 'webp'
                && $width <= $maxWidth
                && $newSize >= (int) round($oldSize * 0.96)
            ) {
                return ['status' => 'skip', 'reason' => 'already optimized WebP'];
            }

            $targetFolder = trim($targetFolder, '/');
            $newPath = ($targetFolder !== '' ? $targetFolder . '/' : '')
                . Str::uuid()
                . '.webp';

            if ($apply) {
                Storage::disk($disk)->put($newPath, $webp);
            }

            return [
                'status' => 'ok',
                'path' => $newPath,
                'size' => $newSize,
                'width' => $targetWidth,
                'height' => $targetHeight,
            ];
        } catch (Throwable $e) {
            return ['status' => 'fail', 'reason' => $e->getMessage()];
        }
    }

    private function replaceBodyReferences(string $oldPath, string $newPath): void
    {
        $oldCandidates = array_values(array_unique([
            'storage/' . ltrim($oldPath, '/'),
            '/storage/' . ltrim($oldPath, '/'),
            asset('storage/' . ltrim($oldPath, '/')),
        ]));

        $newCandidates = [
            'storage/' . ltrim($newPath, '/'),
            '/storage/' . ltrim($newPath, '/'),
            asset('storage/' . ltrim($newPath, '/')),
        ];

        Article::query()
            ->orderBy('id')
            ->chunkById(100, function ($articles) use ($oldCandidates, $newCandidates) {
                foreach ($articles as $article) {
                    $body = (string) ($article->body ?? '');
                    $updated = str_replace($oldCandidates, $newCandidates, $body);

                    if ($updated !== $body) {
                        $article->forceFill(['body' => $updated])->save();
                    }
                }
            });

        ArticleChapter::query()
            ->orderBy('id')
            ->chunkById(100, function ($chapters) use ($oldCandidates, $newCandidates) {
                foreach ($chapters as $chapter) {
                    $body = (string) ($chapter->body ?? '');
                    $updated = str_replace($oldCandidates, $newCandidates, $body);

                    if ($updated !== $body) {
                        $chapter->forceFill(['body' => $updated])->save();
                    }
                }
            });
    }

    private function writeBackupManifest(): void
    {
        $disk = Storage::disk('local');
        $dir = 'media-migration-backups';

        if (!$disk->exists($dir)) {
            $disk->makeDirectory($dir);
        }

        $filename = $dir . '/media-optimize-' . now()->format('Ymd-His') . '.json';

        $disk->put(
            $filename,
            json_encode([
                'created_at' => now()->toIso8601String(),
                'originals_deleted' => (bool) $this->option('delete-originals'),
                'items' => $this->mapping,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }

        return number_format($bytes / (1024 * 1024), 2) . ' MB';
    }
}
