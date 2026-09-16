<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;
use ZipArchive;

class BackupContentCommand extends Command
{
    protected $signature =
        'app:backup-content
        {--no-media : Do not include storage/app/public files}
        {--keep=5 : Number of newest backups to keep}';

    protected $description =
        'Create a downloadable content/database backup without .env or user credentials.';

    public function handle(): int
    {
        Storage::disk('local')
            ->makeDirectory(
                'backups'
            );

        $stamp =
            now()->format(
                'Ymd-His'
            );

        $snapshot =
            $this->databaseSnapshot();

        $json =
            json_encode(
                $snapshot,
                JSON_PRETTY_PRINT
                | JSON_UNESCAPED_SLASHES
                | JSON_UNESCAPED_UNICODE
            );

        if ($json === false) {
            $this->error(
                'Could not encode the database snapshot.'
            );

            return self::FAILURE;
        }

        if (
            !class_exists(
                ZipArchive::class
            )
        ) {
            $filename =
                'back-then-stories-'
                . $stamp
                . '.json';

            Storage::disk('local')
                ->put(
                    'backups/'
                    . $filename,
                    $json
                );

            $this->warn(
                'PHP ZipArchive is not available. Created a content-only JSON backup.'
            );

            $this->info(
                'Backup: storage/app/backups/'
                . $filename
            );

            $this->cleanupOldBackups();

            return self::SUCCESS;
        }

        $filename =
            'back-then-stories-'
            . $stamp
            . '.zip';

        $relativePath =
            'backups/'
            . $filename;

        $absolutePath =
            Storage::disk('local')
                ->path($relativePath);

        $zip =
            new ZipArchive();

        if (
            $zip->open(
                $absolutePath,
                ZipArchive::CREATE
                | ZipArchive::OVERWRITE
            ) !== true
        ) {
            $this->error(
                'Could not create the backup ZIP.'
            );

            return self::FAILURE;
        }

        $zip->addFromString(
            'database/content.json',
            $json
        );

        $zip->addFromString(
            'RESTORE_README.txt',
            $this->restoreReadme()
        );

        $mediaIncluded = false;
        $mediaCount = 0;

        if (!$this->option('no-media')) {
            [$mediaIncluded, $mediaCount] =
                $this->addPublicFiles(
                    $zip
                );
        }

        $zip->addFromString(
            'manifest.json',
            json_encode(
                [
                    'created_at' =>
                        now()->toIso8601String(),
                    'app_url' =>
                        config('app.url'),
                    'database_tables' =>
                        array_keys(
                            $snapshot['tables']
                        ),
                    'media_included' =>
                        $mediaIncluded,
                    'media_file_count' =>
                        $mediaCount,
                    'contains_env_file' =>
                        false,
                    'contains_user_credentials' =>
                        false,
                ],
                JSON_PRETTY_PRINT
                | JSON_UNESCAPED_SLASHES
            )
        );

        $zip->close();

        $this->cleanupOldBackups();

        $this->info(
            'Backup created successfully.'
        );

        $this->line(
            'storage/app/backups/'
            . $filename
        );

        if ($mediaIncluded) {
            $this->line(
                'Media files included: '
                . $mediaCount
            );
        }

        $this->newLine();

        $this->warn(
            'Important: download this backup from Admin → System Health so it also exists outside the Railway volume.'
        );

        return self::SUCCESS;
    }

    private function databaseSnapshot(): array
    {
        $tables = [
            'sites',
            'artists',
            'categories',
            'articles',
            'article_aliases',
            'article_chapters',
            'article_daily_views',
            'media',
            'ad_slots',
            'ad_slot_audits',
        ];

        $result = [];

        foreach ($tables as $table) {
            if (
                !Schema::hasTable($table)
            ) {
                continue;
            }

            try {
                $rows =
                    DB::table($table)
                        ->get()
                        ->map(
                            fn ($row) =>
                                (array) $row
                        )
                        ->values()
                        ->all();

                $result[$table] = [
                    'row_count' =>
                        count($rows),
                    'rows' =>
                        $rows,
                ];

            } catch (Throwable $e) {
                $result[$table] = [
                    'row_count' => 0,
                    'rows' => [],
                    'backup_error' =>
                        $e->getMessage(),
                ];
            }
        }

        return [
            'format' =>
                'back-then-stories-content-backup-v1',
            'created_at' =>
                now()->toIso8601String(),
            'tables' =>
                $result,
        ];
    }

    private function addPublicFiles(
        ZipArchive $zip
    ): array {
        $disk =
            Storage::disk('public');

        $files =
            $disk->allFiles();

        $count = 0;

        foreach ($files as $file) {
            if (
                str_starts_with(
                    $file,
                    'healthcheck/'
                )
            ) {
                continue;
            }

            try {
                $absolute =
                    $disk->path($file);

                if (
                    !is_file($absolute)
                ) {
                    continue;
                }

                $zip->addFile(
                    $absolute,
                    'public-storage/'
                    . $file
                );

                $count++;

            } catch (Throwable $e) {
                // Continue the backup even if one
                // individual media file is unreadable.
            }
        }

        return [
            true,
            $count,
        ];
    }

    private function cleanupOldBackups(): void
    {
        $keep =
            max(
                1,
                min(
                    50,
                    (int) $this->option(
                        'keep'
                    )
                )
            );

        try {
            $files =
                collect(
                    Storage::disk('local')
                        ->files(
                            'backups'
                        )
                )
                    ->filter(
                        fn ($file) =>
                            preg_match(
                                '/^backups\/back-then-stories-.*\.(zip|json)$/i',
                                $file
                            )
                    )
                    ->sortByDesc(
                        fn ($file) =>
                            Storage::disk('local')
                                ->lastModified(
                                    $file
                                )
                    )
                    ->values();

            $files
                ->slice($keep)
                ->each(
                    fn ($file) =>
                        Storage::disk('local')
                            ->delete(
                                $file
                            )
                );

        } catch (Throwable $e) {
            $this->warn(
                'Backup created, but old-backup cleanup could not be completed.'
            );
        }
    }

    private function restoreReadme(): string
    {
        return <<<'TXT'
BACK THEN STORIES — RESTORE NOTES
=================================

This archive is designed as a SAFE backup/export.

It contains:
1. database/content.json
   - sites
   - artists
   - categories
   - articles
   - alternate article URLs
   - article chapters
   - daily view history when available
   - media database records
   - ad slots / ad audit history when available

2. public-storage/
   Files from storage/app/public, unless the backup was created with --no-media.

It intentionally DOES NOT contain:
- .env
- API keys
- database passwords
- session data
- cache files
- user passwords / credentials

IMPORTANT RESTORE SAFETY
------------------------
Do not import this backup directly over a live production database.

Recommended restore process:
1. Keep the current production database untouched.
2. Create a fresh staging/test database.
3. Restore public-storage/ into storage/app/public.
4. Review database/content.json.
5. Import data into the matching tables only after confirming the schema.
6. Test articles, chapters, media, aliases and ads.
7. Move to production only after verification.

This project intentionally does not include a one-click destructive restore command.
That protects the live site from accidental data replacement.
TXT;
    }
}
