<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\WalletService;
use App\Support\ApiResponse;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            'type' => ['sometimes', 'string', Rule::in(['deposit', 'transfer', 'reversal'])],
            'status' => ['sometimes', 'string', Rule::in(['pending', 'completed', 'reversed'])],
            'date' => ['sometimes', 'date_format:Y-m-d'],
            'timezone' => ['sometimes', 'timezone'],
        ]);

        $limit = (int) ($validated['limit'] ?? 20);
        $offset = (int) ($validated['offset'] ?? 0);

        $wallet = $this->walletService->getUserWallet($request->user());

        $transactionsQuery = Transaction::query()
            ->where(function ($query) use ($wallet): void {
                $query
                    ->where('sender_wallet_id', $wallet->id)
                    ->orWhere('receiver_wallet_id', $wallet->id);
            });

        if (isset($validated['type'])) {
            $transactionsQuery->where('type', $validated['type']);
        }

        if (isset($validated['status'])) {
            $transactionsQuery->where('status', $validated['status']);
        }

        if (isset($validated['date'])) {
            $timezone = $validated['timezone'] ?? config('app.timezone');
            $date = CarbonImmutable::createFromFormat('Y-m-d', $validated['date'], $timezone);
            $rangeStart = $date->startOfDay()->setTimezone(config('app.timezone'));
            $rangeEnd = $date->endOfDay()->setTimezone(config('app.timezone'));

            $transactionsQuery->where(function ($query) use ($validated, $rangeStart, $rangeEnd): void {
                $query->whereDate('created_at', $validated['date'])
                    ->orWhereBetween('created_at', [$rangeStart, $rangeEnd]);
            });
        }

        $transactions = $transactionsQuery
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
