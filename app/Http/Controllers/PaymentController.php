<?php

namespace App\Http\Controllers;

use App\Events\DonationReceived;
use App\Jobs\SendDonationReceipt;
use App\Models\Donation;
use App\Models\Transaction;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function midtransWebhook(Request $request, PaymentService $paymentService): JsonResponse
    {
        ['order_id' => $orderId, 'transaction_status' => $status] = $paymentService->parseWebhookNotification($request);

        $donation = Donation::find($orderId);

        if (! $donation) {
            return response()->json(['status' => 'not_found'], 404);
        }

        if (in_array($status, ['settlement', 'capture'])) {
            if ($donation->status !== 'paid') {
                $donation->update(['status' => 'paid', 'paid_at' => now()]);
                Transaction::createFromDonation($donation);
                SendDonationReceipt::dispatch($donation);
                broadcast(new DonationReceived($donation));
            }
        } elseif ($status === 'expire') {
            $donation->update(['status' => 'expired']);
        } elseif (in_array($status, ['deny', 'cancel'])) {
            $donation->update(['status' => 'failed']);
        }

        return response()->json(['status' => 'ok']);
    }
}
