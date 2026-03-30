<?php

use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::apiResource('wallets', WalletController::class);

    Route::get('wallets/{wallet}/transactions', [TransactionController::class, 'indexByWallet']);
    Route::post('wallets/{wallet}/transactions', [TransactionController::class, 'store']);

    Route::apiResource('transactions', TransactionController::class)->except(['index', 'store']);
});

