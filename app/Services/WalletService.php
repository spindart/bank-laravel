<?php

namespace App\Services;

use App\Exceptions\Finance\InsufficientBalanceException;
use App\Exceptions\Finance\TransactionNotReversibleException;
use App\Exceptions\Finance\WalletNotFoundException;
use App\Jobs\DepositJob;
use App\Jobs\ReverseJob;
use App\Jobs\TransferJob;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Repositories\Contracts\WalletRepositoryInterface;
use App\ValueObjects\Money;
use Illuminate\Support\Facades\Log;
use Throwable;

class WalletService
{
    public function __construct(
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly TransactionRepositoryInterface $transactionRepository
    ) {}

    public function getUserWallet(User $user): Wallet
    {
        $wallet = $this->walletRepository->findByUserId($user->id);

        if (! $wallet) {
            throw new WalletNotFoundException;
        }

        return $wallet;
    }

    public function deposit(User $user, string $amount): Transaction
    {
        try {
            $wallet = $this->walletRepository->findByUserId($user->id);

            if (! $wallet) {
                throw new WalletNotFoundException;
            }

            $money = Money::fromDecimal($amount);

            $transaction = $this->transactionRepository->create([
                'type' => 'deposit',
                'amount' => $money->toDecimal(),
                'amount_cents' => $money->cents(),
                'sender_wallet_id' => null,
                'receiver_wallet_id' => $wallet->id,
                'status' => 'pending',
                'original_transaction_id' => null,
            ]);

            DepositJob::dispatch($transaction->id, $user->id, $money->cents());

            Log::info('wallet.deposit_queued', [
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'amount' => $money->toDecimal(),
                'transaction_id' => $transaction->id,
            ]);

            return $transaction;
        } catch (Throwable $exception) {
            Log::error('wallet.deposit_failed', [
                'user_id' => $user->id,
                'amount' => $amount,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function transfer(User $senderUser, int $receiverUserId, string $amount, ?string $idempotencyKey = null): Transaction
    {
        try {
            if ($idempotencyKey) {
                $existingTransaction = $this->transactionRepository->findByIdempotencyKey($idempotencyKey);

                if ($existingTransaction) {
                    return $existingTransaction;
                }
            }

            $money = Money::fromDecimal($amount);

            $senderWallet = $this->walletRepository->findByUserId($senderUser->id);
            $receiverWallet = $this->walletRepository->findByUserId($receiverUserId);

            if (! $senderWallet || ! $receiverWallet) {
                throw new WalletNotFoundException;
            }

            if ($senderUser->id === $receiverUserId) {
                throw new InsufficientBalanceException('Cannot transfer to self');
            }

            if ($this->walletBalanceCents($senderWallet) < $money->cents()) {
                throw new InsufficientBalanceException;
            }

            $transaction = $this->transactionRepository->create([
                'type' => 'transfer',
                'amount' => $money->toDecimal(),
                'amount_cents' => $money->cents(),
                'sender_wallet_id' => $senderWallet->id,
                'receiver_wallet_id' => $receiverWallet->id,
                'status' => 'pending',
                'idempotency_key' => $idempotencyKey,
                'original_transaction_id' => null,
            ]);

            TransferJob::dispatch($transaction->id, $senderUser->id, $receiverUserId, $money->cents(), $idempotencyKey);

            Log::info('wallet.transfer_queued', [
                'sender_user_id' => $senderUser->id,
                'receiver_user_id' => $receiverUserId,
                'amount' => $money->toDecimal(),
                'transaction_id' => $transaction->id,
                'idempotency_key' => $idempotencyKey,
            ]);

            return $transaction;
        } catch (Throwable $exception) {
            Log::error('wallet.transfer_failed', [
                'sender_user_id' => $senderUser->id,
                'receiver_user_id' => $receiverUserId,
                'amount' => $amount,
                'idempotency_key' => $idempotencyKey,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function reverse(User $user, int $transactionId): Transaction
    {
        try {
            $targetTransaction = $this->transactionRepository->findById($transactionId);

            if (! $targetTransaction) {
                throw new TransactionNotReversibleException;
            }

            if ($targetTransaction->status === 'reversed') {
                $existingReversal = $this->transactionRepository->findReversalByOriginalId($targetTransaction->id);

                if ($existingReversal) {
                    return $existingReversal;
                }
            }

            if ($targetTransaction->status !== 'completed') {
                throw new TransactionNotReversibleException;
            }

            if (! in_array($targetTransaction->type, ['deposit', 'transfer'], true)) {
                throw new TransactionNotReversibleException;
            }

            $existingReversal = $this->transactionRepository->findReversalByOriginalId($targetTransaction->id);

            if ($existingReversal) {
                return $existingReversal;
            }

            // Check ownership
            $senderWallet = $targetTransaction->senderWallet;
            $receiverWallet = $targetTransaction->receiverWallet;

            if ($targetTransaction->type === 'deposit' && (! $receiverWallet || $receiverWallet->user_id !== $user->id)) {
                throw new TransactionNotReversibleException;
            }

            if ($targetTransaction->type === 'transfer' && (! $senderWallet || $senderWallet->user_id !== $user->id)) {
                throw new TransactionNotReversibleException;
            }

            $targetAmount = Money::fromCents($this->transactionAmountCents($targetTransaction));

            $reversal = $this->transactionRepository->create([
                'type' => 'reversal',
                'amount' => $targetAmount->toDecimal(),
                'amount_cents' => $targetAmount->cents(),
                'sender_wallet_id' => $targetTransaction->receiver_wallet_id,
                'receiver_wallet_id' => $targetTransaction->sender_wallet_id,
                'status' => 'pending',
                'original_transaction_id' => $targetTransaction->id,
            ]);

            ReverseJob::dispatch($transactionId, $user->id, $reversal->id);

            Log::info('wallet.reverse_queued', [
                'user_id' => $user->id,
                'transaction_id' => $transactionId,
                'reversal_transaction_id' => $reversal->id,
            ]);

            return $reversal;
        } catch (Throwable $exception) {
            Log::error('wallet.reverse_failed', [
                'user_id' => $user->id,
                'transaction_id' => $transactionId,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function walletBalanceCents(Wallet $wallet): int
    {
        if (! is_null($wallet->balance_cents)) {
            return (int) $wallet->balance_cents;
        }

        return Money::fromDecimal((string) $wallet->balance)->cents();
    }

    private function transactionAmountCents(Transaction $transaction): int
    {
        if (! is_null($transaction->amount_cents)) {
            return (int) $transaction->amount_cents;
        }

        return Money::fromDecimal((string) $transaction->amount)->cents();
    }
}
