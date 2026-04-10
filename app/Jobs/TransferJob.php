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

class TransferJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transactionId;
    protected $senderUserId;
    protected $receiverUserId;
    protected $amount;
    protected $idempotencyKey;

    /**
     * Create a new job instance.
     */
    public function __construct($transactionId, $senderUserId, $receiverUserId, $amount, $idempotencyKey = null)
    {
        $this->transactionId = $transactionId;
        $this->senderUserId = $senderUserId;
        $this->receiverUserId = $receiverUserId;
        $this->amount = $amount;
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
                    'amount' => $this->amount,
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
                    'amount' => $this->amount,
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
                    'amount' => $this->amount,
                    'reason' => 'Wallet not found'
                ]);
                return;
            }

            if ((float) $senderWallet->balance < (float) $this->amount) {
                Log::error('wallet.transfer_failed', [
                    'transaction_id' => $this->transactionId,
                    'sender_user_id' => $this->senderUserId,
                    'receiver_user_id' => $this->receiverUserId,
                    'amount' => $this->amount,
                    'reason' => 'Insufficient balance in job execution',
                ]);
                return;
            }

            $senderWallet->decrement('balance', $this->amount);
            $receiverWallet->increment('balance', $this->amount);

            $transaction->update(['status' => 'completed']);
            $shouldBroadcast = true;

            Log::info('wallet.transfer_completed', [
                'transaction_id' => $transaction->id,
                'sender_user_id' => $this->senderUserId,
                'receiver_user_id' => $this->receiverUserId,
                'amount' => $this->amount,
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
}
