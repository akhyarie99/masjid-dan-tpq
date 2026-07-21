<?php

namespace App\Services;

use App\Jobs\SendWhatsAppNotification;
use Illuminate\Support\Facades\Http;

class WhatsAppService
{
    private string $token;

    private string $baseUrl = 'https://api.fonnte.com';

    public function __construct()
    {
        $this->token = (string) config('services.fonnte.token');
    }

    public function send(string $phone, string $message): bool
    {
        if (empty($this->token)) {
            return false;
        }

        $response = Http::withHeaders(['Authorization' => $this->token])
            ->post("{$this->baseUrl}/send", [
                'target' => $phone,
                'message' => $message,
            ]);

        return $response->successful();
    }

    public function sendDocument(string $phone, string $message, string $filePath): bool
    {
        if (empty($this->token)) {
            return false;
        }

        $response = Http::withHeaders(['Authorization' => $this->token])
            ->attach('file', file_get_contents(storage_path("app/{$filePath}")), basename($filePath))
            ->post("{$this->baseUrl}/send", [
                'target' => $phone,
                'message' => $message,
            ]);

        return $response->successful();
    }

    public function sendBulk(array $phones, string $message): void
    {
        foreach ($phones as $index => $phone) {
            SendWhatsAppNotification::dispatch($phone, $message)->delay(now()->addSeconds($index * 2));
        }
    }
}
