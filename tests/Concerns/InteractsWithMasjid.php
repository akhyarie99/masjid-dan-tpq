<?php

namespace Tests\Concerns;

use App\Models\Masjid;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Str;

trait InteractsWithMasjid
{
    protected function createMasjid(array $attributes = []): Masjid
    {
        return Masjid::create([
            'name' => 'Masjid Test',
            'slug' => 'masjid-test-'.Str::random(6),
            'address' => 'Jl. Test No. 1',
            'latitude' => -7.4894,
            'longitude' => 109.0044,
            'prayer_method' => 'kemenag',
            'is_active' => true,
            ...$attributes,
        ]);
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
