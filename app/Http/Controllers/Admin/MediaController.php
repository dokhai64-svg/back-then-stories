<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        $query = Media::query()
            ->latest();

        $search = trim(
            (string) $request->query(
                'q',
                ''
            )
        );

        if ($search !== '') {
            $query->where(
                function ($builder) use ($search) {
                    $builder
                        ->where(
                            'filename',
                            'ilike',
                            '%' . $search . '%'
                        )
                        ->orWhere(
                            'alt_text',
                            'ilike',
                            '%' . $search . '%'
                        );
                }
            );
        }

        return view(
            'admin.media.index',
            [
                'media' =>
                    $query
                        ->paginate(36)
                        ->withQueryString(),
                'search' =>
                    $search,
            ]
        );
    }

    public function store(
        Request $request,
        ImageUploadService $images
    ) {
        $request->validate([
            'file' => [
                'nullable',
                'image',
                'max:8192',
            ],
            'files' => [
                'nullable',
                'array',
                'max:20',
            ],
            'files.*' => [
                'image',
                'max:8192',
            ],
            'alt_text' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $files = [];

        if ($request->hasFile('files')) {
            $files =
                $request->file(
                    'files'
                );
        } elseif ($request->hasFile('file')) {
            $files = [
                $request->file(
                    'file'
                ),
            ];
        }

        if (!$files) {
            return back()
                ->withErrors([
                    'file' =>
                        'Choose at least one image.',
                ]);
        }

        $created = [];

        foreach ($files as $file) {
            $created[] =
                $this->createMedia(
                    $request,
                    $images,
                    $file
                );
        }

        if ($request->expectsJson()) {
            return response()->json([
                'items' =>
                    collect($created)
                        ->map(
                            fn (Media $medium) =>
                                $this->payload(
                                    $medium
                                )
                        )
                        ->values(),
            ]);
        }

        return back()->with(
            'ok',
            count($created)
            . ' image(s) uploaded.'
        );
    }

    public function inlineStore(
        Request $request,
        ImageUploadService $images
    ) {
        $request->validate([
            'file' => [
                'required',
                'image',
                'max:8192',
            ],
            'alt_text' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $medium =
            $this->createMedia(
                $request,
                $images,
                $request->file('file')
            );

        return response()->json([
            'item' =>
                $this->payload(
                    $medium
                ),
        ]);
    }

    public function library(Request $request)
    {
        $query =
            Media::query()
                ->latest();

        $search =
            trim(
                (string) $request->query(
                    'q',
                    ''
                )
            );

        if ($search !== '') {
            $query->where(
                function ($builder) use ($search) {
                    $builder
                        ->where(
                            'filename',
                            'ilike',
                            '%' . $search . '%'
                        )
                        ->orWhere(
                            'alt_text',
                            'ilike',
                            '%' . $search . '%'
                        );
                }
            );
        }

        $page =
            $query->paginate(48);

        return response()->json([
            'items' =>
                collect(
                    $page->items()
                )
                    ->map(
                        fn (Media $medium) =>
                            $this->payload(
                                $medium
                            )
                    )
                    ->values(),
            'current_page' =>
                $page->currentPage(),
            'last_page' =>
                $page->lastPage(),
            'has_more' =>
                $page->hasMorePages(),
        ]);
    }

    public function updateAlt(
        Request $request,
        Media $medium
    ) {
        $data =
            $request->validate([
                'alt_text' => [
                    'nullable',
                    'string',
                    'max:255',
                ],
            ]);

        $medium->update([
            'alt_text' =>
                trim(
                    (string) (
                        $data['alt_text']
                        ?? ''
                    )
                ) ?: null,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'item' =>
                    $this->payload(
                        $medium->fresh()
                    ),
            ]);
        }

        return back()->with(
            'ok',
            'Alt text updated.'
        );
    }

    public function destroy(Media $medium)
    {
        Storage::disk(
            $medium->disk
        )->delete(
            $medium->path
        );

        $medium->delete();

        return back()->with(
            'ok',
            'Image deleted.'
        );
    }

    private function createMedia(
        Request $request,
        ImageUploadService $images,
        $file
    ): Media {
        $upload =
            $images->store(
                $file,
                'media'
            );

        $upload['user_id'] =
            $request->user()->id;

        $upload['alt_text'] =
            trim(
                (string) $request->input(
                    'alt_text',
                    ''
                )
            ) ?: null;

        return Media::create(
            $upload
        );
    }

    private function payload(
        Media $medium
    ): array {
        return [
            'id' =>
                $medium->id,
            'url' =>
                asset(
                    'storage/'
                    . $medium->path
                ),
            'filename' =>
                $medium->filename,
            'alt_text' =>
                $medium->alt_text
                ?? '',
            'mime_type' =>
                $medium->mime_type
                ?? '',
            'size' =>
                (int) $medium->size,
            'created_at' =>
                optional(
                    $medium->created_at
                )->toIso8601String(),
        ];
    }
}
