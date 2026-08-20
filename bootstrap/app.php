<?php

use App\Http\Middleware\AuthenticatePlatformAdmin;
use App\Http\Middleware\AuthenticateWali;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ResolveTenant;
use App\Models\Masjid;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Middleware\RoleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(prepend: [
            ResolveTenant::class,
        ]);
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        // Endpoint mobile API (dipakai app Flutter) juga perlu tenant ter-resolusi
        // supaya login/data staf ikut ter-scope per lembaga, bukan cuma sisi web.
        $middleware->api(prepend: [
            ResolveTenant::class,
        ]);

        // Midtrans posts server-to-server without a Laravel session/CSRF token.
        $middleware->validateCsrfTokens(except: [
            'webhook/midtrans',
        ]);

        $middleware->alias([
            'auth.wali' => AuthenticateWali::class,
            'auth.platform' => AuthenticatePlatformAdmin::class,
            'role' => RoleMiddleware::class,
        ]);

        // TrustHosts nonaktif secara default; diaktifkan di sini supaya URL
        // absolut (link email/WA) tidak bisa dipalsukan lewat Host header,
        // sambil tetap menerima domain pusat + subdomain tenant + custom domain
        // yang sudah terverifikasi (daftar custom domain di-cache 5 menit
        // supaya tidak query DB di setiap request).
        $middleware->trustHosts(at: function () {
            $central = config('tenancy.central_domain');

            $customDomains = Cache::remember(
                'trusted-custom-domains',
                300,
                fn () => Masjid::whereNotNull('custom_domain_verified_at')->pluck('custom_domain')->all(),
            );

            return [
                '^'.preg_quote($central, '#').'$',
                '^(.+\.)?'.preg_quote($central, '#').'$',
                ...array_map(fn ($domain) => '^'.preg_quote($domain, '#').'$', $customDomains),
            ];
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
