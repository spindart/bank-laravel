<?php

namespace App\Repositories\Contracts;

use App\Models\Transaction;
interface TransactionRepositoryInterface
{
    public function create(array $data): Transaction;

    public function findById(int $id): ?Transaction;

    public function findByIdempotencyKey(string $idempotencyKey): ?Transaction;

    public function lockById(int $id): Transaction;

    public function update(Transaction $transaction, array $data): Transaction;

    public function findReversalByOriginalId(int $originalTransactionId): ?Transaction;
}
