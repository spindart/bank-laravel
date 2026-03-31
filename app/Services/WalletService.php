<?php

namespace App\Services;

use App\Exceptions\Finance\InsufficientBalanceException;
use App\Exceptions\Finance\TransactionNotReversibleException;
use App\Exceptions\Finance\WalletNotFoundException;
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
            return DB::transaction(function () use ($user, $amount): Transaction {
                $wallet = $this->walletRepository->lockByUserId($user->id);
                $wallet->balance = (float) $wallet->balance + $amount;
                $this->walletRepository->save($wallet);

                $transaction = $this->transactionRepository->create([
                    'type' => 'deposit',
                    'amount' => $amount,
                    'sender_wallet_id' => null,
                    'receiver_wallet_id' => $wallet->id,
                    'status' => 'completed',
                    'original_transaction_id' => null,
                ]);

                Log::info('wallet.deposit_completed', [
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'amount' => $amount,
                    'transaction_id' => $transaction->id,
                ]);

                return $transaction;
            });
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
            return DB::transaction(function () use ($senderUser, $receiverUserId, $amount, $idempotencyKey): Transaction {
                if ($idempotencyKey) {
                    $existingTransaction = $this->transactionRepository->findByIdempotencyKey($idempotencyKey);

                    if ($existingTransaction) {
                        return $existingTransaction;
                    }
                }

                $senderWallet = $this->walletRepository->lockByUserId($senderUser->id);
                $receiverWallet = $this->walletRepository->lockByUserId($receiverUserId);

                if ((float) $senderWallet->balance < $amount) {
                    throw new InsufficientBalanceException();
                }

                $senderWallet->balance = (float) $senderWallet->balance - $amount;
                $receiverWallet->balance = (float) $receiverWallet->balance + $amount;

                $this->walletRepository->save($senderWallet);
                $this->walletRepository->save($receiverWallet);

                $transaction = $this->transactionRepository->create([
                    'type' => 'transfer',
                    'amount' => $amount,
                    'sender_wallet_id' => $senderWallet->id,
                    'receiver_wallet_id' => $receiverWallet->id,
                    'status' => 'completed',
                    'idempotency_key' => $idempotencyKey,
                    'original_transaction_id' => null,
                ]);

                Log::info('wallet.transfer_completed', [
                    'sender_user_id' => $senderUser->id,
                    'receiver_user_id' => $receiverUserId,
                    'sender_wallet_id' => $senderWallet->id,
                    'receiver_wallet_id' => $receiverWallet->id,
                    'amount' => $amount,
                    'transaction_id' => $transaction->id,
                    'idempotency_key' => $idempotencyKey,
                ]);

                return $transaction;
            });
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
            return DB::transaction(function () use ($user, $transactionId): Transaction {
                $targetTransaction = $this->transactionRepository->lockById($transactionId);

                if ($targetTransaction->status === 'reversed') {
                    $existingReversal = $this->transactionRepository->findReversalByOriginalId($targetTransaction->id);

                    if ($existingReversal) {
                        Log::info('wallet.reverse_idempotent_reversed_status', [
                            'user_id' => $user->id,
                            'transaction_id' => $transactionId,
                            'reversal_transaction_id' => $existingReversal->id,
                        ]);

                        return $existingReversal;
                    }
                }

                if ($targetTransaction->status !== 'completed') {
                    throw new TransactionNotReversibleException();
                }

                $existingReversal = $this->transactionRepository->findReversalByOriginalId($targetTransaction->id);

                if ($existingReversal) {
                    Log::info('wallet.reverse_idempotent_hit', [
                        'user_id' => $user->id,
                        'transaction_id' => $transactionId,
                        'reversal_transaction_id' => $existingReversal->id,
                    ]);

                    return $existingReversal;
                }

                $senderWallet = $targetTransaction->sender_wallet_id
                    ? $this->walletRepository->lockById($targetTransaction->sender_wallet_id)
                    : null;
                $receiverWallet = $targetTransaction->receiver_wallet_id
                    ? $this->walletRepository->lockById($targetTransaction->receiver_wallet_id)
                    : null;

                if ($targetTransaction->type === 'deposit') {
                    if (! $receiverWallet) {
                        throw new TransactionNotReversibleException();
                    }

                    if ($receiverWallet->user_id !== $user->id) {
                        throw new TransactionNotReversibleException();
                    }

                    if ((float) $receiverWallet->balance < (float) $targetTransaction->amount) {
                        throw new InsufficientBalanceException();
                    }

                    $receiverWallet->balance = (float) $receiverWallet->balance - (float) $targetTransaction->amount;
                    $this->walletRepository->save($receiverWallet);
                }

                if ($targetTransaction->type === 'transfer') {
                    if (! $senderWallet || ! $receiverWallet) {
                        throw new TransactionNotReversibleException();
                    }

                    if ($senderWallet->user_id !== $user->id) {
                        throw new TransactionNotReversibleException();
                    }

                    if ((float) $receiverWallet->balance < (float) $targetTransaction->amount) {
                        throw new InsufficientBalanceException();
                    }

                    $receiverWallet->balance = (float) $receiverWallet->balance - (float) $targetTransaction->amount;
                    $senderWallet->balance = (float) $senderWallet->balance + (float) $targetTransaction->amount;

                    $this->walletRepository->save($receiverWallet);
                    $this->walletRepository->save($senderWallet);
                }

                $reversal = $this->transactionRepository->create([
                    'type' => 'reversal',
                    'amount' => (float) $targetTransaction->amount,
                    'sender_wallet_id' => $targetTransaction->receiver_wallet_id,
                    'receiver_wallet_id' => $targetTransaction->sender_wallet_id,
                    'status' => 'completed',
                    'original_transaction_id' => $targetTransaction->id,
                ]);

                $this->transactionRepository->update($targetTransaction, [
                    'status' => 'reversed',
                ]);

                Log::info('wallet.reverse_completed', [
                    'user_id' => $user->id,
                    'transaction_id' => $transactionId,
                    'reversal_transaction_id' => $reversal->id,
                ]);

                return $reversal;
            });
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
