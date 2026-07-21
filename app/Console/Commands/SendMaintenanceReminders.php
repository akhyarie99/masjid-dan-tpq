<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class SendMaintenanceReminders extends Command
{
    protected $signature = 'assets:maintenance-reminders';

    protected $description = 'Kirim reminder WA ke pengurus aset untuk aset yang jatuh tempo maintenance dalam 7 hari';

    public function handle(WhatsAppService $whatsAppService): int
    {
        $assets = Asset::whereNotNull('next_maintenance_date')
            ->whereBetween('next_maintenance_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
            ->with('masjid')
            ->get();

        if ($assets->isEmpty()) {
            $this->info('Tidak ada aset yang jatuh tempo maintenance.');

            return self::SUCCESS;
        }

        $assets->groupBy('masjid_id')->each(function ($group) use ($whatsAppService) {
            $masjid = $group->first()->masjid;

            $recipients = Role::whereIn('name', ['super_admin', 'admin'])
                ->get()
                ->flatMap(fn ($role) => $role->users)
                ->unique('id')
                ->where('masjid_id', $masjid->id);

            $list = $group->map(fn ($asset) => "• {$asset->name} ({$asset->asset_code}) — jatuh tempo {$asset->next_maintenance_date->translatedFormat('d F Y')}")->implode("\n");

            $message = "🔧 *Reminder Maintenance Aset*\n\nAset berikut memerlukan perawatan dalam 7 hari ke depan:\n\n{$list}\n\n— {$masjid->name}";

            foreach ($recipients as $recipient) {
                if ($recipient->phone) {
                    $whatsAppService->send($recipient->phone, $message);
                }
            }
        });

        $this->info("Reminder terkirim untuk {$assets->count()} aset.");

        return self::SUCCESS;
    }
}
