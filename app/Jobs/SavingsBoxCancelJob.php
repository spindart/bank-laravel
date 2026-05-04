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

class SavingsBoxCancelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transactionId;

    protected $userId;

    protected $savingsBoxId;

    public function __construct($transactionId, $userId, $savingsBoxId)
    {
        $this->transactionId = $transactionId;
        $this->userId = $userId;
        $this->savingsBoxId = $savingsBoxId;
    }

    public function handle(DashboardRealtimePayloadBuilder $payloadBuilder): void
    {
        $shouldBroadcast = false;

        DB::transaction(function () use (&$shouldBroadcast): void {
            $transaction = $this->transactionId
                ? Transaction::query()->whereKey($this->transactionId)->lockForUpdate()->first()
                : null;
            $wallet = Wallet::query()->where('user_id', $this->userId)->lockForUpdate()->first();
            $savingsBox = SavingsBox::query()
                ->where('user_id', $this->userId)
                ->whereKey($this->savingsBoxId)
                ->lockForUpdate()
                ->first();

            if (! $wallet || ! $savingsBox) {
                Log::error('savings_box.cancel_failed', [
                    'transaction_id' => $this->transactionId,
                    'user_id' => $this->userId,
                    'savings_box_id' => $this->savingsBoxId,
                    'reason' => 'Wallet or savings box not found',
                ]);

                return;
            }

            if (in_array($savingsBox->status, ['cancelled', 'archived'], true)) {
                return;
            }

            $beforeCents = (int) $savingsBox->current_amount_cents;

            if ($beforeCents > 0) {
                if (! $transaction) {
                    Log::error('savings_box.cancel_failed', [
                        'user_id' => $this->userId,
                        'savings_box_id' => $this->savingsBoxId,
                        'reason' => 'Refund transaction not found',
                    ]);

                    return;
                }

                if ($transaction->status !== 'completed') {
                    $newWalletBalanceCents = $this->walletBalanceCents($wallet) + $beforeCents;

                    $wallet->update([
                        'balance_cents' => $newWalletBalanceCents,
                        'balance' => Money::fromCents($newWalletBalanceCents)->toDecimal(),
                    ]);

                    $transaction->update([
                        'status' => 'completed',
                        'amount_cents' => $beforeCents,
                        'amount' => Money::fromCents($beforeCents)->toDecimal(),
                    ]);

                    SavingsBoxMovement::query()->create([
                        'savings_box_id' => $savingsBox->id,
                        'user_id' => $this->userId,
                        'transaction_id' => $transaction->id,
                        'type' => 'cancel_refund',
                        'amount' => Money::fromCents($beforeCents)->toDecimal(),
                        'amount_cents' => $beforeCents,
                        'balance_before' => Money::fromCents($beforeCents)->toDecimal(),
                        'balance_before_cents' => $beforeCents,
                        'balance_after' => '0.00',
                        'balance_after_cents' => 0,
                    ]);
                }
            }

            $savingsBox->update([
                'current_amount_cents' => 0,
                'current_amount' => '0.00',
                'status' => 'cancelled',
            ]);

            $shouldBroadcast = true;

            Log::info('savings_box.cancelled', [
                'user_id' => $this->userId,
                'savings_box_id' => $savingsBox->id,
                'transaction_id' => $transaction?->id,
                'refunded_cents' => $beforeCents,
            ]);
        });

        if ($shouldBroadcast) {
            $payload = $payloadBuilder->buildForUser(
                $this->userId,
                'savings_box_cancelled',
                array_filter([$this->transactionId])
            );

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
