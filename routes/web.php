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
    GeminiArticleController as AdminGeminiArticle
};

use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/

Route::get(
    '/',
    [HomeController::class, 'index']
)->name('home');


Route::get(
    '/story/{slug}',
    [ArticleController::class, 'show']
)->name('articles.show');


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


/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/

Route::prefix('admin')
    ->name('admin.')
    ->group(function () {

        /*
        |--------------------------------------------------------------------------
        | ADMIN LOGIN
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | AUTHENTICATED ADMIN
        |--------------------------------------------------------------------------
        */

        Route::middleware('auth')
            ->group(function () {

                /*
                |--------------------------------------------------------------------------
                | LOGOUT
                |--------------------------------------------------------------------------
                */

                Route::post(
                    '/logout',
                    [AdminAuth::class, 'destroy']
                )->name('logout');


                /*
                |--------------------------------------------------------------------------
                | DASHBOARD
                |--------------------------------------------------------------------------
                */

                Route::get(
                    '/',
                    DashboardController::class
                )->name('dashboard');


                /*
                |--------------------------------------------------------------------------
                | DASHBOARD — EXPORT PUBLISHED ARTICLE URLS
                |--------------------------------------------------------------------------
                */

                Route::get(
                    'dashboard/export-urls',
                    [
                        DashboardController::class,
                        'exportUrls',
                    ]
                )->name(
                    'dashboard.export-urls'
                );


                /*
                |--------------------------------------------------------------------------
                | GEMINI AI
                |--------------------------------------------------------------------------
                */

                Route::post(
                    'articles/ai-generate',
                    [
                        AdminGeminiArticle::class,
                        'generate',
                    ]
                )->name(
                    'articles.ai-generate'
                );


                /*
                |--------------------------------------------------------------------------
                | ARTICLE TRASH / RESTORE
                |--------------------------------------------------------------------------
                */

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


                /*
                |--------------------------------------------------------------------------
                | ARTICLE PREVIEW
                |--------------------------------------------------------------------------
                */

                Route::get(
                    'articles/{article}/preview',
                    [
                        AdminArticles::class,
                        'preview',
                    ]
                )->name(
                    'articles.preview'
                );


                /*
                |--------------------------------------------------------------------------
                | ARTICLES
                |--------------------------------------------------------------------------
                */

                Route::resource(
                    'articles',
                    AdminArticles::class
                )->except(['show']);


                /*
                |--------------------------------------------------------------------------
                | ARTISTS
                |--------------------------------------------------------------------------
                */

                Route::resource(
                    'artists',
                    AdminArtists::class
                )->except(['show']);


                /*
                |--------------------------------------------------------------------------
                | CATEGORIES
                |--------------------------------------------------------------------------
                */

                Route::resource(
                    'categories',
                    AdminCategories::class
                )->except(['show']);


                /*
                |--------------------------------------------------------------------------
                | ADMIN-ONLY SETTINGS
                |--------------------------------------------------------------------------
                */

                Route::middleware('admin.role')
                    ->group(function () {

                        /*
                        |--------------------------------------------------------------------------
                        | SITES
                        |--------------------------------------------------------------------------
                        */

                        Route::resource(
                            'sites',
                            AdminSites::class
                        )->except(['show']);


                        /*
                        |--------------------------------------------------------------------------
                        | AD MANAGER
                        |--------------------------------------------------------------------------
                        */

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


                /*
                |--------------------------------------------------------------------------
                | MEDIA
                |--------------------------------------------------------------------------
                */

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


/*
|--------------------------------------------------------------------------
| LOGIN FALLBACK
|--------------------------------------------------------------------------
*/

Route::redirect(
    '/login',
    '/admin/login'
)->name('login');
