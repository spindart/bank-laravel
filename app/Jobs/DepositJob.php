<?php

namespace App\Jobs;

use App\Events\WalletDashboardUpdated;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\DashboardRealtimePayloadBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DepositJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transactionId;
    protected $userId;
    protected $amount;

    /**
     * Create a new job instance.
     */
    public function __construct($transactionId, $userId, $amount)
    {
        $this->transactionId = $transactionId;
        $this->userId = $userId;
        $this->amount = $amount;
    }

    /**
     * Execute the job.
     */
    public function handle(DashboardRealtimePayloadBuilder $payloadBuilder): void
    {
        $shouldBroadcast = false;

        DB::transaction(function () use (&$shouldBroadcast) {
            $wallet = Wallet::where('user_id', $this->userId)->lockForUpdate()->first();
            $transaction = Transaction::query()->whereKey($this->transactionId)->lockForUpdate()->first();

            if (!$wallet || !$transaction) {
                Log::error('wallet.deposit_failed', [
                    'transaction_id' => $this->transactionId,
                    'user_id' => $this->userId,
                    'amount' => $this->amount,
                    'reason' => 'Wallet or transaction not found',
                ]);
                return;
            }

            if ($transaction->status === 'completed') {
                return;
            }

            $wallet->increment('balance', $this->amount);

            $transaction->update(['status' => 'completed']);
            $shouldBroadcast = true;

            Log::info('wallet.deposit_completed', [
                'transaction_id' => $transaction->id,
                'user_id' => $this->userId,
                'amount' => $this->amount,
                'new_balance' => $wallet->fresh()->balance,
            ]);
        });

        if ($shouldBroadcast) {
            $payload = $payloadBuilder->buildForUser($this->userId, 'deposit_completed', [$this->transactionId]);

            if ($payload) {
                event(new WalletDashboardUpdated($this->userId, $payload));
            }
        }
    }
}
