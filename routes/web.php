<?php

use App\Http\Controllers\{
    HomeController,
    ArticleController,
    SystemController
};

use App\Http\Controllers\Admin\{
    AuthController as AdminAuth,
    DashboardController,
    ArticleController as AdminArticles,
    ArtistController as AdminArtists,
    CategoryController as AdminCategories,
    SiteController as AdminSites,
    AdSlotController as AdminAds,
    MediaController as AdminMedia,
    GeminiArticleController as AdminGeminiArticle,
    ArticleImportController as AdminArticleImport
};

use Illuminate\Support\Facades\Route;

Route::get(
    '/',
    [HomeController::class, 'index']
)->name('home');

Route::get(
    '/story/{slug}',
    [ArticleController::class, 'show']
)->name('articles.show');

Route::get(
    '/story/{slug}/chapter/{chapterNumber}',
    [ArticleController::class, 'chapter']
)
    ->whereNumber('chapterNumber')
    ->name('articles.chapter');


Route::view(
    '/about',
    'pages.about'
)->name('about');

Route::view(
    '/privacy',
    'pages.privacy'
)->name('privacy');

Route::view(
    '/terms',
    'pages.terms'
)->name('terms');

Route::view(
    '/editorial-policy',
    'pages.editorial'
)->name('editorial');

Route::view(
    '/contact',
    'pages.contact'
)->name('contact');

Route::get(
    '/ads.txt',
    [SystemController::class, 'ads']
);

Route::get(
    '/robots.txt',
    [SystemController::class, 'robots']
);

Route::get(
    '/sitemap.xml',
    [SystemController::class, 'sitemap']
);

Route::prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::middleware('guest')
            ->group(function () {

                Route::get(
                    '/login',
                    [AdminAuth::class, 'create']
                )->name('login');

                Route::post(
                    '/login',
                    [AdminAuth::class, 'store']
                )->name('login.store');
            });

        Route::middleware('auth')
            ->group(function () {

                Route::post(
                    '/logout',
                    [AdminAuth::class, 'destroy']
                )->name('logout');

                Route::get(
                    '/',
                    DashboardController::class
                )->name('dashboard');

                Route::get(
                    'dashboard/export-urls',
                    [
                        DashboardController::class,
                        'exportUrls',
                    ]
                )->name(
                    'dashboard.export-urls'
                );

                Route::post(
                    'articles/ai-generate',
                    [
                        AdminGeminiArticle::class,
                        'generate',
                    ]
                )->name(
                    'articles.ai-generate'
                );

                Route::post(
                    'articles/import-url',
                    [
                        AdminArticleImport::class,
                        'import',
                    ]
                )
                    ->middleware('throttle:10,1')
                    ->name(
                        'articles.import-url'
                    );

                Route::post(
                    'articles/{article}/aliases/generate',
                    [
                        AdminArticles::class,
                        'generateAliases',
                    ]
                )->name(
                    'articles.aliases.generate'
                );

                Route::post(
                    'articles/{article}/aliases',
                    [
                        AdminArticles::class,
                        'storeAlias',
                    ]
                )->name(
                    'articles.aliases.store'
                );

                Route::delete(
                    'articles/{article}/aliases/{alias}',
                    [
                        AdminArticles::class,
                        'destroyAlias',
                    ]
                )->name(
                    'articles.aliases.destroy'
                );

                Route::post(
                    'articles/{article}/chapters',
                    [
                        AdminArticles::class,
                        'storeChapter',
                    ]
                )->name(
                    'articles.chapters.store'
                );

                Route::put(
                    'articles/{article}/chapters/{chapter}',
                    [
                        AdminArticles::class,
                        'updateChapter',
                    ]
                )->name(
                    'articles.chapters.update'
                );

                Route::delete(
                    'articles/{article}/chapters/{chapter}',
                    [
                        AdminArticles::class,
                        'destroyChapter',
                    ]
                )->name(
                    'articles.chapters.destroy'
                );

                Route::post(
                    'articles/{articleId}/restore',
                    [
                        AdminArticles::class,
                        'restore',
                    ]
                )->name(
                    'articles.restore'
                );

                Route::delete(
                    'articles/{articleId}/force',
                    [
                        AdminArticles::class,
                        'forceDelete',
                    ]
                )->name(
                    'articles.force-delete'
                );

                Route::get(
                    'articles/{article}/preview',
                    [
                        AdminArticles::class,
                        'preview',
                    ]
                )->name(
                    'articles.preview'
                );

                Route::resource(
                    'articles',
                    AdminArticles::class
                )->except(['show']);

                Route::resource(
                    'artists',
                    AdminArtists::class
                )->except(['show']);

                Route::resource(
                    'categories',
                    AdminCategories::class
                )->except(['show']);

                Route::middleware('admin.role')
                    ->group(function () {

                        Route::resource(
                            'sites',
                            AdminSites::class
                        )->except(['show']);

                        Route::get(
                            'ads',
                            [
                                AdminAds::class,
                                'index',
                            ]
                        )->name('ads.index');

                        Route::get(
                            'ads/{adSlot}/edit',
                            [
                                AdminAds::class,
                                'edit',
                            ]
                        )->name('ads.edit');

                        Route::put(
                            'ads/{adSlot}',
                            [
                                AdminAds::class,
                                'update',
                            ]
                        )->name('ads.update');
                    });

                Route::get(
                    'media',
                    [
                        AdminMedia::class,
                        'index',
                    ]
                )->name('media.index');

                Route::post(
                    'media',
                    [
                        AdminMedia::class,
                        'store',
                    ]
                )->name('media.store');

                Route::delete(
                    'media/{medium}',
                    [
                        AdminMedia::class,
                        'destroy',
                    ]
                )->name('media.destroy');
            });
    });

Route::redirect(
    '/login',
    '/admin/login'
)->name('login');
