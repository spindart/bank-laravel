<?php

namespace Tests\Unit;

use App\Exceptions\Finance\InsufficientBalanceException;
use App\Models\User;
use App\Models\Wallet;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_increases_wallet_balance(): void
    {
        [$user, $wallet] = $this->createUserWithWallet(0);
        $service = app(WalletService::class);

        $service->deposit($user, 25.30);
        $wallet->refresh();

        $this->assertSame('25.30', $wallet->balance);
    }

    public function test_transfer_throws_when_balance_is_insufficient(): void
    {
        [$sender] = $this->createUserWithWallet(10);
        [$receiver] = $this->createUserWithWallet(0);
        $service = app(WalletService::class);

        $this->expectException(InsufficientBalanceException::class);
        $service->transfer($sender, $receiver->id, 100);
    }

    public function test_reverse_is_idempotent_for_same_transaction(): void
    {
        [$sender, $senderWallet] = $this->createUserWithWallet(60);
        [$receiver, $receiverWallet] = $this->createUserWithWallet(15);
        $service = app(WalletService::class);

        $transfer = $service->transfer($sender, $receiver->id, 20);
        $firstReverse = $service->reverse($sender, $transfer->id);
        $secondReverse = $service->reverse($sender, $transfer->id);

        $senderWallet->refresh();
        $receiverWallet->refresh();

        $this->assertSame($firstReverse->id, $secondReverse->id);
        $this->assertSame('60.00', $senderWallet->balance);
        $this->assertSame('15.00', $receiverWallet->balance);
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

