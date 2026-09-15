<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{
    Article,
    Artist,
    Category,
    Site
};
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
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
                    new Article(['status' => 'draft']),
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

        Article::create($data);

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
            'excerpt' => [
                'nullable',
                'string',
                'max:800',
            ],
            'body' => [
                'required',
                'string',
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
        ) {
            $slug =
                $base . '-' . $index++;
        }

        return $slug;
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
