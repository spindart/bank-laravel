<?php

namespace App\Support;

use App\Events\WalletDashboardUpdated;
use Illuminate\Support\Facades\Log;
use Throwable;

class SafeBroadcast
{
    public static function walletDashboardUpdated(int $userId, array $payload): void
    {
        try {
            event(new WalletDashboardUpdated($userId, $payload));
        } catch (Throwable $exception) {
            Log::warning('wallet.dashboard_broadcast_failed', [
                'user_id' => $userId,
                'event_type' => $payload['event_type'] ?? null,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
