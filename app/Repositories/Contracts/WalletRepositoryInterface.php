<?php

namespace App\Repositories\Contracts;

use App\Models\Wallet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface WalletRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): Wallet;

    public function update(Wallet $wallet, array $data): Wallet;

    public function delete(Wallet $wallet): void;

    public function lockById(int $id): Wallet;

    public function save(Wallet $wallet): Wallet;
}

