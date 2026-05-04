<?php

namespace App\Services;

use App\Models\SavingsBox;
use App\Models\Transaction;
use App\Models\Wallet;
use App\ValueObjects\Money;

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
     *     savings_summary: array{total_saved:string,active_count:int,completed_count:int},
     *     savings_boxes: array<int, array<string, mixed>>,
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

        $savingsBoxes = SavingsBox::query()
            ->where('user_id', $userId)
            ->latest()
            ->get();

        return [
            'event_type' => $eventType,
            'wallet' => [
                'id' => $wallet->id,
                'user_id' => $wallet->user_id,
                'balance' => (string) $wallet->balance,
            ],
            'transactions' => $transactions,
            'savings_summary' => [
                'total_saved' => Money::fromCents((int) $savingsBoxes->sum('current_amount_cents'))->toDecimal(),
                'active_count' => $savingsBoxes->where('status', 'active')->count(),
                'completed_count' => $savingsBoxes->where('status', 'completed')->count(),
            ],
            'savings_boxes' => $savingsBoxes
                ->map(fn (SavingsBox $savingsBox): array => $savingsBox->toArray())
                ->values()
                ->all(),
            'occurred_at' => now()->toAtomString(),
        ];
    }
}
