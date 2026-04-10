<?php

namespace App\Jobs;

use App\Events\WalletDashboardUpdated;
use App\Models\Transaction;
use App\Services\DashboardRealtimePayloadBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReverseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transactionId;
    protected $userId;
    protected $reversalTransactionId;

    /**
     * Create a new job instance.
     */
    public function __construct($transactionId, $userId, $reversalTransactionId)
    {
        $this->transactionId = $transactionId;
        $this->userId = $userId;
        $this->reversalTransactionId = $reversalTransactionId;
    }

    /**
     * Execute the job.
     */
    public function handle(DashboardRealtimePayloadBuilder $payloadBuilder): void
    {
        $affectedUserIds = [];
        $shouldBroadcast = false;

        DB::transaction(function () use (&$affectedUserIds, &$shouldBroadcast) {
            $originalTransaction = Transaction::query()->whereKey($this->transactionId)->lockForUpdate()->first();
            $reversalTransaction = Transaction::query()->whereKey($this->reversalTransactionId)->lockForUpdate()->first();

            if (!$originalTransaction || !$reversalTransaction || $originalTransaction->status !== 'completed') {
                Log::error('wallet.reverse_failed', [
                    'transaction_id' => $this->transactionId,
                    'reversal_transaction_id' => $this->reversalTransactionId,
                    'user_id' => $this->userId,
                    'reason' => 'Transaction not found or not reversible'
                ]);
                return;
            }

            // Check if reversal already exists
            if ($reversalTransaction->status === 'completed') {
                return;
            }

            $senderWallet = $originalTransaction->senderWallet;
            $receiverWallet = $originalTransaction->receiverWallet;

            if ($originalTransaction->type === 'transfer') {
                // Restore balances
                if ($senderWallet) {
                    $senderWallet->increment('balance', $originalTransaction->amount);
                }
                if ($receiverWallet) {
                    $receiverWallet->decrement('balance', $originalTransaction->amount);
                }
            } elseif ($originalTransaction->type === 'deposit') {
                // Reverse deposit: decrement receiver balance
                if ($receiverWallet) {
                    $receiverWallet->decrement('balance', $originalTransaction->amount);
                }
            }

            $reversalTransaction->update(['status' => 'completed']);

            $originalTransaction->update(['status' => 'reversed']);
            $shouldBroadcast = true;

            $affectedUserIds = collect([$senderWallet?->user_id, $receiverWallet?->user_id])
                ->filter(fn (?int $id): bool => !is_null($id))
                ->unique()
                ->values()
                ->all();

            Log::info('wallet.reverse_completed', [
                'original_transaction_id' => $this->transactionId,
                'reversal_transaction_id' => $reversalTransaction->id,
                'user_id' => $this->userId,
            ]);
        });

        if ($shouldBroadcast) {
            foreach ($affectedUserIds as $userId) {
                $payload = $payloadBuilder->buildForUser(
                    $userId,
                    'reversal_completed',
                    [$this->transactionId, $this->reversalTransactionId]
                );

                if ($payload) {
                    event(new WalletDashboardUpdated($userId, $payload));
                }
            }
        }
    }
}
