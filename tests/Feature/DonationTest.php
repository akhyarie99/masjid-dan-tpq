<?php

namespace Tests\Feature;

use App\Jobs\SendDonationReceipt;
use App\Models\Donation;
use App\Models\KasAccount;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\InteractsWithMasjid;
use Tests\TestCase;

class DonationTest extends TestCase
{
    use InteractsWithMasjid, RefreshDatabase;

    public function test_public_can_create_donation_charge(): void
    {
        $masjid = $this->createMasjid();

        $this->mock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('createDonationCharge')
                ->once()
                ->andReturn(['token' => 'snap-token-123', 'redirect_url' => 'https://example.test/pay']);
        });

        $response = $this->postJson(route('public.donation.store'), [
            'donor_name' => 'Hamba Allah',
            'donor_phone' => '081234567890',
            'purpose' => 'Umum',
            'amount' => 50000,
            'payment_method' => 'qris',
        ]);

        $response->assertOk();
        $response->assertJson(['snap_token' => 'snap-token-123']);
        $this->assertDatabaseHas('donations', [
            'masjid_id' => $masjid->id,
            'donor_phone' => '081234567890',
            'amount' => 50000,
            'status' => 'pending',
        ]);
    }

    public function test_webhook_marks_donation_paid_and_creates_transaction(): void
    {
        Queue::fake();

        $masjid = $this->createMasjid();
        KasAccount::create([
            'masjid_id' => $masjid->id,
            'name' => 'Kas Tunai',
            'type' => 'cash',
            'initial_balance' => 0,
            'is_active' => true,
        ]);

        $donation = Donation::create([
            'masjid_id' => $masjid->id,
            'donor_name' => 'Hamba Allah',
            'donor_phone' => '081234567890',
            'purpose' => 'Umum',
            'amount' => 50000,
            'payment_method' => 'qris',
            'status' => 'pending',
        ]);

        $this->mock(PaymentService::class, function ($mock) use ($donation) {
            $mock->shouldReceive('parseWebhookNotification')
                ->once()
                ->andReturn(['order_id' => $donation->id, 'transaction_status' => 'settlement']);
        });

        $response = $this->postJson(route('webhook.midtrans'), []);

        $response->assertOk();
        $this->assertDatabaseHas('donations', ['id' => $donation->id, 'status' => 'paid']);
        $this->assertDatabaseHas('transactions', ['masjid_id' => $masjid->id, 'amount' => 50000, 'status' => 'approved']);
        Queue::assertPushed(SendDonationReceipt::class);
    }

    public function test_webhook_marks_donation_expired(): void
    {
        $masjid = $this->createMasjid();

        $donation = Donation::create([
            'masjid_id' => $masjid->id,
            'donor_name' => 'Hamba Allah',
            'donor_phone' => '081234567890',
            'purpose' => 'Umum',
            'amount' => 25000,
            'payment_method' => 'qris',
            'status' => 'pending',
        ]);

        $this->mock(PaymentService::class, function ($mock) use ($donation) {
            $mock->shouldReceive('parseWebhookNotification')
                ->once()
                ->andReturn(['order_id' => $donation->id, 'transaction_status' => 'expire']);
        });

        $response = $this->postJson(route('webhook.midtrans'), []);

        $response->assertOk();
        $this->assertDatabaseHas('donations', ['id' => $donation->id, 'status' => 'expired']);
    }

    public function test_webhook_returns_not_found_for_unknown_donation(): void
    {
        $this->mock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('parseWebhookNotification')
                ->once()
                ->andReturn(['order_id' => 'non-existent-id', 'transaction_status' => 'settlement']);
        });

        $response = $this->postJson(route('webhook.midtrans'), []);

        $response->assertNotFound();
    }
}
