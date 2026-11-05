<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\NewsSourceController;
use App\Http\Controllers\Admin\PasswordResetController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\UserLoginController;
use App\Http\Controllers\UserRegisterController;
use Illuminate\Support\Facades\Route;

// === PUBLIC (Ziyaretçi & Kullanıcı) ===

// SEO & Sitemap & RSS Feed (IP/kullanıcı rate limit + sunucu önbelleği korumalı)
Route::middleware('throttle:xml-feeds')->group(function (): void {
    Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
    Route::get('/sitemap-news.xml', [SitemapController::class, 'news'])->name('sitemap.news');
    Route::get('/feed', [SitemapController::class, 'feed'])->name('feed');
});

// Ana sayfa ve haberler (arama sorgularında hem dakikalık limit hem günlük kota aktif)
Route::middleware(['throttle:public-browse', 'quota:public_search'])->group(function (): void {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/kategori/{slug}', [HomeController::class, 'index'])->name('category.news');
    Route::get('/haber/{slug}', [HomeController::class, 'show'])->name('news.show');
});

// Ön yüzden haber ekleme/güncelleme (sadece yetkili personel + dakikalık limit + günlük kota)
Route::middleware(['auth', 'role:Super Admin|Admin|Editor', 'throttle:news-write', 'quota:news_write'])->group(function (): void {
    Route::post('/haber-ekle', [HomeController::class, 'store'])->name('news.store');
    Route::put('/haber/{id}', [HomeController::class, 'update'])->name('news.update');
});

// KULLANICI GİRİŞ & KAYIT
Route::get('/login', [UserLoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [UserLoginController::class, 'login'])->name('login.submit')->middleware('throttle:5,1');
Route::post('/logout', [UserLoginController::class, 'logout'])->name('logout');
Route::get('/register', [UserRegisterController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [UserRegisterController::class, 'register'])->name('register.submit')->middleware('throttle:5,1');

// PROFİL & YORUM
Route::middleware('auth')->group(function (): void {
    Route::get('/profil', [ProfileController::class, 'show'])->name('profile');
    Route::put('/profil', [ProfileController::class, 'update'])
        ->name('profile.update')
        ->middleware(['throttle:profile-write', 'quota:profile_write']);

    Route::post('/haber/{news}/yorum', [CommentController::class, 'store'])
        ->name('comments.store')
        ->middleware(['throttle:comments-write', 'quota:comments_write']);
    Route::get('/yorum/{comment}/duzenle', [CommentController::class, 'edit'])->name('comments.edit');
    Route::put('/yorum/{comment}', [CommentController::class, 'update'])
        ->name('comments.update')
        ->middleware('throttle:comments-write');
    Route::delete('/yorum/{comment}', [CommentController::class, 'destroy'])
        ->name('comments.destroy')
        ->middleware('throttle:comments-write');
});

// === ADMIN GİRİŞ & ŞİFRE ===
Route::middleware(['nocache', 'guest'])->group(function (): void {
    Route::get('/admin/login', [LoginController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/admin/login', [LoginController::class, 'login'])->name('admin.login.post')->middleware('throttle:5,1');
    Route::get('/admin/forgot-password', [PasswordResetController::class, 'showForgotPasswordForm'])->name('admin.password.request');
    Route::post('/admin/forgot-password', [PasswordResetController::class, 'sendResetLinkEmail'])->name('admin.password.email')->middleware('throttle:5,1');
    Route::get('/admin/reset-password/{token}', [PasswordResetController::class, 'showResetPasswordForm'])->name('admin.password.reset');
    Route::post('/admin/reset-password', [PasswordResetController::class, 'resetPassword'])->name('admin.password.update')->middleware('throttle:5,1');
});

// === ADMIN PANELİ & PROFİL ===
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['nocache', 'auth'])
    ->group(function (): void {
        Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    });

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['nocache', 'auth', 'role:Super Admin|Admin|Editor'])
    ->group(function (): void {
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        Route::post('/clear-cache', [DashboardController::class, 'clearCache'])
            ->name('clear-cache')
            ->middleware('throttle:10,1');
        Route::post('/fetch-news', [DashboardController::class, 'fetchNews'])
            ->name('fetch-news')
            ->middleware(['throttle:rss-fetch', 'quota:rss_fetch']);
        Route::get('/profile', [AdminProfileController::class, 'show'])->name('profile');
        Route::put('/profile', [AdminProfileController::class, 'update'])
            ->name('profile.update')
            ->middleware(['throttle:profile-write', 'quota:profile_write']);

        // Arama (dakikalık istek limiti korumalı)
        Route::middleware('throttle:admin-search')->group(function (): void {
            Route::get('/news/search', [NewsController::class, 'search'])->name('news.search');
            Route::get('/categories/search', [AdminCategoryController::class, 'search'])->name('categories.search');
            Route::get('/users/search', [UserController::class, 'search'])->name('users.search')->middleware('permission:users.view');
        });

        // Kaynak rotaları
        Route::resource('news', NewsController::class);
        Route::resource('categories', AdminCategoryController::class);
        Route::resource('users', UserController::class)->middleware('permission:users.view');

        // Haber Kaynakları & Etiketler
        Route::post('sources/{source}/fetch', [NewsSourceController::class, 'fetch'])
            ->name('sources.fetch')
            ->middleware(['throttle:rss-fetch', 'quota:rss_fetch']);
        Route::resource('sources', NewsSourceController::class);
        Route::resource('tags', TagController::class);

        // Profil resimleri
        Route::delete('/profile/image', [AdminProfileController::class, 'deleteProfileImage'])
            ->name('profile.image.delete')
            ->middleware(['throttle:profile-write', 'quota:profile_write']);
        Route::get('/profile/image/{filename}', [AdminProfileController::class, 'showProfileImage'])
            ->where('filename', '[A-Za-z0-9._-]+')
            ->name('profile.image.show');
    });
