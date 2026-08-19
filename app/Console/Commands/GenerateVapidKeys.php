<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateVapidKeys extends Command
{
    protected $signature = 'tpq:generate-vapid-keys';

    protected $description = 'Generate sepasang VAPID key untuk web push notifikasi wali murid';

    public function handle(): int
    {
        $keys = VAPID::createVapidKeys();

        $this->info('Tambahkan baris berikut ke .env:');
        $this->newLine();
        $this->line("VAPID_PUBLIC_KEY={$keys['publicKey']}");
        $this->line("VAPID_PRIVATE_KEY={$keys['privateKey']}");
        $this->newLine();
        $this->warn('Jangan bagikan VAPID_PRIVATE_KEY. Setelah diisi, jalankan php artisan config:clear.');

        return self::SUCCESS;
    }
}
