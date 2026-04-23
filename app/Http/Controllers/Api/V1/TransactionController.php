<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\WalletService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(
        private readonly WalletService $walletService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'offset' => ['sometimes', 'integer', 'min:0'],
        ]);

        $limit = (int) ($validated['limit'] ?? 20);
        $offset = (int) ($validated['offset'] ?? 0);

        $wallet = $this->walletService->getUserWallet($request->user());

        $transactions = Transaction::query()
            ->where('sender_wallet_id', $wallet->id)
            ->orWhere('receiver_wallet_id', $wallet->id)
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return ApiResponse::success($transactions, trans('messages.transaction.history.success'));
    }

    public function reverse(Request $request, int $transactionId): JsonResponse
    {
        $transaction = $this->walletService->reverse($request->user(), $transactionId);

        return ApiResponse::success($transaction, trans('messages.wallet.reverse.success'));
    }
}
