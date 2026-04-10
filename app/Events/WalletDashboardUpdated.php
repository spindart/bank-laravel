<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WalletDashboardUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        private readonly int $userId,
        private readonly array $payload
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("private-user.{$this->userId}");
    }

    public function broadcastAs(): string
    {
        return 'wallet.dashboard.updated';
    }

    public function userId(): int
    {
        return $this->userId;
    }

    public function payload(): array
    {
        return $this->payload;
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
