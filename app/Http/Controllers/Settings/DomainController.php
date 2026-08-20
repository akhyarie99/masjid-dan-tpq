<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DomainController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $masjid = $request->user()->masjid;

        $data = $request->validate([
            'custom_domain' => [
                'required', 'string', 'max:255',
                'regex:/^(?!https?:\/\/)([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i',
                Rule::unique('masjids', 'custom_domain')->ignore($masjid->id),
            ],
        ]);

        // Ganti domain = wajib verifikasi ulang, jangan warisi status verified lama.
        $masjid->update([
            'custom_domain' => strtolower($data['custom_domain']),
            'custom_domain_verification_token' => Str::random(32),
            'custom_domain_verified_at' => null,
        ]);

        return back()->with('success', 'Domain disimpan. Pasang DNS TXT record di bawah, lalu klik "Verifikasi".');
    }

    public function verify(Request $request): RedirectResponse
    {
        $masjid = $request->user()->masjid;

        if (! $masjid->custom_domain || ! $masjid->custom_domain_verification_token) {
            return back()->with('error', 'Belum ada domain yang diatur.');
        }

        $expected = "tpq-verify={$masjid->custom_domain_verification_token}";
        $host = "_tpq-verify.{$masjid->custom_domain}";

        $verified = $this->txtRecordContains($host, $expected);

        if (! $verified) {
            return back()->with('error', "Belum terverifikasi. Pastikan DNS TXT record _tpq-verify.{$masjid->custom_domain} berisi \"{$expected}\" sudah aktif (propagasi DNS bisa sampai 24 jam).");
        }

        $masjid->update(['custom_domain_verified_at' => now()]);

        // Verifikasi kepemilikan domain (TXT record) sudah cukup untuk menandai
        // status "terverifikasi", tapi domainnya belum benar-benar melayani
        // trafik sampai admin platform menjalankan deploy/add-custom-domain.sh
        // di server (bikin vhost Nginx + terbitkan sertifikat SSL) — lihat
        // docs/multi-tenancy-limitations.md. Bukan proses instan/self-service.
        return back()->with('success', 'Domain terverifikasi. Tim kami akan mengaktifkan domain Anda dalam 1x24 jam.');
    }

    /**
     * Query DNS-over-HTTPS (Google) dulu, bukan resolver lokal server —
     * resolver lokal (systemd-resolved dsb) pernah terbukti lambat sinkron ke
     * nameserver registrar tertentu meski record-nya sudah aktif secara
     * publik, bikin verifikasi gagal terus padahal DNS-nya sudah benar.
     * Fallback ke dns_get_record() bawaan PHP kalau permintaan HTTP gagal
     * (mis. Google DoH down) supaya verifikasi tidak macet total.
     */
    private function txtRecordContains(string $host, string $expected): bool
    {
        try {
            $response = Http::timeout(5)->get('https://dns.google/resolve', [
                'name' => $host,
                'type' => 'TXT',
            ]);

            if ($response->ok()) {
                $answers = collect($response->json('Answer', []))
                    ->pluck('data')
                    ->map(fn ($txt) => trim($txt, '"'));

                return $answers->contains($expected);
            }
        } catch (\Throwable $e) {
            Log::warning('Domain verify: DNS-over-HTTPS gagal, fallback ke resolver lokal.', ['error' => $e->getMessage()]);
        }

        $records = dns_get_record($host, DNS_TXT) ?: [];

        return collect($records)->contains(fn ($record) => ($record['txt'] ?? null) === $expected);
    }
}
