<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Masjid;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Dipanggil oleh Caddy's on-demand TLS "ask" directive sebelum menerbitkan
 * sertifikat untuk hostname yang belum dikenal — mencegah orang mengarahkan
 * domain sembarang ke server ini dan otomatis dapat SSL cert atas nama
 * aplikasi ini. Sengaja tanpa auth (Caddy tidak bisa kirim kredensial), tapi
 * cuma jawab ya/tidak, tidak bocorkan data apapun.
 */
class DomainCheckController extends Controller
{
    public function ask(Request $request): Response
    {
        $domain = strtolower((string) $request->query('domain'));
        $centralDomain = strtolower(config('tenancy.central_domain'));

        if ($domain === $centralDomain) {
            return response('ok', 200);
        }

        $suffix = ".{$centralDomain}";
        if (str_ends_with($domain, $suffix)) {
            $slug = substr($domain, 0, -strlen($suffix));
            $exists = Masjid::where('slug', $slug)->where('is_active', true)->exists();

            return response($exists ? 'ok' : 'not found', $exists ? 200 : 404);
        }

        $verified = Masjid::where('custom_domain', $domain)->whereNotNull('custom_domain_verified_at')->exists();

        return response($verified ? 'ok' : 'not found', $verified ? 200 : 404);
    }
}
