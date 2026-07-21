<?php

namespace App\Jobs;

use App\Models\Announcement;
use App\Models\JamaahProfile;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BroadcastAnnouncementWA implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Announcement $announcement) {}

    public function handle(WhatsAppService $whatsAppService): void
    {
        $message = "📢 *{$this->announcement->title}*\n\n{$this->announcement->content}\n\n— {$this->announcement->masjid->name}";

        $phones = JamaahProfile::where('masjid_id', $this->announcement->masjid_id)
            ->where('receive_notification', true)
            ->whereNotNull('phone')
            ->pluck('phone')
            ->all();

        $whatsAppService->sendBulk($phones, $message);
    }
}
