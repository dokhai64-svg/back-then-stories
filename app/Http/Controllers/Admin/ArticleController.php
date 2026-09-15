<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{
    Article,
    ArticleAlias,
    ArticleChapter,
    Artist,
    Category,
    Site
};
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $scope = in_array(
            $request->get('scope'),
            ['active', 'trash', 'all'],
            true
        )
            ? $request->get('scope')
            : 'active';

        $query = Article::query();

        if ($scope === 'trash') {
            $query->onlyTrashed();
        } elseif ($scope === 'all') {
            $query->withTrashed();
        }

        $query
            ->with([
                'site',
                'artist',
                'category',
                'author',
                'aliases',
            ])
            ->when(
                $request->filled('q'),
                function ($q) use ($request) {
                    $search = trim(
                        (string) $request->get('q')
                    );

                    $q->where(function ($sub) use ($search) {
                        $sub
                            ->where(
                                'title',
                                'like',
                                '%' . $search . '%'
                            )
                            ->orWhere(
                                'slug',
                                'like',
                                '%' . $search . '%'
                            );
                    });
                }
            )
            ->when(
                $request->filled('status'),
                fn ($q) =>
                    $q->where(
                        'status',
                        $request->get('status')
                    )
            )
            ->when(
                $request->filled('category_id'),
                fn ($q) =>
                    $q->where(
                        'category_id',
                        $request->integer('category_id')
                    )
            )
            ->when(
                $request->filled('site_id'),
                fn ($q) =>
                    $q->where(
                        'site_id',
                        $request->integer('site_id')
                    )
            )
            ->when(
                $request->filled('published_from'),
                fn ($q) =>
                    $q->whereDate(
                        'published_at',
                        '>=',
                        $request->get('published_from')
                    )
            )
            ->when(
                $request->filled('published_to'),
                fn ($q) =>
                    $q->whereDate(
                        'published_at',
                        '<=',
                        $request->get('published_to')
                    )
            );

        $allowedSorts = [
            'title',
            'views',
            'published_at',
            'created_at',
        ];

        $sort = in_array(
            $request->get('sort'),
            $allowedSorts,
            true
        )
            ? $request->get('sort')
            : 'created_at';

        $direction =
            $request->get('direction') === 'asc'
                ? 'asc'
                : 'desc';

        $allowedPerPage = [10, 20, 50, 100];

        $perPage = in_array(
            $request->integer('per_page'),
            $allowedPerPage,
            true
        )
            ? $request->integer('per_page')
            : 20;

        $articles = $query
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        $counts = [
            'active' => Article::count(),
            'trash' => Article::onlyTrashed()->count(),
            'all' => Article::withTrashed()->count(),
        ];

        return view(
            'admin.articles.index',
            [
                'articles' => $articles,
                'sites' => Site::orderBy('name')->get(),
                'categories' =>
                    Category::orderBy('name')->get(),
                'counts' => $counts,
                'scope' => $scope,
                'sort' => $sort,
                'direction' => $direction,
                'perPageOptions' => $allowedPerPage,
            ]
        );
    }

    public function create()
    {
        return view(
            'admin.articles.form',
            $this->lookups() + [
                'article' =>
                    new Article([
                        'status' => 'draft',
                        'content_mode' => 'normal',
                    ]),
            ]
        );
    }

    public function store(
        Request $request,
        ImageUploadService $images
    ) {
        $data = $this->validated($request);

        $data['user_id'] =
            $request->user()->id;

        $data['content_mode'] =
            $data['content_mode']
            ?? 'normal';

        $data['skip_intro'] =
            $request->boolean(
                'skip_intro'
            );

        $chaptersJson =
            $data['chapters_json']
            ?? null;

        unset(
            $data['chapters_json']
        );

        $data['body'] =
            trim(
                (string) (
                    $data['body']
                    ?? ''
                )
            ) !== ''
                ? $data['body']
                : '<p></p>';

        $data['slug'] = $this->slug(
            $data['site_id'],
            $data['title'],
            $data['slug'] ?? null
        );

        $importedImageUrl =
            $data['imported_featured_image_url']
            ?? null;

        unset(
            $data['imported_featured_image_url']
        );

        if ($request->hasFile('featured_image_file')) {
            $upload = $images->store(
                $request->file('featured_image_file'),
                'articles'
            );

            $data['featured_image'] =
                $upload['path'];

        } elseif ($importedImageUrl) {
            $downloaded =
                $this->downloadRemoteImage(
                    $importedImageUrl
                );

            if ($downloaded) {
                $data['featured_image'] =
                    $downloaded;
            }
        }

        $this->normalizePublish($data);

        $article =
            Article::create($data);

        $this->syncChaptersFromJson(
            $article,
            $chaptersJson
        );

        return redirect()
            ->route(
                'admin.articles.index',
                ['scope' => 'active']
            )
            ->with(
                'ok',
                'Article created.'
            );
    }

    public function edit(Article $article)
    {
        $article->load([
            'aliases',
            'chapters',
        ]);

        return view(
            'admin.articles.form',
            $this->lookups() + compact('article')
        );
    }

    public function preview(Article $article)
    {
        $article->load([
            'artist',
            'category',
            'author',
            'chapters',
        ]);

        $related = Article::query()
            ->where(
                'site_id',
                $article->site_id
            )
            ->whereKeyNot($article->id)
            ->latest()
            ->take(4)
            ->get();

        return view(
            'articles.show',
            compact('article', 'related')
        );
    }

    public function update(
        Request $request,
        Article $article,
        ImageUploadService $images
    ) {
        $data = $this->validated(
            $request,
            $article
        );

        $data['content_mode'] =
            $data['content_mode']
            ?? 'normal';

        $data['skip_intro'] =
            $request->boolean(
                'skip_intro'
            );

        $chaptersJson =
            $data['chapters_json']
            ?? null;

        unset(
            $data['chapters_json']
        );

        $data['body'] =
            trim(
                (string) (
                    $data['body']
                    ?? ''
                )
            ) !== ''
                ? $data['body']
                : '<p></p>';

        $data['slug'] = $this->slug(
            $data['site_id'],
            $data['title'],
            $data['slug'] ?? null,
            $article->id
        );

        $importedImageUrl =
            $data['imported_featured_image_url']
            ?? null;

        unset(
            $data['imported_featured_image_url']
        );

        if ($request->hasFile('featured_image_file')) {
            if ($article->featured_image) {
                Storage::disk('public')
                    ->delete(
                        $article->featured_image
                    );
            }

            $upload = $images->store(
                $request->file('featured_image_file'),
                'articles'
            );

            $data['featured_image'] =
                $upload['path'];

        } elseif ($importedImageUrl) {
            $downloaded =
                $this->downloadRemoteImage(
                    $importedImageUrl
                );

            if ($downloaded) {
                if ($article->featured_image) {
                    Storage::disk('public')
                        ->delete(
                            $article->featured_image
                        );
                }

                $data['featured_image'] =
                    $downloaded;
            }
        }

        $this->normalizePublish($data);

        $article->update($data);

        $this->syncChaptersFromJson(
            $article,
            $chaptersJson
        );

        $this->syncAliasSite(
            $article
        );

        return redirect()
            ->route(
                'admin.articles.index',
                ['scope' => 'active']
            )
            ->with(
                'ok',
                'Article saved.'
            );
    }

    public function generateAliases(
        Request $request,
        Article $article
    ) {
        $data = $request->validate([
            'count' => [
                'required',
                'integer',
                'min:1',
                'max:20',
            ],
        ]);

        DB::transaction(
            function () use (
                $article,
                $data
            ) {
                for (
                    $i = 0;
                    $i < $data['count'];
                    $i++
                ) {
                    $article->aliases()
                        ->create([
                            'site_id' =>
                                $article->site_id,
                            'slug' =>
                                $this->uniqueAliasSlug(
                                    $article
                                ),
                        ]);
                }
            }
        );

        return response()->json([
            'message' =>
                $data['count']
                . ' alternate URL(s) created.',
            'aliases' =>
                $this->aliasesPayload(
                    $article
                ),
        ]);
    }

    public function storeAlias(
        Request $request,
        Article $article
    ) {
        $data = $request->validate([
            'slug' => [
                'required',
                'string',
                'max:255',
            ],
        ]);

        $slug =
            Str::slug(
                $data['slug']
            );

        if ($slug === '') {
            return response()->json([
                'message' =>
                    'Please enter a valid alternate slug.',
            ], 422);
        }

        if (
            !$this->aliasSlugAvailable(
                $article,
                $slug
            )
        ) {
            return response()->json([
                'message' =>
                    'That slug is already in use.',
            ], 422);
        }

        $article->aliases()
            ->create([
                'site_id' =>
                    $article->site_id,
                'slug' =>
                    $slug,
            ]);

        return response()->json([
            'message' =>
                'Alternate URL added.',
            'aliases' =>
                $this->aliasesPayload(
                    $article
                ),
        ]);
    }

    public function destroyAlias(
        Article $article,
        ArticleAlias $alias
    ) {
        abort_unless(
            (int) $alias->article_id
            === (int) $article->id,
            404
        );

        $alias->delete();

        return response()->json([
            'message' =>
                'Alternate URL removed.',
            'aliases' =>
                $this->aliasesPayload(
                    $article
                ),
        ]);
    }

    public function storeChapter(
        Request $request,
        Article $article
    ) {
        $data = $request->validate([
            'title' => [
                'nullable',
                'string',
                'max:255',
            ],
            'body' => [
                'required',
                'string',
                'min:40',
            ],
        ]);

        $nextNumber =
            ((int) $article->chapters()
                ->max('chapter_number'))
            + 1;

        $chapter =
            $article->chapters()
                ->create([
                    'chapter_number' =>
                        $nextNumber,
                    'title' =>
                        trim(
                            (string) (
                                $data['title']
                                ?? ''
                            )
                        ) ?: null,
                    'body' =>
                        $data['body'],
                ]);

        $article->update([
            'content_mode' =>
                'chapter',
        ]);

        return response()->json([
            'message' =>
                'Chapter created.',
            'chapter' =>
                $this->chapterPayload(
                    $article,
                    $chapter
                ),
            'chapters' =>
                $this->chaptersPayload(
                    $article
                ),
        ]);
    }

    public function updateChapter(
        Request $request,
        Article $article,
        ArticleChapter $chapter
    ) {
        $this->assertChapterOwner(
            $article,
            $chapter
        );

        $data = $request->validate([
            'title' => [
                'nullable',
                'string',
                'max:255',
            ],
            'body' => [
                'required',
                'string',
                'min:40',
            ],
        ]);

        $chapter->update([
            'title' =>
                trim(
                    (string) (
                        $data['title']
                        ?? ''
                    )
                ) ?: null,
            'body' =>
                $data['body'],
        ]);

        return response()->json([
            'message' =>
                'Chapter saved.',
            'chapters' =>
                $this->chaptersPayload(
                    $article
                ),
        ]);
    }

    public function destroyChapter(
        Article $article,
        ArticleChapter $chapter
    ) {
        $this->assertChapterOwner(
            $article,
            $chapter
        );

        $chapter->delete();

        return response()->json([
            'message' =>
                'Chapter deleted.',
            'chapters' =>
                $this->chaptersPayload(
                    $article
                ),
        ]);
    }

    public function destroy(Article $article)
    {
        $article->delete();

        return redirect()
            ->route(
                'admin.articles.index',
                ['scope' => 'active']
            )
            ->with(
                'ok',
                'Article moved to trash.'
            );
    }

    public function restore(int $articleId)
    {
        $article = Article::onlyTrashed()
            ->findOrFail($articleId);

        $article->restore();

        return redirect()
            ->route(
                'admin.articles.index',
                ['scope' => 'trash']
            )
            ->with(
                'ok',
                'Article restored.'
            );
    }

    public function forceDelete(int $articleId)
    {
        $article = Article::onlyTrashed()
            ->findOrFail($articleId);

        if ($article->featured_image) {
            Storage::disk('public')
                ->delete(
                    $article->featured_image
                );
        }

        $article->forceDelete();

        return redirect()
            ->route(
                'admin.articles.index',
                ['scope' => 'trash']
            )
            ->with(
                'ok',
                'Article permanently deleted.'
            );
    }

    private function validated(
        Request $request,
        ?Article $article = null
    ): array {
        return $request->validate([
            'site_id' => [
                'required',
                'exists:sites,id',
            ],
            'artist_id' => [
                'nullable',
                'exists:artists,id',
            ],
            'category_id' => [
                'nullable',
                'exists:categories,id',
            ],
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
            ],
            'content_mode' => [
                'required',
                Rule::in([
                    'normal',
                    'chapter',
                ]),
            ],
            'skip_intro' => [
                'nullable',
                'boolean',
            ],
            'chapters_json' => [
                'nullable',
                'string',
                'max:1000000',
            ],
            'excerpt' => [
                'nullable',
                'string',
                'max:800',
            ],
            'body' => [
                'nullable',
                'string',
                'required_if:content_mode,normal',
            ],
            'featured_image_file' => [
                'nullable',
                'image',
                'max:8192',
            ],
            'imported_featured_image_url' => [
                'nullable',
                'url',
                'max:2048',
            ],
            'youtube_url' => [
                'nullable',
                'url',
                'max:500',
            ],
            'seo_title' => [
                'nullable',
                'string',
                'max:255',
            ],
            'meta_description' => [
                'nullable',
                'string',
                'max:320',
            ],
            'facebook_hook' => [
                'nullable',
                'string',
                'max:1500',
            ],
            'status' => [
                'required',
                Rule::in([
                    'draft',
                    'review',
                    'scheduled',
                    'published',
                ]),
            ],
            'published_at' => [
                'nullable',
                'date',
            ],
            'featured' => [
                'nullable',
                'boolean',
            ],
        ]);
    }

    private function lookups(): array
    {
        return [
            'sites' =>
                Site::where(
                    'active',
                    true
                )
                    ->orderBy('name')
                    ->get(),

            'artists' =>
                Artist::orderBy('name')
                    ->get(),

            'categories' =>
                Category::orderBy('name')
                    ->get(),
        ];
    }

    private function slug(
        int $siteId,
        string $title,
        ?string $input = null,
        ?int $ignore = null
    ): string {
        $base =
            Str::slug(
                $input ?: $title
            )
            ?: Str::random(8);

        $slug = $base;
        $index = 2;

        while (
            Article::withTrashed()
                ->where(
                    'site_id',
                    $siteId
                )
                ->where(
                    'slug',
                    $slug
                )
                ->when(
                    $ignore,
                    fn ($q) =>
                        $q->whereKeyNot(
                            $ignore
                        )
                )
                ->exists()
            ||
            ArticleAlias::query()
                ->where(
                    'site_id',
                    $siteId
                )
                ->where(
                    'slug',
                    $slug
                )
                ->exists()
        ) {
            $slug =
                $base . '-' . $index++;
        }

        return $slug;
    }

    private function syncChaptersFromJson(
        Article $article,
        ?string $chaptersJson
    ): void {
        if ($chaptersJson === null) {
            return;
        }

        $decoded =
            json_decode(
                $chaptersJson,
                true
            );

        if (!is_array($decoded)) {
            return;
        }

        $decoded =
            array_slice(
                $decoded,
                0,
                30
            );

        $existing =
            $article->chapters()
                ->get()
                ->keyBy('id');

        $keptIds = [];
        $number = 1;

        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }

            $body =
                trim(
                    (string) (
                        $row['body']
                        ?? ''
                    )
                );

            if ($body === '') {
                continue;
            }

            $title =
                trim(
                    (string) (
                        $row['title']
                        ?? ''
                    )
                );

            $payload = [
                'chapter_number' =>
                    $number++,
                'title' =>
                    $title !== ''
                        ? Str::limit(
                            $title,
                            255,
                            ''
                        )
                        : null,
                'body' =>
                    Str::limit(
                        $body,
                        120000,
                        ''
                    ),
            ];

            $id =
                isset($row['id'])
                    ? (int) $row['id']
                    : 0;

            if (
                $id > 0 &&
                $existing->has($id)
            ) {
                $chapter =
                    $existing->get($id);

                $chapter->update(
                    $payload
                );

                $keptIds[] =
                    $chapter->id;

                continue;
            }

            $chapter =
                $article->chapters()
                    ->create(
                        $payload
                    );

            $keptIds[] =
                $chapter->id;
        }

        $deleteQuery =
            $article->chapters();

        if ($keptIds) {
            $deleteQuery->whereNotIn(
                'id',
                $keptIds
            );
        }

        $deleteQuery->delete();
    }

    private function aliasesPayload(
        Article $article
    ) {
        return $article->aliases()
            ->orderBy('id')
            ->get()
            ->map(
                fn ($alias) => [
                    'id' =>
                        $alias->id,
                    'slug' =>
                        $alias->slug,
                    'url' =>
                        route(
                            'articles.show',
                            [
                                'slug' =>
                                    $alias->slug,
                            ]
                        ),
                ]
            )
            ->values();
    }

    private function chaptersPayload(
        Article $article
    ) {
        return $article->chapters()
            ->orderBy(
                'chapter_number'
            )
            ->get()
            ->map(
                fn ($chapter) =>
                    $this->chapterPayload(
                        $article,
                        $chapter
                    )
            )
            ->values();
    }

    private function chapterPayload(
        Article $article,
        ArticleChapter $chapter
    ): array {
        return [
            'id' =>
                $chapter->id,
            'chapter_number' =>
                $chapter->chapter_number,
            'title' =>
                $chapter->title,
            'body' =>
                $chapter->body,
            'views' =>
                (int) $chapter->views,
            'url' =>
                route(
                    'articles.chapter',
                    [
                        'slug' =>
                            $article->slug,
                        'chapterNumber' =>
                            $chapter->chapter_number,
                    ]
                ),
        ];
    }

    private function assertChapterOwner(
        Article $article,
        ArticleChapter $chapter
    ): void {
        abort_unless(
            (int) $chapter->article_id
            === (int) $article->id,
            404
        );
    }

    private function aliasSlugAvailable(
        Article $article,
        string $slug,
        ?int $ignoreAliasId = null
    ): bool {
        if (
            Article::withTrashed()
                ->where(
                    'site_id',
                    $article->site_id
                )
                ->where(
                    'slug',
                    $slug
                )
                ->exists()
        ) {
            return false;
        }

        return !ArticleAlias::query()
            ->where(
                'site_id',
                $article->site_id
            )
            ->where(
                'slug',
                $slug
            )
            ->when(
                $ignoreAliasId,
                fn ($q) =>
                    $q->whereKeyNot(
                        $ignoreAliasId
                    )
            )
            ->exists();
    }

    private function uniqueAliasSlug(
        Article $article,
        ?string $preferred = null,
        ?int $ignoreAliasId = null
    ): string {
        $base =
            Str::slug(
                $preferred
                ?: $article->title
            )
            ?: 'story';

        do {
            $slug =
                $base
                . '-'
                . Str::lower(
                    Str::random(6)
                );
        } while (
            !$this->aliasSlugAvailable(
                $article,
                $slug,
                $ignoreAliasId
            )
        );

        return $slug;
    }

    private function syncAliasSite(
        Article $article
    ): void {
        $aliases =
            $article->aliases()
                ->get();

        foreach ($aliases as $alias) {
            $slug =
                $alias->slug;

            $conflict =
                Article::withTrashed()
                    ->where(
                        'site_id',
                        $article->site_id
                    )
                    ->where(
                        'slug',
                        $slug
                    )
                    ->exists()
                ||
                ArticleAlias::query()
                    ->where(
                        'site_id',
                        $article->site_id
                    )
                    ->where(
                        'slug',
                        $slug
                    )
                    ->whereKeyNot(
                        $alias->id
                    )
                    ->exists();

            if ($conflict) {
                $slug =
                    $this->uniqueAliasSlug(
                        $article,
                        $slug,
                        $alias->id
                    );
            }

            $alias->update([
                'site_id' =>
                    $article->site_id,
                'slug' =>
                    $slug,
            ]);
        }
    }

    private function downloadRemoteImage(
        string $url
    ): ?string {
        try {
            $parts =
                parse_url($url);

            if (
                !is_array($parts) ||
                !isset(
                    $parts['scheme'],
                    $parts['host']
                ) ||
                !in_array(
                    mb_strtolower(
                        $parts['scheme']
                    ),
                    ['http', 'https'],
                    true
                )
            ) {
                return null;
            }

            $host =
                mb_strtolower(
                    (string) $parts['host']
                );

            $ips =
                gethostbynamel($host)
                ?: [];

            if (
                filter_var(
                    $host,
                    FILTER_VALIDATE_IP
                )
            ) {
                $ips[] = $host;
            }

            if (!$ips) {
                return null;
            }

            foreach (
                array_unique($ips)
                as $ip
            ) {
                if (
                    !filter_var(
                        $ip,
                        FILTER_VALIDATE_IP,
                        FILTER_FLAG_NO_PRIV_RANGE
                        | FILTER_FLAG_NO_RES_RANGE
                    )
                ) {
                    return null;
                }
            }

            $response =
                Http::withHeaders([
                    'User-Agent' =>
                        'Mozilla/5.0 (compatible; BackThenStoriesImporter/1.0)',
                    'Accept' =>
                        'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
                ])
                    ->withOptions([
                        'allow_redirects' => false,
                    ])
                    ->connectTimeout(3)
                    ->timeout(10)
                    ->get($url);

            if (!$response->successful()) {
                return null;
            }

            $contentType =
                mb_strtolower(
                    trim(
                        explode(
                            ';',
                            (string) $response->header(
                                'Content-Type'
                            )
                        )[0]
                    )
                );

            $extensions = [
                'image/jpeg' => 'jpg',
                'image/jpg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                'image/gif' => 'gif',
            ];

            if (
                !isset(
                    $extensions[$contentType]
                )
            ) {
                return null;
            }

            $contents =
                (string) $response->body();

            if (
                $contents === '' ||
                strlen($contents)
                    > 8 * 1024 * 1024
            ) {
                return null;
            }

            $path =
                'articles/imported/'
                . Str::uuid()
                . '.'
                . $extensions[$contentType];

            Storage::disk('public')
                ->put(
                    $path,
                    $contents
                );

            return $path;

        } catch (\Throwable $e) {
            logger()->warning(
                'Imported featured image download failed',
                [
                    'url' => $url,
                    'message' => $e->getMessage(),
                ]
            );

            return null;
        }
    }

    private function normalizePublish(
        array &$data
    ): void {
        $data['featured'] =
            !empty($data['featured']);

        if (
            $data['status'] === 'published' &&
            empty($data['published_at'])
        ) {
            $data['published_at'] =
                now();
        }

        if (
            $data['status'] === 'scheduled' &&
            empty($data['published_at'])
        ) {
            $data['published_at'] =
                now()->addHour();
        }

        if (
            in_array(
                $data['status'],
                ['draft', 'review'],
                true
            )
        ) {
            $data['published_at'] =
                $data['published_at'] ?? null;
        }
    }
}
