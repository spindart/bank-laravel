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

class DepositJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transactionId;
    protected $userId;
    protected $amountCents;

    /**
     * Create a new job instance.
     */
    public function __construct($transactionId, $userId, $amountCents)
    {
        $this->transactionId = $transactionId;
        $this->userId = $userId;
        $this->amountCents = $amountCents;
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
                    'amount_cents' => $this->amountCents,
                    'reason' => 'Wallet or transaction not found',
                ]);
                return;
            }

            if ($transaction->status === 'completed') {
                return;
            }

            $newBalanceCents = $this->walletBalanceCents($wallet) + (int) $this->amountCents;
            $wallet->update([
                'balance_cents' => $newBalanceCents,
                'balance' => Money::fromCents($newBalanceCents)->toDecimal(),
            ]);

            $transaction->update([
                'status' => 'completed',
                'amount_cents' => (int) $this->amountCents,
                'amount' => Money::fromCents((int) $this->amountCents)->toDecimal(),
            ]);
            $shouldBroadcast = true;

            Log::info('wallet.deposit_completed', [
                'transaction_id' => $transaction->id,
                'user_id' => $this->userId,
                'amount_cents' => $this->amountCents,
                'new_balance_cents' => $newBalanceCents,
            ]);
        });

        if ($shouldBroadcast) {
            $payload = $payloadBuilder->buildForUser($this->userId, 'deposit_completed', [$this->transactionId]);

            if ($payload) {
                event(new WalletDashboardUpdated($this->userId, $payload));
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
