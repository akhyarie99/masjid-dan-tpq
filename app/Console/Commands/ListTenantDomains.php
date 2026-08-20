<?php

namespace App\Console\Commands;

use App\Models\Masjid;
use Illuminate\Console\Command;

/**
 * Output satu domain per baris: domain pusat + subdomain tiap tenant aktif.
 * Dipakai deploy/sync-subdomain-certs.sh untuk membangun daftar -d untuk
 * certbot (SAN cert diperluas otomatis tiap ada tenant baru) — bukan
 * wildcard cert, supaya tidak perlu akses API DNS provider apa pun.
 */
class ListTenantDomains extends Command
{
    protected $signature = 'tenants:domains';

    protected $description = 'List domain pusat + subdomain semua tenant aktif, satu per baris';

    public function handle(): int
    {
        $central = config('tenancy.central_domain');

        $this->line($central);

        Masjid::where('is_active', true)
            ->orderBy('slug')
            ->pluck('slug')
            ->each(fn (string $slug) => $this->line("{$slug}.{$central}"));

        return self::SUCCESS;
    }
}
