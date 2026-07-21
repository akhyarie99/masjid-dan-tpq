<?php

namespace App\Services;

use App\Models\Donation;
use Illuminate\Http\Request;
use Midtrans\Config;
use Midtrans\Notification;
use Midtrans\Snap;

class PaymentService
{
    public function __construct()
    {
        Config::$serverKey = (string) config('services.midtrans.server_key');
        Config::$isProduction = (bool) config('services.midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    public function createDonationCharge(Donation $donation): array
    {
        $params = [
            'transaction_details' => [
                'order_id' => $donation->id,
                'gross_amount' => (int) $donation->amount,
            ],
            'customer_details' => [
                'first_name' => $donation->donor_name ?? 'Donatur',
                'phone' => $donation->donor_phone ?? '',
            ],
            'item_details' => [[
                'id' => 'DONASI',
                'price' => (int) $donation->amount,
                'quantity' => 1,
                'name' => 'Donasi Masjid - '.($donation->purpose ?? 'Umum'),
            ]],
            'enabled_payments' => ['qris', 'bca_va', 'bni_va', 'bri_va'],
        ];

        return (array) Snap::createTransaction($params);
    }

    /**
     * Bungkus Midtrans\Notification, yang memverifikasi status transaksi
     * langsung ke server Midtrans (bukan mempercayai payload webhook
     * mentah). Dipisah sebagai method agar dapat di-mock saat testing.
     *
     * @return array{order_id: string, transaction_status: string}
     */
    public function parseWebhookNotification(Request $request): array
    {
        $inputSource = 'data://text/plain,'.rawurlencode($request->getContent());
        $notification = new Notification($inputSource);

        return [
            'order_id' => (string) $notification->order_id,
            'transaction_status' => (string) $notification->transaction_status,
        ];
    }
}
