<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\Wallet;

class DashboardRealtimePayloadBuilder
{
    /**
     * @param  array<int>  $transactionIds
     * @return array{
     *     event_type: string,
     *     wallet: array{id:int,user_id:int,balance:string},
     *     transactions: array<int, array{
     *         id:int,
     *         type:string,
     *         amount:string,
     *         sender_wallet_id:?int,
     *         receiver_wallet_id:?int,
     *         status:string,
     *         created_at:?string
     *     }>,
     *     occurred_at: string
     * }|null
     */
    public function buildForUser(int $userId, string $eventType, array $transactionIds): ?array
    {
        $wallet = Wallet::query()->where('user_id', $userId)->first();

        if (! $wallet) {
            return null;
        }

        $ids = collect($transactionIds)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        $transactions = Transaction::query()
            ->whereIn('id', $ids)
            ->where(function ($query) use ($wallet): void {
                $query->where('sender_wallet_id', $wallet->id)
                    ->orWhere('receiver_wallet_id', $wallet->id);
            })
            ->orderByDesc('id')
            ->get()
            ->map(function (Transaction $transaction): array {
                return [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'amount' => (string) $transaction->amount,
                    'sender_wallet_id' => $transaction->sender_wallet_id,
                    'receiver_wallet_id' => $transaction->receiver_wallet_id,
                    'status' => $transaction->status,
                    'created_at' => $transaction->created_at?->toAtomString(),
                ];
            })
            ->values()
            ->all();

        return [
            'event_type' => $eventType,
            'wallet' => [
                'id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'balance' => (string) $wallet->balance,
            ],
            'transactions' => $transactions,
            'occurred_at' => now()->toAtomString(),
        ];
    }
}
