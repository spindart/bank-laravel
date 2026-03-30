<?php

namespace App\Repositories\Eloquent;

use App\Models\Wallet;
use App\Repositories\Contracts\WalletRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WalletRepository implements WalletRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Wallet::query()->latest()->paginate($perPage);
    }

    public function create(array $data): Wallet
    {
        return Wallet::query()->create($data);
    }

    public function update(Wallet $wallet, array $data): Wallet
    {
        $wallet->fill($data);
        $wallet->save();

        return $wallet->refresh();
    }

    public function delete(Wallet $wallet): void
    {
        $wallet->delete();
    }

    public function lockById(int $id): Wallet
    {
        return Wallet::query()->whereKey($id)->lockForUpdate()->firstOrFail();
    }

    public function save(Wallet $wallet): Wallet
    {
        $wallet->save();

        return $wallet->refresh();
    }
}

