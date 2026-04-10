<?php

namespace App\Jobs;

use App\Events\WalletDashboardUpdated;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\DashboardRealtimePayloadBuilder;
use App\ValueObjects\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransferJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transactionId;
    protected $senderUserId;
    protected $receiverUserId;
    protected $amountCents;
    protected $idempotencyKey;

    /**
     * Create a new job instance.
     */
    public function __construct($transactionId, $senderUserId, $receiverUserId, $amountCents, $idempotencyKey = null)
    {
        $this->transactionId = $transactionId;
        $this->senderUserId = $senderUserId;
        $this->receiverUserId = $receiverUserId;
        $this->amountCents = $amountCents;
        $this->idempotencyKey = $idempotencyKey;
    }

    /**
     * Execute the job.
     */
    public function handle(DashboardRealtimePayloadBuilder $payloadBuilder): void
    {
        $shouldBroadcast = false;

        DB::transaction(function () use (&$shouldBroadcast) {
            $transaction = Transaction::query()->whereKey($this->transactionId)->lockForUpdate()->first();

            if (!$transaction) {
                Log::error('wallet.transfer_failed', [
                    'transaction_id' => $this->transactionId,
                    'sender_user_id' => $this->senderUserId,
                    'receiver_user_id' => $this->receiverUserId,
                    'amount_cents' => $this->amountCents,
                    'reason' => 'Transaction not found',
                ]);
                return;
            }

            if ($transaction->status === 'completed') {
                return;
            }

            if ($this->idempotencyKey && $transaction->idempotency_key !== $this->idempotencyKey) {
                Log::error('wallet.transfer_failed', [
                    'transaction_id' => $this->transactionId,
                    'sender_user_id' => $this->senderUserId,
                    'receiver_user_id' => $this->receiverUserId,
                    'amount_cents' => $this->amountCents,
                    'reason' => 'Idempotency key mismatch',
                ]);
                return;
            }

            $senderWallet = Wallet::where('user_id', $this->senderUserId)->lockForUpdate()->first();
            $receiverWallet = Wallet::where('user_id', $this->receiverUserId)->lockForUpdate()->first();

            if (!$senderWallet || !$receiverWallet) {
                Log::error('wallet.transfer_failed', [
                    'sender_user_id' => $this->senderUserId,
                    'receiver_user_id' => $this->receiverUserId,
                    'amount_cents' => $this->amountCents,
                    'reason' => 'Wallet not found'
                ]);
                return;
            }

            $senderBalanceCents = $this->walletBalanceCents($senderWallet);
            $receiverBalanceCents = $this->walletBalanceCents($receiverWallet);

            if ($senderBalanceCents < (int) $this->amountCents) {
                Log::error('wallet.transfer_failed', [
                    'transaction_id' => $this->transactionId,
                    'sender_user_id' => $this->senderUserId,
                    'receiver_user_id' => $this->receiverUserId,
                    'amount_cents' => $this->amountCents,
                    'reason' => 'Insufficient balance in job execution',
                ]);
                return;
            }

            $newSenderBalanceCents = $senderBalanceCents - (int) $this->amountCents;
            $newReceiverBalanceCents = $receiverBalanceCents + (int) $this->amountCents;

            $senderWallet->update([
                'balance_cents' => $newSenderBalanceCents,
                'balance' => Money::fromCents($newSenderBalanceCents)->toDecimal(),
            ]);
            $receiverWallet->update([
                'balance_cents' => $newReceiverBalanceCents,
                'balance' => Money::fromCents($newReceiverBalanceCents)->toDecimal(),
            ]);

            $transaction->update([
                'status' => 'completed',
                'amount_cents' => (int) $this->amountCents,
                'amount' => Money::fromCents((int) $this->amountCents)->toDecimal(),
            ]);
            $shouldBroadcast = true;

            Log::info('wallet.transfer_completed', [
                'transaction_id' => $transaction->id,
                'sender_user_id' => $this->senderUserId,
                'receiver_user_id' => $this->receiverUserId,
                'amount_cents' => $this->amountCents,
                'idempotency_key' => $this->idempotencyKey,
            ]);
        });

        if ($shouldBroadcast) {
            foreach ([$this->senderUserId, $this->receiverUserId] as $userId) {
                $payload = $payloadBuilder->buildForUser($userId, 'transfer_completed', [$this->transactionId]);

                if ($payload) {
                    event(new WalletDashboardUpdated($userId, $payload));
                }
            }
        }
    }

    private function walletBalanceCents(Wallet $wallet): int
    {
        if (!is_null($wallet->balance_cents)) {
            return (int) $wallet->balance_cents;
        }

        return Money::fromDecimal((string) $wallet->balance)->cents();
    }
}
