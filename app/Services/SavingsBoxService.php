<?php

namespace App\Services;

use App\Exceptions\Finance\FinanceException;
use App\Exceptions\Finance\InsufficientBalanceException;
use App\Exceptions\Finance\SavingsBoxInactiveException;
use App\Exceptions\Finance\SavingsBoxNotFoundException;
use App\Exceptions\Finance\WalletNotFoundException;
use App\Models\SavingsBox;
use App\Models\SavingsBoxMovement;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SavingsBoxService
{
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

        return SavingsBox::query()->create([
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

        return $savingsBox->refresh();
    }

    public function deposit(User $user, int $savingsBoxId, string $amount): SavingsBoxMovement
    {
        return DB::transaction(function () use ($user, $savingsBoxId, $amount): SavingsBoxMovement {
            $money = Money::fromDecimal($amount);
            $wallet = $this->lockWalletForUser($user->id);
            $savingsBox = $this->lockSavingsBoxForUser($user->id, $savingsBoxId);

            $this->assertActive($savingsBox);

            $walletBalanceCents = $this->walletBalanceCents($wallet);

            if ($walletBalanceCents < $money->cents()) {
                throw new InsufficientBalanceException;
            }

            $beforeCents = (int) $savingsBox->current_amount_cents;
            $afterCents = $beforeCents + $money->cents();
            $newWalletBalanceCents = $walletBalanceCents - $money->cents();
            $transaction = $this->createCompletedTransaction('savings_deposit', $money, $wallet->id);

            $wallet->update([
                'balance_cents' => $newWalletBalanceCents,
                'balance' => Money::fromCents($newWalletBalanceCents)->toDecimal(),
            ]);

            $savingsBox->update([
                'current_amount_cents' => $afterCents,
                'current_amount' => Money::fromCents($afterCents)->toDecimal(),
                'status' => $this->resolveStatus($afterCents, (int) $savingsBox->target_amount_cents, $savingsBox->status),
            ]);

            $movement = $this->createMovement($savingsBox, $user, $transaction, 'deposit', $money, $beforeCents, $afterCents);

            Log::info('savings_box.deposit_completed', [
                'user_id' => $user->id,
                'savings_box_id' => $savingsBox->id,
                'transaction_id' => $transaction->id,
                'amount_cents' => $money->cents(),
            ]);

            return $movement->load('savingsBox', 'transaction');
        });
    }

    public function withdraw(User $user, int $savingsBoxId, string $amount): SavingsBoxMovement
    {
        return DB::transaction(function () use ($user, $savingsBoxId, $amount): SavingsBoxMovement {
            $money = Money::fromDecimal($amount);
            $wallet = $this->lockWalletForUser($user->id);
            $savingsBox = $this->lockSavingsBoxForUser($user->id, $savingsBoxId);

            $this->assertActive($savingsBox);

            $beforeCents = (int) $savingsBox->current_amount_cents;

            if ($beforeCents < $money->cents()) {
                throw new FinanceException(trans('messages.error.savings_box_insufficient_balance'), 422);
            }

            $afterCents = $beforeCents - $money->cents();
            $newWalletBalanceCents = $this->walletBalanceCents($wallet) + $money->cents();
            $transaction = $this->createCompletedTransaction('savings_withdraw', $money, $wallet->id);

            $wallet->update([
                'balance_cents' => $newWalletBalanceCents,
                'balance' => Money::fromCents($newWalletBalanceCents)->toDecimal(),
            ]);

            $savingsBox->update([
                'current_amount_cents' => $afterCents,
                'current_amount' => Money::fromCents($afterCents)->toDecimal(),
                'status' => $afterCents >= (int) $savingsBox->target_amount_cents ? 'completed' : 'active',
            ]);

            $movement = $this->createMovement($savingsBox, $user, $transaction, 'withdraw', $money, $beforeCents, $afterCents);

            Log::info('savings_box.withdraw_completed', [
                'user_id' => $user->id,
                'savings_box_id' => $savingsBox->id,
                'transaction_id' => $transaction->id,
                'amount_cents' => $money->cents(),
            ]);

            return $movement->load('savingsBox', 'transaction');
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

            if ($beforeCents > 0) {
                $money = Money::fromCents($beforeCents);
                $newWalletBalanceCents = $this->walletBalanceCents($wallet) + $beforeCents;
                $transaction = $this->createCompletedTransaction('savings_cancel_refund', $money, $wallet->id);

                $wallet->update([
                    'balance_cents' => $newWalletBalanceCents,
                    'balance' => Money::fromCents($newWalletBalanceCents)->toDecimal(),
                ]);

                $this->createMovement($savingsBox, $user, $transaction, 'cancel_refund', $money, $beforeCents, 0);
            }

            $savingsBox->update([
                'current_amount_cents' => 0,
                'current_amount' => '0.00',
                'status' => 'cancelled',
            ]);

            Log::info('savings_box.cancelled', [
                'user_id' => $user->id,
                'savings_box_id' => $savingsBox->id,
                'refunded_cents' => $beforeCents,
            ]);

            return $savingsBox->refresh();
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

    private function createCompletedTransaction(string $type, Money $money, int $walletId): Transaction
    {
        return Transaction::query()->create([
            'type' => $type,
            'amount' => $money->toDecimal(),
            'amount_cents' => $money->cents(),
            'sender_wallet_id' => $walletId,
            'receiver_wallet_id' => $walletId,
            'status' => 'completed',
            'original_transaction_id' => null,
        ]);
    }

    private function createMovement(
        SavingsBox $savingsBox,
        User $user,
        Transaction $transaction,
        string $type,
        Money $money,
        int $beforeCents,
        int $afterCents
    ): SavingsBoxMovement {
        return SavingsBoxMovement::query()->create([
            'savings_box_id' => $savingsBox->id,
            'user_id' => $user->id,
            'transaction_id' => $transaction->id,
            'type' => $type,
            'amount' => $money->toDecimal(),
            'amount_cents' => $money->cents(),
            'balance_before' => Money::fromCents($beforeCents)->toDecimal(),
            'balance_before_cents' => $beforeCents,
            'balance_after' => Money::fromCents($afterCents)->toDecimal(),
            'balance_after_cents' => $afterCents,
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
}
