<?php

namespace App\Repositories\Eloquent;

use App\Models\Transaction;
use App\Repositories\Contracts\TransactionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TransactionRepository implements TransactionRepositoryInterface
{
    public function paginateByWallet(int $walletId, int $perPage = 15): LengthAwarePaginator
    {
        return Transaction::query()
            ->where('wallet_id', $walletId)
            ->latest('transaction_date')
            ->paginate($perPage);
    }

    public function create(array $data): Transaction
    {
        return Transaction::query()->create($data);
    }

    public function update(Transaction $transaction, array $data): Transaction
    {
        $transaction->fill($data);
        $transaction->save();

        return $transaction->refresh();
    }

    public function delete(Transaction $transaction): void
    {
        $transaction->delete();
    }
}

