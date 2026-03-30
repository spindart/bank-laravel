<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWalletRequest;
use App\Http\Requests\UpdateWalletRequest;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $wallets = $this->walletService->paginate((int) $request->integer('per_page', 15));

        return response()->json($wallets);
    }

    public function store(StoreWalletRequest $request): JsonResponse
    {
        $wallet = $this->walletService->create($request->validated());

        return response()->json($wallet, 201);
    }

    public function show(Wallet $wallet): JsonResponse
    {
        return response()->json($wallet);
    }

    public function update(UpdateWalletRequest $request, Wallet $wallet): JsonResponse
    {
        $updatedWallet = $this->walletService->update($wallet, $request->validated());

        return response()->json($updatedWallet);
    }

    public function destroy(Wallet $wallet): JsonResponse
    {
        $this->walletService->delete($wallet);

        return response()->json(status: 204);
    }
}

