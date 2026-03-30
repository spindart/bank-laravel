<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionService $transactionService
    ) {
    }

    public function indexByWallet(Request $request, Wallet $wallet): JsonResponse
    {
        $transactions = $this->transactionService->paginateByWallet(
            $wallet,
            (int) $request->integer('per_page', 15)
        );

        return response()->json($transactions);
    }

    public function store(StoreTransactionRequest $request, Wallet $wallet): JsonResponse
    {
        $transaction = $this->transactionService->create($wallet, $request->validated());

        return response()->json($transaction, 201);
    }

    public function show(Transaction $transaction): JsonResponse
    {
        return response()->json($transaction);
    }

    public function update(UpdateTransactionRequest $request, Transaction $transaction): JsonResponse
    {
        $updatedTransaction = $this->transactionService->update($transaction, $request->validated());

        return response()->json($updatedTransaction);
    }

    public function destroy(Transaction $transaction): JsonResponse
    {
        $this->transactionService->delete($transaction);

        return response()->json(status: 204);
    }
}

