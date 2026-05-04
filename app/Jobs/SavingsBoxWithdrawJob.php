<?php

namespace App\Jobs;

use App\Models\SavingsBox;
use App\Models\SavingsBoxMovement;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\DashboardRealtimePayloadBuilder;
use App\Support\SafeBroadcast;
use App\ValueObjects\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SavingsBoxWithdrawJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transactionId;

    protected $userId;

    protected $savingsBoxId;

    protected $amountCents;

    public function __construct($transactionId, $userId, $savingsBoxId, $amountCents)
    {
        $this->transactionId = $transactionId;
        $this->userId = $userId;
        $this->savingsBoxId = $savingsBoxId;
        $this->amountCents = $amountCents;
    }

    public function handle(DashboardRealtimePayloadBuilder $payloadBuilder): void
    {
        $shouldBroadcast = false;

        DB::transaction(function () use (&$shouldBroadcast): void {
            $transaction = Transaction::query()->whereKey($this->transactionId)->lockForUpdate()->first();
            $wallet = Wallet::query()->where('user_id', $this->userId)->lockForUpdate()->first();
            $savingsBox = SavingsBox::query()
                ->where('user_id', $this->userId)
                ->whereKey($this->savingsBoxId)
                ->lockForUpdate()
                ->first();

            if (! $transaction || ! $wallet || ! $savingsBox) {
                Log::error('savings_box.withdraw_failed', [
                    'transaction_id' => $this->transactionId,
                    'user_id' => $this->userId,
                    'savings_box_id' => $this->savingsBoxId,
                    'reason' => 'Transaction, wallet or savings box not found',
                ]);

                return;
            }

            if ($transaction->status === 'completed') {
                return;
            }

            if ($savingsBox->status !== 'active' && $savingsBox->status !== 'completed') {
                Log::error('savings_box.withdraw_failed', [
                    'transaction_id' => $transaction->id,
                    'savings_box_id' => $savingsBox->id,
                    'reason' => 'Savings box is inactive',
                ]);

                return;
            }

            $beforeCents = (int) $savingsBox->current_amount_cents;

            if ($beforeCents < (int) $this->amountCents) {
                Log::error('savings_box.withdraw_failed', [
                    'transaction_id' => $transaction->id,
                    'savings_box_id' => $savingsBox->id,
                    'amount_cents' => $this->amountCents,
                    'reason' => 'Insufficient savings box balance in job execution',
                ]);

                return;
            }

            $afterCents = $beforeCents - (int) $this->amountCents;
            $newWalletBalanceCents = $this->walletBalanceCents($wallet) + (int) $this->amountCents;

            $wallet->update([
                'balance_cents' => $newWalletBalanceCents,
                'balance' => Money::fromCents($newWalletBalanceCents)->toDecimal(),
            ]);

            $savingsBox->update([
                'current_amount_cents' => $afterCents,
                'current_amount' => Money::fromCents($afterCents)->toDecimal(),
                'status' => $afterCents >= (int) $savingsBox->target_amount_cents ? 'completed' : 'active',
            ]);

            $transaction->update([
                'status' => 'completed',
                'amount_cents' => (int) $this->amountCents,
                'amount' => Money::fromCents((int) $this->amountCents)->toDecimal(),
            ]);

            SavingsBoxMovement::query()->create([
                'savings_box_id' => $savingsBox->id,
                'user_id' => $this->userId,
                'transaction_id' => $transaction->id,
                'type' => 'withdraw',
                'amount' => Money::fromCents((int) $this->amountCents)->toDecimal(),
                'amount_cents' => (int) $this->amountCents,
                'balance_before' => Money::fromCents($beforeCents)->toDecimal(),
                'balance_before_cents' => $beforeCents,
                'balance_after' => Money::fromCents($afterCents)->toDecimal(),
                'balance_after_cents' => $afterCents,
            ]);

            $shouldBroadcast = true;

            Log::info('savings_box.withdraw_completed', [
                'user_id' => $this->userId,
                'savings_box_id' => $savingsBox->id,
                'transaction_id' => $transaction->id,
                'amount_cents' => $this->amountCents,
            ]);
        });

        if ($shouldBroadcast) {
            $payload = $payloadBuilder->buildForUser($this->userId, 'savings_box_withdraw_completed', [$this->transactionId]);

            if ($payload) {
                SafeBroadcast::walletDashboardUpdated($this->userId, $payload);
            }
        }
    }

    private function walletBalanceCents(Wallet $wallet): int
    {
        if (! is_null($wallet->balance_cents)) {
            return (int) $wallet->balance_cents;
        }

        return Money::fromDecimal((string) $wallet->balance)->cents();
    }
}
