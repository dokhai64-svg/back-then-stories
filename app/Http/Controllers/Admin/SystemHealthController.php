<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class SystemHealthController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureAdmin($request);

        $checks = [
            'database' =>
                $this->databaseCheck(),
            'public_storage' =>
                $this->publicStorageCheck(),
            'storage_link' =>
                $this->storageLinkCheck(),
            'config_cache' => [
                'ok' =>
                    app()->configurationIsCached(),
                'label' =>
                    app()->configurationIsCached()
                        ? 'Cached'
                        : 'Not cached',
            ],
            'route_cache' => [
                'ok' =>
                    app()->routesAreCached(),
                'label' =>
                    app()->routesAreCached()
                        ? 'Cached'
                        : 'Not cached',
            ],
            'view_cache' =>
                $this->viewCacheCheck(),
        ];

        $stats =
            $this->contentStats();

        $recentErrors =
            $this->recentErrors();

        $backups =
            $this->backups();

        $allCriticalHealthy =
            collect([
                'database',
                'public_storage',
                'storage_link',
            ])->every(
                fn ($key) =>
                    (bool) (
                        $checks[$key]['ok']
                        ?? false
                    )
            );

        return view(
            'admin.system.health',
            compact(
                'checks',
                'stats',
                'recentErrors',
                'backups',
                'allCriticalHealthy'
            )
        );
    }

    public function downloadBackup(
        Request $request,
        string $filename
    ) {
        $this->ensureAdmin($request);

        if (
            !preg_match(
                '/^back-then-stories-[A-Za-z0-9._-]+\.(zip|json)$/',
                $filename
            )
        ) {
            abort(404);
        }

        $path =
            'backups/'
            . $filename;

        if (
            !Storage::disk('local')
                ->exists($path)
        ) {
            abort(404);
        }

        return Storage::disk('local')
            ->download($path);
    }

    private function ensureAdmin(
        Request $request
    ): void {
        abort_unless(
            $request->user()
            && $request->user()->role === 'admin',
            403
        );
    }

    private function databaseCheck(): array
    {
        try {
            DB::select('select 1');

            return [
                'ok' => true,
                'label' => 'Connected',
            ];

        } catch (Throwable $e) {
            return [
                'ok' => false,
                'label' =>
                    'Database connection failed',
            ];
        }
    }

    private function publicStorageCheck(): array
    {
        $probe =
            'healthcheck/.probe';

        try {
            Storage::disk('public')
                ->put(
                    $probe,
                    now()->toIso8601String()
                );

            $ok =
                Storage::disk('public')
                    ->exists($probe);

            Storage::disk('public')
                ->delete($probe);

            return [
                'ok' => $ok,
                'label' =>
                    $ok
                        ? 'Writable'
                        : 'Write test failed',
            ];

        } catch (Throwable $e) {
            return [
                'ok' => false,
                'label' =>
                    'Storage is not writable',
            ];
        }
    }

    private function storageLinkCheck(): array
    {
        $path =
            public_path('storage');

        $ok =
            is_link($path)
            || is_dir($path);

        return [
            'ok' => $ok,
            'label' =>
                $ok
                    ? 'Available'
                    : 'Missing',
        ];
    }

    private function viewCacheCheck(): array
    {
        $path =
            storage_path(
                'framework/views'
            );

        $count = 0;

        try {
            if (is_dir($path)) {
                $items =
                    glob(
                        $path
                        . '/*.php'
                    ) ?: [];

                $count =
                    count($items);
            }
        } catch (Throwable $e) {
            $count = 0;
        }

        return [
            'ok' => $count > 0,
            'label' =>
                $count > 0
                    ? $count
                        . ' compiled view(s)'
                    : 'No compiled views',
        ];
    }

    private function contentStats(): array
    {
        $tables = [
            'articles' =>
                'Articles',
            'article_chapters' =>
                'Chapters',
            'article_aliases' =>
                'Alternate URLs',
            'media' =>
                'Media files',
            'artists' =>
                'Artists',
            'categories' =>
                'Categories',
        ];

        $stats = [];

        foreach (
            $tables
            as $table => $label
        ) {
            if (
                !Schema::hasTable($table)
            ) {
                continue;
            }

            try {
                $stats[] = [
                    'label' =>
                        $label,
                    'value' =>
                        DB::table($table)
                            ->count(),
                ];
            } catch (Throwable $e) {
                // Keep the health page usable
                // even if one optional table fails.
            }
        }

        return $stats;
    }

    private function recentErrors(): array
    {
        $path =
            storage_path(
                'logs/laravel.log'
            );

        if (!is_file($path)) {
            return [];
        }

        try {
            $size =
                filesize($path)
                ?: 0;

            $readBytes =
                min(
                    $size,
                    500000
                );

            $handle =
                fopen(
                    $path,
                    'rb'
                );

            if (!$handle) {
                return [];
            }

            if (
                $size > $readBytes
            ) {
                fseek(
                    $handle,
                    -$readBytes,
                    SEEK_END
                );
            }

            $content =
                stream_get_contents(
                    $handle
                );

            fclose($handle);

            if (!is_string($content)) {
                return [];
            }

            $lines =
                preg_split(
                    '/\R/',
                    $content
                ) ?: [];

            $errors =
                collect($lines)
                    ->filter(
                        fn ($line) =>
                            str_contains(
                                $line,
                                '.ERROR:'
                            )
                    )
                    ->map(
                        function ($line) {
                            $line =
                                preg_replace(
                                    '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',
                                    '',
                                    (string) $line
                                );

                            return Str::limit(
                                trim($line),
                                420,
                                '…'
                            );
                        }
                    )
                    ->filter()
                    ->take(-10)
                    ->values()
                    ->reverse()
                    ->values()
                    ->all();

            return $errors;

        } catch (Throwable $e) {
            return [];
        }
    }

    private function backups(): array
    {
        try {
            $files =
                Storage::disk('local')
                    ->files('backups');

            return collect($files)
                ->filter(
                    fn ($file) =>
                        preg_match(
                            '/\.(zip|json)$/i',
                            $file
                        )
                )
                ->map(
                    function ($file) {
                        $filename =
                            basename($file);

                        $size =
                            Storage::disk('local')
                                ->size($file);

                        $modified =
                            Storage::disk('local')
                                ->lastModified($file);

                        return [
                            'filename' =>
                                $filename,
                            'size' =>
                                $this->humanBytes(
                                    $size
                                ),
                            'modified' =>
                                date(
                                    'Y-m-d H:i:s',
                                    $modified
                                ),
                        ];
                    }
                )
                ->sortByDesc(
                    'modified'
                )
                ->take(12)
                ->values()
                ->all();

        } catch (Throwable $e) {
            return [];
        }
    }

    private function humanBytes(
        int $bytes
    ): string {
        $units = [
            'B',
            'KB',
            'MB',
            'GB',
        ];

        $value =
            max(
                0,
                $bytes
            );

        $unit = 0;

        while (
            $value >= 1024
            && $unit
                < count($units) - 1
        ) {
            $value /= 1024;
            $unit++;
        }

        return number_format(
            $value,
            $unit === 0 ? 0 : 1
        )
            . ' '
            . $units[$unit];
    }
}
