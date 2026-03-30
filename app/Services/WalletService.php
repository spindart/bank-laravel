<?php

namespace App\Services;

use App\Models\Wallet;
use App\Repositories\Contracts\WalletRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WalletService
{
    public function __construct(
        private readonly WalletRepositoryInterface $walletRepository
    ) {
    }

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->walletRepository->paginate($perPage);
    }

    public function create(array $data): Wallet
    {
        return $this->walletRepository->create($data);
    }

    public function update(Wallet $wallet, array $data): Wallet
    {
        return $this->walletRepository->update($wallet, $data);
    }

    public function delete(Wallet $wallet): void
    {
        $this->walletRepository->delete($wallet);
    }
}

