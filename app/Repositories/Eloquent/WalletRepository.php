<?php

namespace App\Repositories\Eloquent;

use App\Models\Wallet;
use App\Repositories\Contracts\WalletRepositoryInterface;

class WalletRepository implements WalletRepositoryInterface
{
    public function findByUserId(int $userId): ?Wallet
    {
        return Wallet::query()->where('user_id', $userId)->first();
    }

    public function lockById(int $id): Wallet
    {
        return Wallet::query()->whereKey($id)->lockForUpdate()->firstOrFail();
    }

    public function lockByUserId(int $userId): Wallet
    {
        return Wallet::query()->where('user_id', $userId)->lockForUpdate()->firstOrFail();
    }

    public function save(Wallet $wallet): Wallet
    {
        $wallet->save();

        return $wallet->refresh();
    }
}
