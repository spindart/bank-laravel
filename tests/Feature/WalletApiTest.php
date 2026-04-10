<?php

namespace Tests\Feature;

use App\Events\WalletDashboardUpdated;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WalletApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_updates_balance_and_creates_transaction(): void
    {
        [$user, $wallet] = $this->createUserWithWallet(0);
        Sanctum::actingAs($user);
        Event::fake([WalletDashboardUpdated::class]);

        $response = $this->postJson('/api/v1/deposit', [
            'amount' => 150.50,
        ]);

        $response->assertOk();
        $wallet->refresh();

        $this->assertSame('150.50', $wallet->balance);
        $this->assertDatabaseHas('transactions', [
            'type' => 'deposit',
            'amount' => '150.50',
            'receiver_wallet_id' => $wallet->id,
            'status' => 'completed',
        ]);
        $this->assertDatabaseCount('transactions', 1);

        Event::assertDispatched(WalletDashboardUpdated::class, function (WalletDashboardUpdated $event) use ($user): bool {
            $payload = $event->payload();

            return $event->userId() === $user->id
                && ($payload['event_type'] ?? null) === 'deposit_completed'
                && ($payload['wallet']['user_id'] ?? null) === $user->id
                && isset($payload['occurred_at'])
                && ! empty($payload['transactions']);
        });
    }

    public function test_transfer_blocks_insufficient_balance(): void
    {
        [$sender, $senderWallet] = $this->createUserWithWallet(20);
        [$receiver] = $this->createUserWithWallet(0);
        Sanctum::actingAs($sender);

        $response = $this->postJson('/api/v1/transfer', [
            'receiver_user_id' => $receiver->id,
            'amount' => 100,
        ]);

        $response->assertStatus(422);
        $senderWallet->refresh();

        $this->assertSame('20.00', $senderWallet->balance);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_transfer_is_idempotent_with_same_key(): void
    {
        [$sender, $senderWallet] = $this->createUserWithWallet(120);
        [$receiver, $receiverWallet] = $this->createUserWithWallet(40);
        Sanctum::actingAs($sender);
        Event::fake([WalletDashboardUpdated::class]);

        $idempotencyKey = 'tx-transfer-unique-key';

        $firstResponse = $this->postJson('/api/v1/transfer', [
            'receiver_user_id' => $receiver->id,
            'amount' => 30,
            'idempotency_key' => $idempotencyKey,
        ])->assertOk();

        $secondResponse = $this->postJson('/api/v1/transfer', [
            'receiver_user_id' => $receiver->id,
            'amount' => 30,
            'idempotency_key' => $idempotencyKey,
        ])->assertOk();

        $senderWallet->refresh();
        $receiverWallet->refresh();

        $this->assertSame('90.00', $senderWallet->balance);
        $this->assertSame('70.00', $receiverWallet->balance);
        $this->assertSame(
            $firstResponse->json('data.id'),
            $secondResponse->json('data.id')
        );
        $this->assertDatabaseCount('transactions', 1);
        Event::assertDispatchedTimes(WalletDashboardUpdated::class, 2);
        Event::assertDispatched(WalletDashboardUpdated::class, function (WalletDashboardUpdated $event) use ($sender, $receiver): bool {
            $payload = $event->payload();

            return $event->userId() === $sender->id
                && ($payload['event_type'] ?? null) === 'transfer_completed'
                && ($payload['wallet']['user_id'] ?? null) === $sender->id
                && ! empty($payload['transactions']);
        });

        Event::assertDispatched(WalletDashboardUpdated::class, function (WalletDashboardUpdated $event) use ($receiver): bool {
            $payload = $event->payload();

            return $event->userId() === $receiver->id
                && ($payload['event_type'] ?? null) === 'transfer_completed'
                && ($payload['wallet']['user_id'] ?? null) === $receiver->id
                && ! empty($payload['transactions']);
        });
    }

    public function test_reverse_restores_balances_and_is_idempotent(): void
    {
        [$sender, $senderWallet] = $this->createUserWithWallet(100);
        [$receiver, $receiverWallet] = $this->createUserWithWallet(10);
        Sanctum::actingAs($sender);
        Event::fake([WalletDashboardUpdated::class]);

        $transferResponse = $this->postJson('/api/v1/transfer', [
            'receiver_user_id' => $receiver->id,
            'amount' => 40,
        ])->assertOk();

        $transactionId = $transferResponse->json('data.id');

        $firstReverse = $this->postJson("/api/v1/reverse/{$transactionId}");
        $firstReverse->assertOk();

        $secondReverse = $this->postJson("/api/v1/reverse/{$transactionId}");
        $secondReverse->assertOk();

        $senderWallet->refresh();
        $receiverWallet->refresh();
        $originalTransaction = Transaction::query()->findOrFail($transactionId);

        $this->assertSame('100.00', $senderWallet->balance);
        $this->assertSame('10.00', $receiverWallet->balance);
        $this->assertSame('reversed', $originalTransaction->status);
        $this->assertSame(
            $firstReverse->json('data.id'),
            $secondReverse->json('data.id')
        );
        $this->assertDatabaseCount('transactions', 2);

        Event::assertDispatched(WalletDashboardUpdated::class, function (WalletDashboardUpdated $event) use ($sender, $receiver): bool {
            $payload = $event->payload();

            return $event->userId() === $sender->id
                && ($payload['event_type'] ?? null) === 'reversal_completed'
                && ($payload['wallet']['user_id'] ?? null) === $sender->id
                && count($payload['transactions'] ?? []) >= 1;
        });

        Event::assertDispatched(WalletDashboardUpdated::class, function (WalletDashboardUpdated $event) use ($receiver): bool {
            $payload = $event->payload();

            return $event->userId() === $receiver->id
                && ($payload['event_type'] ?? null) === 'reversal_completed'
                && ($payload['wallet']['user_id'] ?? null) === $receiver->id
                && count($payload['transactions'] ?? []) >= 1;
        });
    }

    private function createUserWithWallet(float $balance): array
    {
        $money = Money::fromDecimal((string) $balance);

        $user = User::factory()->create();
        $wallet = Wallet::query()->create([
            'user_id' => $user->id,
            'balance' => $money->toDecimal(),
            'balance_cents' => $money->cents(),
        ]);

        return [$user, $wallet];
    }
}
