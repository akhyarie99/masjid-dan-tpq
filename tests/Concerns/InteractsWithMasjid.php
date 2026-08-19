<?php

namespace Tests\Concerns;

use App\Models\Masjid;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

trait InteractsWithMasjid
{
    protected function createMasjid(array $attributes = []): Masjid
    {
        $masjid = Masjid::create([
            'name' => 'Masjid Test',
            'slug' => 'masjid-test-'.Str::random(6),
            'address' => 'Jl. Test No. 1',
            'latitude' => -7.4894,
            'longitude' => 109.0044,
            'prayer_method' => 'kemenag',
            'is_active' => true,
            ...$attributes,
        ]);

        // ResolveTenant middleware resolve tenant dari host request — tanpa ini
        // semua request test akan 404 (host default testing tidak cocok subdomain
        // manapun). UrlGenerator meng-cache root URL dari request pertama yang
        // ter-bind, jadi sekadar ganti config('app.url') tidak cukup — harus
        // forceRootUrl() supaya route()/url()/test HTTP client (yang keduanya
        // lewat UrlGenerator) ikut mengarah ke host tenant ini untuk sisa test.
        URL::forceRootUrl('http://'.$masjid->slug.'.'.config('tenancy.central_domain'));

        return $masjid;
    }

    protected function createUser(Masjid $masjid, string $role = 'super_admin', array $attributes = []): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::create([
            'masjid_id' => $masjid->id,
            'name' => 'Test User',
            'email' => Str::random(8).'@example.test',
            'phone' => '08'.random_int(1000000000, 1999999999),
            'password' => bcrypt('password'),
            'is_active' => true,
            ...$attributes,
        ]);

        $user->assignRole($role);

        return $user;
    }
}
