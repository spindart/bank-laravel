<?php

namespace App\Services;

use App\Exceptions\Finance\FinanceException;
use App\Exceptions\Finance\InsufficientBalanceException;
use App\Exceptions\Finance\SavingsBoxInactiveException;
use App\Exceptions\Finance\SavingsBoxNotFoundException;
use App\Exceptions\Finance\WalletNotFoundException;
use App\Jobs\SavingsBoxCancelJob;
use App\Jobs\SavingsBoxDepositJob;
use App\Jobs\SavingsBoxWithdrawJob;
use App\Models\SavingsBox;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Support\SafeBroadcast;
use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SavingsBoxService
{
    public function __construct(
        private readonly DashboardRealtimePayloadBuilder $payloadBuilder
    ) {}

    public function listForUser(User $user): Collection
    {
        return SavingsBox::query()
            ->where('user_id', $user->id)
            ->latest()
            ->get();
    }

    public function summaryForUser(User $user): array
    {
        $boxes = $this->listForUser($user);

        return [
            'total_saved' => Money::fromCents((int) $boxes->sum('current_amount_cents'))->toDecimal(),
            'active_count' => $boxes->where('status', 'active')->count(),
            'completed_count' => $boxes->where('status', 'completed')->count(),
        ];
    }

    public function findForUser(User $user, int $savingsBoxId): SavingsBox
    {
        $savingsBox = SavingsBox::query()
            ->where('user_id', $user->id)
            ->whereKey($savingsBoxId)
            ->first();

        if (! $savingsBox) {
            throw new SavingsBoxNotFoundException;
        }

        return $savingsBox;
    }

    public function create(User $user, array $data): SavingsBox
    {
        $target = Money::fromDecimal((string) $data['target_amount']);

        $savingsBox = SavingsBox::query()->create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'target_amount' => $target->toDecimal(),
            'target_amount_cents' => $target->cents(),
            'current_amount' => '0.00',
            'current_amount_cents' => 0,
            'target_date' => $data['target_date'] ?? null,
            'status' => 'active',
            'icon' => $data['icon'] ?? null,
        ]);

        $this->broadcastDashboardUpdate($user->id, 'savings_box_created');

        return $savingsBox;
    }

    public function update(User $user, int $savingsBoxId, array $data): SavingsBox
    {
        $savingsBox = $this->findForUser($user, $savingsBoxId);

        if (! in_array($savingsBox->status, ['active', 'completed'], true)) {
            throw new SavingsBoxInactiveException;
        }

        $target = Money::fromDecimal((string) $data['target_amount']);
        $status = $this->resolveStatus((int) $savingsBox->current_amount_cents, $target->cents(), $savingsBox->status);

        $savingsBox->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'target_amount' => $target->toDecimal(),
            'target_amount_cents' => $target->cents(),
            'target_date' => $data['target_date'] ?? null,
            'status' => $status,
            'icon' => $data['icon'] ?? null,
        ]);

        $this->broadcastDashboardUpdate($user->id, 'savings_box_updated');

        return $savingsBox->refresh();
    }

    public function deposit(User $user, int $savingsBoxId, string $amount): Transaction
    {
        return DB::transaction(function () use ($user, $savingsBoxId, $amount): Transaction {
            $money = Money::fromDecimal($amount);
            $wallet = $this->lockWalletForUser($user->id);
            $savingsBox = $this->lockSavingsBoxForUser($user->id, $savingsBoxId);

            $this->assertActive($savingsBox);

            $walletBalanceCents = $this->walletBalanceCents($wallet);

            if ($walletBalanceCents < $money->cents()) {
                throw new InsufficientBalanceException;
            }

            $transaction = $this->createPendingTransaction('savings_deposit', $money, $wallet->id);

            SavingsBoxDepositJob::dispatch($transaction->id, $user->id, $savingsBox->id, $money->cents());

            return $transaction;
        });
    }

    public function withdraw(User $user, int $savingsBoxId, string $amount): Transaction
    {
        return DB::transaction(function () use ($user, $savingsBoxId, $amount): Transaction {
            $money = Money::fromDecimal($amount);
            $wallet = $this->lockWalletForUser($user->id);
            $savingsBox = $this->lockSavingsBoxForUser($user->id, $savingsBoxId);

            $this->assertActive($savingsBox);

            $beforeCents = (int) $savingsBox->current_amount_cents;

            if ($beforeCents < $money->cents()) {
                throw new FinanceException(trans('messages.error.savings_box_insufficient_balance'), 422);
            }

            $transaction = $this->createPendingTransaction('savings_withdraw', $money, $wallet->id);

            SavingsBoxWithdrawJob::dispatch($transaction->id, $user->id, $savingsBox->id, $money->cents());

            return $transaction;
        });
    }

    public function cancel(User $user, int $savingsBoxId): SavingsBox
    {
        return DB::transaction(function () use ($user, $savingsBoxId): SavingsBox {
            $wallet = $this->lockWalletForUser($user->id);
            $savingsBox = $this->lockSavingsBoxForUser($user->id, $savingsBoxId);

            if (in_array($savingsBox->status, ['cancelled', 'archived'], true)) {
                return $savingsBox;
            }

            $beforeCents = (int) $savingsBox->current_amount_cents;
            $transaction = null;

            if ($beforeCents > 0) {
                $money = Money::fromCents($beforeCents);
                $transaction = $this->createPendingTransaction('savings_cancel_refund', $money, $wallet->id);
            }

            SavingsBoxCancelJob::dispatch($transaction?->id, $user->id, $savingsBox->id);

            return $savingsBox;
        });
    }

    private function lockWalletForUser(int $userId): Wallet
    {
        $wallet = Wallet::query()->where('user_id', $userId)->lockForUpdate()->first();

        if (! $wallet) {
            throw new WalletNotFoundException;
        }

        return $wallet;
    }

    private function lockSavingsBoxForUser(int $userId, int $savingsBoxId): SavingsBox
    {
        $savingsBox = SavingsBox::query()
            ->where('user_id', $userId)
            ->whereKey($savingsBoxId)
            ->lockForUpdate()
            ->first();

        if (! $savingsBox) {
            throw new SavingsBoxNotFoundException;
        }

        return $savingsBox;
    }

    private function assertActive(SavingsBox $savingsBox): void
    {
        if (! in_array($savingsBox->status, ['active', 'completed'], true)) {
            throw new SavingsBoxInactiveException;
        }
    }

    private function createPendingTransaction(string $type, Money $money, int $walletId): Transaction
    {
        return Transaction::query()->create([
            'type' => $type,
            'amount' => $money->toDecimal(),
            'amount_cents' => $money->cents(),
            'sender_wallet_id' => $walletId,
            'receiver_wallet_id' => $walletId,
            'status' => 'pending',
            'original_transaction_id' => null,
        ]);
    }

    private function resolveStatus(int $currentAmountCents, int $targetAmountCents, string $currentStatus): string
    {
        if (in_array($currentStatus, ['cancelled', 'archived'], true)) {
            return $currentStatus;
        }

        return $currentAmountCents >= $targetAmountCents ? 'completed' : 'active';
    }

    private function walletBalanceCents(Wallet $wallet): int
    {
        if (! is_null($wallet->balance_cents)) {
            return (int) $wallet->balance_cents;
        }

        return Money::fromDecimal((string) $wallet->balance)->cents();
    }

    /**
     * @param  array<int>  $transactionIds
     */
    private function broadcastDashboardUpdate(int $userId, string $eventType, array $transactionIds = []): void
    {
        $payload = $this->payloadBuilder->buildForUser($userId, $eventType, $transactionIds);

        if ($payload) {
            SafeBroadcast::walletDashboardUpdated($userId, $payload);
        }
    }
}
