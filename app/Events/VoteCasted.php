<?php

namespace App\Events;

use App\Models\Election;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VoteCasted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Election $election,
        public int $totalVoted,
        public int $totalEligible,
        public float $participationRate
    ) {}

    /**
     * P2-06: private channel ber-auth (routes/channels.php) — hanya
     * admin/operator. Payload tetap agregat (tanpa kandidat/voter).
     */
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('election.' . $this->election->id . '.monitoring');
    }

    public function broadcastAs(): string
    {
        return 'vote.casted';
    }

    public function broadcastWith(): array
    {
        return [
            'election_id' => $this->election->id,
            'total_voted' => $this->totalVoted,
            'total_eligible' => $this->totalEligible,
            'participation_rate' => $this->participationRate,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
