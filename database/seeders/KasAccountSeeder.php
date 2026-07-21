<?php

namespace Database\Seeders;

use App\Models\KasAccount;
use App\Models\Masjid;
use Illuminate\Database\Seeder;

class KasAccountSeeder extends Seeder
{
    public function run(): void
    {
        $masjid = Masjid::firstOrFail();

        KasAccount::firstOrCreate([
            'masjid_id' => $masjid->id,
            'name' => 'Kas Tunai',
        ], [
            'type' => 'cash',
            'initial_balance' => 0,
            'is_active' => true,
        ]);
    }
}
