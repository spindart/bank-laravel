<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Models\Wallet;
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

    protected $senderUserId;
    protected $receiverUserId;
    protected $amount;
    protected $idempotencyKey;

    /**
     * Create a new job instance.
     */
    public function __construct($senderUserId, $receiverUserId, $amount, $idempotencyKey = null)
    {
        $this->senderUserId = $senderUserId;
        $this->receiverUserId = $receiverUserId;
        $this->amount = $amount;
        $this->idempotencyKey = $idempotencyKey;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function () {
            // Check idempotency again in case of race
            if ($this->idempotencyKey) {
                $existingTransaction = Transaction::where('idempotency_key', $this->idempotencyKey)->first();
                if ($existingTransaction && $existingTransaction->status === 'completed') {
                    return;
                }
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

            $senderWallet->decrement('balance', $this->amount);
            $receiverWallet->increment('balance', $this->amount);

            $transaction = Transaction::where('idempotency_key', $this->idempotencyKey)->first();
            if ($transaction) {
                $transaction->update(['status' => 'completed']);
            }

            Log::info('wallet.transfer_completed', [
                'sender_user_id' => $this->senderUserId,
                'receiver_user_id' => $this->receiverUserId,
                'amount' => $this->amount,
                'idempotency_key' => $this->idempotencyKey,
            ]);
        });
    }
}