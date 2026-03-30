<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('logout', [AuthController::class, 'logout']);

        Route::apiResource('wallets', WalletController::class);

        Route::get('wallets/{wallet}/transactions', [TransactionController::class, 'indexByWallet']);
        Route::post('wallets/{wallet}/transactions', [TransactionController::class, 'store']);

        Route::apiResource('transactions', TransactionController::class)->except(['index', 'store']);
    });
});
