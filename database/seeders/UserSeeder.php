<?php

namespace Database\Seeders;

use App\Models\Masjid;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $masjid = Masjid::firstOrFail();

        $admin = User::firstOrCreate(
            ['email' => 'admin@simasjid.test'],
            [
                'masjid_id' => $masjid->id,
                'name' => 'Super Admin',
                'phone' => '081200000000',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        if (! $admin->hasRole('super_admin')) {
            $admin->assignRole('super_admin');
        }
    }
}
