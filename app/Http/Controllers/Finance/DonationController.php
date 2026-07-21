<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use App\Models\Masjid;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DonationController extends Controller
{
    public function index(Request $request): Response
    {
        $donations = Donation::where('masjid_id', $request->user()->masjid_id)
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Finance/Donation/Index', ['donations' => $donations]);
    }

    public function show(Donation $donasi): Response
    {
        return Inertia::render('Finance/Donation/Show', ['donation' => $donasi]);
    }

    public function publicStore(Request $request, PaymentService $paymentService): JsonResponse
    {
        $data = $request->validate([
            'donor_name' => ['nullable', 'string', 'max:255'],
            'donor_phone' => ['required', 'string', 'max:30'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:10000'],
            'payment_method' => ['required', 'in:qris,va_bri,va_bni,cash'],
        ]);

        $masjid = Masjid::where('is_active', true)->firstOrFail();

        $donation = Donation::create([
            'masjid_id' => $masjid->id,
            'donor_name' => $data['donor_name'] ?: null,
            'donor_phone' => $data['donor_phone'],
            'purpose' => $data['purpose'] ?: 'Umum',
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'status' => 'pending',
        ]);

        try {
            $charge = $paymentService->createDonationCharge($donation);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Gagal membuat transaksi pembayaran. Pastikan konfigurasi Midtrans sudah benar.',
            ], 422);
        }

        $donation->update([
            'payment_gateway_id' => $charge['token'] ?? null,
            'gateway_response' => $charge,
        ]);

        return response()->json([
            'donation_id' => $donation->id,
            'snap_token' => $charge['token'] ?? null,
            'redirect_url' => $charge['redirect_url'] ?? null,
        ]);
    }

    public function publicStatus(Donation $donation): JsonResponse
    {
        return response()->json(['status' => $donation->status]);
    }
}
