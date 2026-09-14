<?php
use App\Http\Controllers\{HomeController,ArticleController,SystemController};
use App\Http\Controllers\Admin\{AuthController as AdminAuth,DashboardController,ArticleController as AdminArticles,ArtistController as AdminArtists,CategoryController as AdminCategories,SiteController as AdminSites,AdSlotController as AdminAds,MediaController as AdminMedia};
use Illuminate\Support\Facades\Route;
Route::get('/',[HomeController::class,'index'])->name('home');
Route::get('/story/{slug}',[ArticleController::class,'show'])->name('articles.show');
Route::view('/about','pages.about')->name('about'); Route::view('/privacy','pages.privacy')->name('privacy'); Route::view('/terms','pages.terms')->name('terms'); Route::view('/editorial-policy','pages.editorial')->name('editorial'); Route::view('/contact','pages.contact')->name('contact');
Route::get('/ads.txt',[SystemController::class,'ads']); Route::get('/robots.txt',[SystemController::class,'robots']); Route::get('/sitemap.xml',[SystemController::class,'sitemap']);
Route::prefix('admin')->name('admin.')->group(function(){
 Route::middleware('guest')->group(function(){Route::get('/login',[AdminAuth::class,'create'])->name('login');Route::post('/login',[AdminAuth::class,'store'])->name('login.store');});
 Route::middleware('auth')->group(function(){Route::post('/logout',[AdminAuth::class,'destroy'])->name('logout');Route::get('/',DashboardController::class)->name('dashboard');Route::get('articles/{article}/preview',[AdminArticles::class,'preview'])->name('articles.preview');Route::resource('articles',AdminArticles::class)->except(['show']);Route::resource('artists',AdminArtists::class)->except(['show']);Route::resource('categories',AdminCategories::class)->except(['show']);Route::middleware('admin.role')->group(function(){Route::resource('sites',AdminSites::class)->except(['show']);Route::get('ads',[AdminAds::class,'index'])->name('ads.index');Route::get('ads/{adSlot}/edit',[AdminAds::class,'edit'])->name('ads.edit');Route::put('ads/{adSlot}',[AdminAds::class,'update'])->name('ads.update');});Route::get('media',[AdminMedia::class,'index'])->name('media.index');Route::post('media',[AdminMedia::class,'store'])->name('media.store');Route::delete('media/{medium}',[AdminMedia::class,'destroy'])->name('media.destroy');});
});
