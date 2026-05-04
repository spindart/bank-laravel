<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\SavingsBoxController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\WalletController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('broadcasting/auth', function () {
            return Broadcast::auth(request());
        });
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('wallet', [WalletController::class, 'show']);
        Route::post('deposit', [WalletController::class, 'deposit'])->middleware('throttle:wallet-mutations');
        Route::post('transfer', [WalletController::class, 'transfer'])->middleware('throttle:wallet-mutations');
        Route::post('reverse/{transactionId}', [TransactionController::class, 'reverse'])->middleware('throttle:wallet-mutations');
        Route::get('transactions', [TransactionController::class, 'index']);
        Route::get('savings-boxes', [SavingsBoxController::class, 'index']);
        Route::post('savings-boxes', [SavingsBoxController::class, 'store'])->middleware('throttle:wallet-mutations');
        Route::get('savings-boxes/{savingsBox}', [SavingsBoxController::class, 'show']);
        Route::put('savings-boxes/{savingsBox}', [SavingsBoxController::class, 'update'])->middleware('throttle:wallet-mutations');
        Route::delete('savings-boxes/{savingsBox}', [SavingsBoxController::class, 'destroy'])->middleware('throttle:wallet-mutations');
        Route::post('savings-boxes/{savingsBox}/deposit', [SavingsBoxController::class, 'deposit'])->middleware('throttle:wallet-mutations');
        Route::post('savings-boxes/{savingsBox}/withdraw', [SavingsBoxController::class, 'withdraw'])->middleware('throttle:wallet-mutations');
        Route::get('savings-boxes/{savingsBox}/movements', [SavingsBoxController::class, 'movements']);
    });
});
