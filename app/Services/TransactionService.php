<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Wallet;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use App\Repositories\Contracts\WalletRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    public function __construct(
        private readonly TransactionRepositoryInterface $transactionRepository,
        private readonly WalletRepositoryInterface $walletRepository
    ) {
    }

    public function paginateByWallet(Wallet $wallet, int $perPage = 15): LengthAwarePaginator
    {
        return $this->transactionRepository->paginateByWallet($wallet->id, $perPage);
    }

    public function create(Wallet $wallet, array $data): Transaction
    {
        return DB::transaction(function () use ($wallet, $data): Transaction {
            $lockedWallet = $this->walletRepository->lockById($wallet->id);
            $signedAmount = $this->signedAmount($data['type'], (float) $data['amount']);

            $this->assertBalance($lockedWallet, $signedAmount);

            $transaction = $this->transactionRepository->create([
                'wallet_id' => $lockedWallet->id,
                'type' => $data['type'],
                'amount' => $data['amount'],
                'description' => $data['description'] ?? null,
                'transaction_date' => $data['transaction_date'] ?? Carbon::now(),
            ]);

            $lockedWallet->balance = (float) $lockedWallet->balance + $signedAmount;
            $this->walletRepository->save($lockedWallet);

            return $transaction->refresh();
        });
    }

    public function update(Transaction $transaction, array $data): Transaction
    {
        return DB::transaction(function () use ($transaction, $data): Transaction {
            $lockedWallet = $this->walletRepository->lockById($transaction->wallet_id);

            $oldSignedAmount = $this->signedAmount($transaction->type, (float) $transaction->amount);
            $newType = $data['type'] ?? $transaction->type;
            $newAmount = (float) ($data['amount'] ?? $transaction->amount);
            $newSignedAmount = $this->signedAmount($newType, $newAmount);

            $futureBalance = (float) $lockedWallet->balance - $oldSignedAmount + $newSignedAmount;
            $this->assertFutureBalance($futureBalance);

            $updatedTransaction = $this->transactionRepository->update($transaction, [
                'type' => $newType,
                'amount' => $newAmount,
                'description' => $data['description'] ?? $transaction->description,
                'transaction_date' => $data['transaction_date'] ?? $transaction->transaction_date,
            ]);

            $lockedWallet->balance = $futureBalance;
            $this->walletRepository->save($lockedWallet);

            return $updatedTransaction->refresh();
        });
    }

    public function delete(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction): void {
            $lockedWallet = $this->walletRepository->lockById($transaction->wallet_id);

            $signedAmount = $this->signedAmount($transaction->type, (float) $transaction->amount);
            $futureBalance = (float) $lockedWallet->balance - $signedAmount;
            $this->assertFutureBalance($futureBalance);

            $this->transactionRepository->delete($transaction);

            $lockedWallet->balance = $futureBalance;
            $this->walletRepository->save($lockedWallet);
        });
    }

    private function signedAmount(string $type, float $amount): float
    {
        return $type === 'debit' ? -$amount : $amount;
    }

    private function assertBalance(Wallet $wallet, float $signedAmount): void
    {
        $futureBalance = (float) $wallet->balance + $signedAmount;
        $this->assertFutureBalance($futureBalance);
    }

    private function assertFutureBalance(float $futureBalance): void
    {
        if ($futureBalance < 0) {
            throw ValidationException::withMessages([
                'amount' => ['Saldo insuficiente para concluir a operação.'],
            ]);
        }
    }
}

