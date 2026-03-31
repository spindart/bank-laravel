<?php

namespace App\Repositories\Eloquent;

use App\Models\Transaction;
use App\Repositories\Contracts\TransactionRepositoryInterface;

class TransactionRepository implements TransactionRepositoryInterface
{
    public function create(array $data): Transaction
    {
        return Transaction::query()->create($data);
    }

    public function findById(int $id): ?Transaction
    {
        return Transaction::query()->find($id);
    }

    public function findByIdempotencyKey(string $idempotencyKey): ?Transaction
    {
        return Transaction::query()
            ->where('idempotency_key', $idempotencyKey)
            ->first();
    }

    public function lockById(int $id): Transaction
    {
        return Transaction::query()->whereKey($id)->lockForUpdate()->firstOrFail();
    }

    public function update(Transaction $transaction, array $data): Transaction
    {
        $transaction->fill($data);
        $transaction->save();

        return $transaction->refresh();
    }

    public function findReversalByOriginalId(int $originalTransactionId): ?Transaction
    {
        return Transaction::query()
            ->where('type', 'reversal')
            ->where('original_transaction_id', $originalTransactionId)
            ->first();
    }
}
