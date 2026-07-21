<?php

namespace App\Events;

use App\Models\Donation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DonationReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Donation $donation) {}

    public function broadcastOn(): array
    {
        return [new Channel("masjid.{$this->donation->masjid_id}.donations")];
    }

    public function broadcastAs(): string
    {
        return 'donation.received';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->donation->id,
            'donor_name' => $this->donation->donor_name ?? 'Hamba Allah',
            'amount' => (float) $this->donation->amount,
            'paid_at' => $this->donation->paid_at?->toIso8601String(),
        ];
    }
}
