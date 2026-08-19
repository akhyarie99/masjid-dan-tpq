<?php

namespace App\Jobs;

use App\Services\WebPushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendGuardianWebPush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public string $guardianPhone,
        public string $title,
        public string $body,
        public string $url = '/wali/dashboard',
    ) {}

    public function handle(WebPushService $webPush): void
    {
        $webPush->sendToGuardian($this->guardianPhone, $this->title, $this->body, $this->url);
    }
}
