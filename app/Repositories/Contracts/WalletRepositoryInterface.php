<?php

namespace App\Repositories\Contracts;

use App\Models\Wallet;
interface WalletRepositoryInterface
{
    public function findByUserId(int $userId): ?Wallet;

    public function lockById(int $id): Wallet;

    public function lockByUserId(int $userId): Wallet;

    public function save(Wallet $wallet): Wallet;
}
