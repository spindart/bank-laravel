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

class DepositJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $amount;

    /**
     * Create a new job instance.
     */
    public function __construct($userId, $amount)
    {
        $this->userId = $userId;
        $this->amount = $amount;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function () {
            $wallet = Wallet::where('user_id', $this->userId)->lockForUpdate()->first();

            if (!$wallet) {
                Log::error('wallet.deposit_failed', [
                    'user_id' => $this->userId,
                    'amount' => $this->amount,
                    'reason' => 'Wallet not found'
                ]);
                return;
            }

            $wallet->increment('balance', $this->amount);

            Transaction::create([
                'type' => 'deposit',
                'amount' => $this->amount,
                'receiver_wallet_id' => $wallet->id,
                'status' => 'completed',
            ]);

            Log::info('wallet.deposit_completed', [
                'user_id' => $this->userId,
                'amount' => $this->amount,
                'new_balance' => $wallet->fresh()->balance,
            ]);
        });
    }
}