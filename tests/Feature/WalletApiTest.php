<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WalletApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_updates_balance_and_creates_transaction(): void
    {
        [$user, $wallet] = $this->createUserWithWallet(0);
        Sanctum::actingAs($user);

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

    public function test_reverse_restores_balances_and_is_idempotent(): void
    {
        [$sender, $senderWallet] = $this->createUserWithWallet(100);
        [$receiver, $receiverWallet] = $this->createUserWithWallet(10);
        Sanctum::actingAs($sender);

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
    }

    private function createUserWithWallet(float $balance): array
    {
        $user = User::factory()->create();
        $wallet = Wallet::query()->create([
            'user_id' => $user->id,
            'balance' => $balance,
        ]);

        return [$user, $wallet];
    }
}

