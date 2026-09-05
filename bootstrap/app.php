<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Exceptions\UnauthorizedException;

return Application::configure(basePath: dirname(__DIR__))
    // Route dosyalarını tanımla
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    // Global Middleware tanımları ve aliaslar
    ->withMiddleware(function (Middleware $middleware) {
        // API ile başlayan tüm endpoint'lerde CSRF koruması devre dışı (Laravel 12+)
        $middleware->validateCsrfTokens(except: ['api/*']);

        // Middleware alias tanımları (kısayol ile çağırmak için)
        $middleware->alias([
            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'loginCheck'         => \App\Http\Middleware\loginCheck::class,
        ]);
    })
    // Exception Handler özelleştirme
    ->withExceptions(function (Exceptions $exceptions) {
        // Spatie Permission yetkisiz erişimlerinde uygun response
        $exceptions->render(function (UnauthorizedException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Bu işlem için yetkiniz bulunmamaktadır.',
                ], 403);
            }

            return response()->view('errors.403', [
                'exception' => $e,
            ], 403);
        });
    })
    ->create();
