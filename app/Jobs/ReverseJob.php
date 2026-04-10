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

class ReverseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transactionId;
    protected $userId;

    /**
     * Create a new job instance.
     */
    public function __construct($transactionId, $userId)
    {
        $this->transactionId = $transactionId;
        $this->userId = $userId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function () {
            $originalTransaction = Transaction::find($this->transactionId);

            if (!$originalTransaction || $originalTransaction->status !== 'completed') {
                Log::error('wallet.reverse_failed', [
                    'transaction_id' => $this->transactionId,
                    'user_id' => $this->userId,
                    'reason' => 'Transaction not found or not reversible'
                ]);
                return;
            }

            // Check if reversal already exists
            $existingReversal = Transaction::where('original_transaction_id', $this->transactionId)->first();
            if ($existingReversal && $existingReversal->status === 'completed') {
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

            $reversalTransaction = Transaction::where('original_transaction_id', $this->transactionId)->first();
            if ($reversalTransaction) {
                $reversalTransaction->update(['status' => 'completed']);
            }

            $originalTransaction->update(['status' => 'reversed']);

            Log::info('wallet.reverse_completed', [
                'original_transaction_id' => $this->transactionId,
                'reversal_transaction_id' => $reversalTransaction ? $reversalTransaction->id : null,
                'user_id' => $this->userId,
            ]);
        });
    }
}