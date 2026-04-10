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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class WalletService
{
    public function __construct(
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly TransactionRepositoryInterface $transactionRepository
    ) {
    }

    public function getUserWallet(User $user): Wallet
    {
        $wallet = $this->walletRepository->findByUserId($user->id);

        if (! $wallet) {
            throw new WalletNotFoundException();
        }

        return $wallet;
    }

    public function deposit(User $user, float $amount): Transaction
    {
        try {
            $wallet = $this->walletRepository->findByUserId($user->id);

            if (!$wallet) {
                throw new WalletNotFoundException();
            }

            $transaction = $this->transactionRepository->create([
                'type' => 'deposit',
                'amount' => $amount,
                'sender_wallet_id' => null,
                'receiver_wallet_id' => $wallet->id,
                'status' => 'pending',
                'original_transaction_id' => null,
            ]);

            DepositJob::dispatch($user->id, $amount);

            Log::info('wallet.deposit_queued', [
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'amount' => $amount,
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

    public function transfer(User $senderUser, int $receiverUserId, float $amount, ?string $idempotencyKey = null): Transaction
    {
        try {
            if ($idempotencyKey) {
                $existingTransaction = $this->transactionRepository->findByIdempotencyKey($idempotencyKey);

                if ($existingTransaction) {
                    return $existingTransaction;
                }
            }

            $senderWallet = $this->walletRepository->findByUserId($senderUser->id);
            $receiverWallet = $this->walletRepository->findByUserId($receiverUserId);

            if (!$senderWallet || !$receiverWallet) {
                throw new WalletNotFoundException();
            }

            if ($senderUser->id === $receiverUserId) {
                throw new InsufficientBalanceException('Cannot transfer to self');
            }

            if ((float) $senderWallet->balance < $amount) {
                throw new InsufficientBalanceException();
            }

            $transaction = $this->transactionRepository->create([
                'type' => 'transfer',
                'amount' => $amount,
                'sender_wallet_id' => $senderWallet->id,
                'receiver_wallet_id' => $receiverWallet->id,
                'status' => 'pending',
                'idempotency_key' => $idempotencyKey,
                'original_transaction_id' => null,
            ]);

            TransferJob::dispatch($senderUser->id, $receiverUserId, $amount, $idempotencyKey);

            Log::info('wallet.transfer_queued', [
                'sender_user_id' => $senderUser->id,
                'receiver_user_id' => $receiverUserId,
                'amount' => $amount,
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

            if (!$targetTransaction) {
                throw new TransactionNotReversibleException();
            }

            if ($targetTransaction->status === 'reversed') {
                $existingReversal = $this->transactionRepository->findReversalByOriginalId($targetTransaction->id);

                if ($existingReversal) {
                    return $existingReversal;
                }
            }

            if ($targetTransaction->status !== 'completed') {
                throw new TransactionNotReversibleException();
            }

            $existingReversal = $this->transactionRepository->findReversalByOriginalId($targetTransaction->id);

            if ($existingReversal) {
                return $existingReversal;
            }

            // Check ownership
            $senderWallet = $targetTransaction->senderWallet;
            $receiverWallet = $targetTransaction->receiverWallet;

            if ($targetTransaction->type === 'deposit' && (!$receiverWallet || $receiverWallet->user_id !== $user->id)) {
                throw new TransactionNotReversibleException();
            }

            if ($targetTransaction->type === 'transfer' && (!$senderWallet || $senderWallet->user_id !== $user->id)) {
                throw new TransactionNotReversibleException();
            }

            $reversal = $this->transactionRepository->create([
                'type' => 'reversal',
                'amount' => (float) $targetTransaction->amount,
                'sender_wallet_id' => $targetTransaction->receiver_wallet_id,
                'receiver_wallet_id' => $targetTransaction->sender_wallet_id,
                'status' => 'pending',
                'original_transaction_id' => $targetTransaction->id,
            ]);

            ReverseJob::dispatch($transactionId, $user->id);

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
}
