<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\ArticleChapter;
use App\Models\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CleanupMigratedOriginals extends Command
{
    protected $signature = 'media:cleanup-migrated-originals
        {--apply : Actually delete verified unreferenced original files}
        {--manifest= : Optional single manifest filename under media-migration-backups}';

    protected $description = 'Safely delete old original media files recorded by migration manifests only when no database references remain.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $this->newLine();
        $this->info('Back Then Stories - Safe Migrated Originals Cleanup');
        $this->line('Mode: ' . ($apply ? 'APPLY' : 'DRY RUN'));
        $this->line('Only originals recorded in migration manifests are eligible.');
        $this->newLine();

        if (!$apply) {
            $this->warn('DRY RUN only. No files will be deleted.');
        }

        $local = Storage::disk('local');
        $manifestDir = 'media-migration-backups';

        if (!$local->exists($manifestDir)) {
            $this->error('No migration backup directory found.');
            return self::FAILURE;
        }

        $requested = trim((string) ($this->option('manifest') ?? ''));
        $manifestFiles = [];

        if ($requested !== '') {
            $path = $manifestDir . '/' . basename($requested);

            if (!$local->exists($path)) {
                $this->error("Manifest not found: {$path}");
                return self::FAILURE;
            }

            $manifestFiles = [$path];
        } else {
            $manifestFiles = collect($local->files($manifestDir))
                ->filter(fn ($path) => str_ends_with(strtolower($path), '.json'))
                ->sort()
                ->values()
                ->all();
        }

        if (!$manifestFiles) {
            $this->warn('No JSON migration manifests found.');
            return self::SUCCESS;
        }

        $items = [];

        foreach ($manifestFiles as $manifestFile) {
            try {
                $decoded = json_decode($local->get($manifestFile), true);

                if (!is_array($decoded)) {
                    $this->warn("Skipping invalid manifest: {$manifestFile}");
                    continue;
                }

                foreach (($decoded['items'] ?? []) as $row) {
                    if (!is_array($row)) {
                        continue;
                    }

                    $oldPath = trim((string) ($row['old_path'] ?? ''));
                    $newPath = trim((string) ($row['new_path'] ?? ''));
                    $disk = trim((string) ($row['disk'] ?? 'public')) ?: 'public';

                    if ($oldPath === '' || $newPath === '' || $oldPath === $newPath) {
                        continue;
                    }

                    $key = $disk . '|' . $oldPath;

                    // Keep the latest mapping seen for a given original path.
                    $items[$key] = [
                        'disk' => $disk,
                        'old_path' => $oldPath,
                        'new_path' => $newPath,
                        'type' => (string) ($row['type'] ?? ''),
                        'id' => $row['id'] ?? null,
                        'manifest' => $manifestFile,
                    ];
                }
            } catch (Throwable $e) {
                $this->warn("Failed reading {$manifestFile}: {$e->getMessage()}");
            }
        }

        if (!$items) {
            $this->warn('No migrated original files were found in manifests.');
            return self::SUCCESS;
        }

        $this->info('Unique original paths found: ' . count($items));

        $deletable = 0;
        $deleted = 0;
        $keptReferenced = 0;
        $missingOriginal = 0;
        $missingNew = 0;
        $failed = 0;

        foreach ($items as $row) {
            $disk = $row['disk'];
            $oldPath = $row['old_path'];
            $newPath = $row['new_path'];

            try {
                $storage = Storage::disk($disk);

                if (!$storage->exists($oldPath)) {
                    $this->line("MISSING OLD: {$oldPath}");
                    $missingOriginal++;
                    continue;
                }

                if (!$storage->exists($newPath)) {
                    $this->warn("KEEP - new optimized file missing: {$newPath}");
                    $missingNew++;
                    continue;
                }

                if ($this->isStillReferenced($oldPath)) {
                    $this->warn("KEEP - still referenced: {$oldPath}");
                    $keptReferenced++;
                    continue;
                }

                $size = (int) $storage->size($oldPath);
                $this->line(sprintf(
                    '%s %s (%s)',
                    $apply ? 'DELETE' : 'WOULD DELETE',
                    $oldPath,
                    $this->formatBytes($size)
                ));

                $deletable++;

                if ($apply) {
                    if ($storage->delete($oldPath)) {
                        $deleted++;
                    } else {
                        $this->warn("DELETE FAILED: {$oldPath}");
                        $failed++;
                    }
                }
            } catch (Throwable $e) {
                $this->warn("ERROR {$oldPath}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->newLine();
        $this->table(
            ['Result', 'Count'],
            [
                ['Eligible / would delete', $deletable],
                ['Actually deleted', $deleted],
                ['Kept: still referenced', $keptReferenced],
                ['Skipped: old file already missing', $missingOriginal],
                ['Kept: optimized file missing', $missingNew],
                ['Failed', $failed],
            ]
        );

        if (!$apply) {
            $this->warn('Review the DRY RUN. If correct, run again with --apply.');
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function isStillReferenced(string $oldPath): bool
    {
        $path = ltrim($oldPath, '/');

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
