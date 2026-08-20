<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $records = dns_get_record("_tpq-verify.{$masjid->custom_domain}", DNS_TXT) ?: [];
        $expected = "tpq-verify={$masjid->custom_domain_verification_token}";

        $verified = collect($records)->contains(fn ($record) => ($record['txt'] ?? null) === $expected);

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
}
