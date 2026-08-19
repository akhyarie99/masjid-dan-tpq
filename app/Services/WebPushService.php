<?php

namespace App\Services;

use App\Models\TpqGuardianPushSubscription;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Throwable;

class WebPushService
{
    private ?WebPush $webPush = null;

    public function __construct()
    {
        $publicKey = (string) config('services.vapid.public_key');
        $privateKey = (string) config('services.vapid.private_key');

        if ($publicKey === '' || $privateKey === '') {
            return;
        }

        $this->webPush = new WebPush([
            'VAPID' => [
                'subject' => (string) config('services.vapid.subject'),
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ]);
    }

    public function isConfigured(): bool
    {
        return $this->webPush !== null;
    }

    public function sendToWaliAccount(string $waliAccountId, string $title, string $body, string $url = '/wali/dashboard'): void
    {
        if (! $this->webPush) {
            return;
        }

        $payload = json_encode(['title' => $title, 'body' => $body, 'url' => $url]);
        $subscriptions = TpqGuardianPushSubscription::where('wali_account_id', $waliAccountId)->get();

        // Kirim satu-satu (bukan queueNotification+flush batch) supaya satu subscription
        // yang datanya rusak/kadaluarsa tidak menggagalkan pengiriman ke device lain.
        foreach ($subscriptions as $record) {
            try {
                $report = $this->webPush->sendOneNotification(
                    Subscription::create([
                        'endpoint' => $record->endpoint,
                        'publicKey' => $record->public_key,
                        'authToken' => $record->auth_token,
                        // Safari HANYA mendukung aes128gcm (standar final RFC8291) — aesgcm
                        // (lama) yang jadi default library ini akan gagal total di iOS/macOS
                        // Safari. Chrome & Firefox modern juga sudah dukung aes128gcm, jadi
                        // aman dipakai seragam tanpa perlu deteksi per-browser.
                        'contentEncoding' => $record->content_encoding ?? 'aes128gcm',
                    ]),
                    $payload,
                );

                if (! $report->isSuccess() && $report->isSubscriptionExpired()) {
                    $record->delete();
                }
            } catch (Throwable $e) {
                Log::warning('Gagal kirim web push ke wali', ['wali_account_id' => $waliAccountId, 'subscription_id' => $record->id, 'error' => $e->getMessage()]);
            }
        }
    }
}
