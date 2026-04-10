<?php

namespace App\Jobs;

use App\Events\WalletDashboardUpdated;
use App\Models\Transaction;
use App\Services\DashboardRealtimePayloadBuilder;
use App\ValueObjects\Money;
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
            $amountCents = $this->transactionAmountCents($originalTransaction);

            if ($originalTransaction->type === 'transfer') {
                // Restore balances
                if ($senderWallet) {
                    $newSenderBalanceCents = $this->walletBalanceCents($senderWallet) + $amountCents;
                    $senderWallet->update([
                        'balance_cents' => $newSenderBalanceCents,
                        'balance' => Money::fromCents($newSenderBalanceCents)->toDecimal(),
                    ]);
                }
                if ($receiverWallet) {
                    $newReceiverBalanceCents = $this->walletBalanceCents($receiverWallet) - $amountCents;
                    $receiverWallet->update([
                        'balance_cents' => $newReceiverBalanceCents,
                        'balance' => Money::fromCents($newReceiverBalanceCents)->toDecimal(),
                    ]);
                }
            } elseif ($originalTransaction->type === 'deposit') {
                // Reverse deposit: decrement receiver balance
                if ($receiverWallet) {
                    $newReceiverBalanceCents = $this->walletBalanceCents($receiverWallet) - $amountCents;
                    $receiverWallet->update([
                        'balance_cents' => $newReceiverBalanceCents,
                        'balance' => Money::fromCents($newReceiverBalanceCents)->toDecimal(),
                    ]);
                }
            }

            $reversalTransaction->update([
                'status' => 'completed',
                'amount_cents' => $this->transactionAmountCents($reversalTransaction),
            ]);

            $originalTransaction->update([
                'status' => 'reversed',
                'amount_cents' => $amountCents,
            ]);
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

    private function walletBalanceCents($wallet): int
    {
        if (!is_null($wallet->balance_cents)) {
            return (int) $wallet->balance_cents;
        }

        return Money::fromDecimal((string) $wallet->balance)->cents();
    }

    private function transactionAmountCents(Transaction $transaction): int
    {
        if (!is_null($transaction->amount_cents)) {
            return (int) $transaction->amount_cents;
        }

        return Money::fromDecimal((string) $transaction->amount)->cents();
    }
}
