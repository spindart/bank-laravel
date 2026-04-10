<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DepositRequest;
use App\Http\Requests\TransferRequest;
use App\Services\WalletService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService
    ) {
    }

    public function show(Request $request): JsonResponse
    {
        $wallet = $this->walletService->getUserWallet($request->user());

        return ApiResponse::success($wallet, trans('messages.wallet.show.success'));
    }

    public function deposit(DepositRequest $request): JsonResponse
    {
        $transaction = $this->walletService->deposit(
            $request->user(),
            (string) $request->validated('amount')
        );

        return ApiResponse::success($transaction, trans('messages.wallet.deposit.success'));
    }

    public function transfer(TransferRequest $request): JsonResponse
    {
        $transaction = $this->walletService->transfer(
            $request->user(),
            (int) $request->validated('receiver_user_id'),
            (string) $request->validated('amount'),
            $request->validated('idempotency_key')
        );

        return ApiResponse::success($transaction, trans('messages.wallet.transfer.success'));
    }
}
