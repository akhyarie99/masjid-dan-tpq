<?php

namespace App\Http\Middleware;

use App\Models\Masjid;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolusi tenant dari host request:
 *   - domain pusat (config('tenancy.central_domain'))       -> tidak ada tenant (halaman marketing/daftar)
 *   - "{slug}.<central_domain>"                              -> tenant lewat slug
 *   - host lain yang cocok custom_domain terverifikasi        -> tenant lewat custom domain
 *   - selain itu                                              -> 404 (tidak ada fallback diam-diam)
 *
 * Tenant yang resolve disimpan di request attribute (bukan container
 * singleton) supaya lifecycle-nya jelas 1:1 dengan request dan aman kalau
 * nanti aplikasi ini pindah ke Octane/worker persisten.
 */
class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        // Midtrans posts ke satu callback URL tetap terlepas dari tenant mana
        // yang punya donasinya (order_id sudah cukup untuk cari Donation-nya
        // sendiri) — sama seperti pengecualian CSRF untuk route ini di
        // bootstrap/app.php, jangan gerbang lewat resolusi tenant berbasis host.
        if ($request->is('webhook/*')) {
            return $next($request);
        }

        $host = strtolower($request->getHost());
        $centralDomain = strtolower(config('tenancy.central_domain'));

        if ($host === $centralDomain || $host === "www.{$centralDomain}") {
            return $next($request);
        }

        $suffix = ".{$centralDomain}";

        if (str_ends_with($host, $suffix)) {
            $slug = substr($host, 0, -strlen($suffix));

            $masjid = Masjid::where('slug', $slug)->where('is_active', true)->first();

            abort_unless($masjid, 404);

            $request->attributes->set('tenant', $masjid);

            return $next($request);
        }

        $masjid = Masjid::where('custom_domain', $host)->whereNotNull('custom_domain_verified_at')->first();

        abort_unless($masjid, 404);

        $request->attributes->set('tenant', $masjid);

        return $next($request);
    }
}
