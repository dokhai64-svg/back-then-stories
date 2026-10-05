<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\ArticleChapter;
use App\Models\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CleanupOrphanOriginalImages extends Command
{
    protected $signature = 'media:cleanup-orphan-originals
        {--apply : Actually delete verified unreferenced PNG/JPG originals}
        {--folders=media,articles : Comma-separated public disk folders to scan}';

    protected $description = 'Safely delete unreferenced old PNG/JPG/JPEG files from public storage after WebP migration.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $folders = collect(explode(',', (string) $this->option('folders')))
            ->map(fn ($v) => trim($v, " \t\n\r\0\x0B/"))
            ->filter()
            ->unique()
            ->values();

        $this->newLine();
        $this->info('Back Then Stories - Orphan Original Image Cleanup V2');
        $this->line('Mode: ' . ($apply ? 'APPLY' : 'DRY RUN'));
        $this->line('Disk: public');
        $this->line('Folders: ' . $folders->implode(', '));
        $this->line('Eligible extensions: png, jpg, jpeg');
        $this->newLine();

        if (!$apply) {
            $this->warn('DRY RUN only. No files will be deleted.');
        }

        $disk = Storage::disk('public');

        $scanned = 0;
        $eligible = 0;
        $referenced = 0;
        $deleted = 0;
        $failed = 0;
        $bytes = 0;
        $wouldDelete = 0;

        foreach ($folders as $folder) {
            if (!$disk->exists($folder)) {
                $this->warn("Folder not found, skipping: {$folder}");
                continue;
            }

            foreach ($disk->allFiles($folder) as $path) {
                $scanned++;

                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

                if (!in_array($ext, ['png', 'jpg', 'jpeg'], true)) {
                    continue;
                }

                $eligible++;

                try {
                    if ($this->isReferenced($path)) {
                        $this->line("KEEP referenced: {$path}");
                        $referenced++;
                        continue;
                    }

                    $size = (int) $disk->size($path);
                    $bytes += $size;
                    $wouldDelete++;

                    $this->line(sprintf(
                        '%s %s (%s)',
                        $apply ? 'DELETE' : 'WOULD DELETE',
                        $path,
                        $this->formatBytes($size)
                    ));

                    if ($apply) {
                        if ($disk->delete($path)) {
                            $deleted++;
                        } else {
                            $this->warn("DELETE FAILED: {$path}");
                            $failed++;
                        }
                    }
                } catch (Throwable $e) {
                    $this->warn("ERROR {$path}: {$e->getMessage()}");
                    $failed++;
                }
            }
        }

        $this->newLine();
        $this->table(
            ['Result', 'Count'],
            [
                ['Files scanned', $scanned],
                ['PNG/JPG/JPEG candidates', $eligible],
                ['Kept: still referenced', $referenced],
                [$apply ? 'Deleted' : 'Would delete', $apply ? $deleted : $wouldDelete],
                ['Failed', $failed],
            ]
        );

        $this->line(
            ($apply ? 'Deleted size: ' : 'Potential reclaimed size: ')
            . $this->formatBytes($bytes)
        );

        if (!$apply) {
            $this->warn('Review the list. If correct, run again with --apply.');
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function isReferenced(string $path): bool
    {
        $path = ltrim($path, '/');

        if (Media::query()->where('path', $path)->exists()) {
            return true;
        }

        if (Article::query()->where('featured_image', $path)->exists()) {
            return true;
        }

        $candidates = array_values(array_unique([
            $path,
            'storage/' . $path,
            '/storage/' . $path,
            asset('storage/' . $path),
        ]));

        foreach ($candidates as $candidate) {
            if (
                Article::query()
                    ->where('body', 'like', '%' . $candidate . '%')
                    ->exists()
            ) {
                return true;
            }

            if (
                ArticleChapter::query()
                    ->where('body', 'like', '%' . $candidate . '%')
                    ->exists()
            ) {
                return true;
            }
        }

        return false;
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
