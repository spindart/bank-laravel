<?php

namespace Tests\Feature;

use App\Events\WalletDashboardUpdated;
use App\Jobs\SavingsBoxDepositJob;
use App\Models\SavingsBox;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\ValueObjects\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SavingsBoxApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_creates_savings_box_with_valid_data(): void
    {
        [$user] = $this->createUserWithWallet(100);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/savings-boxes', [
            'name' => 'Reserva de emergencia',
            'description' => 'Dinheiro separado',
            'target_amount' => '1000.00',
            'target_date' => now()->addMonth()->toDateString(),
            'icon' => 'bi-shield-check',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Reserva de emergencia')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.target_amount', '1000.00');

        $this->assertDatabaseHas('savings_boxes', [
            'user_id' => $user->id,
            'name' => 'Reserva de emergencia',
            'target_amount_cents' => 100000,
            'current_amount_cents' => 0,
            'status' => 'active',
        ]);
    }

    public function test_user_cannot_create_savings_box_with_invalid_target_amount(): void
    {
        [$user] = $this->createUserWithWallet(100);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/savings-boxes', [
            'name' => 'Meta',
            'target_amount' => 0,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['target_amount']);
    }

    public function test_user_cannot_access_other_user_savings_box(): void
    {
        [$owner] = $this->createUserWithWallet(100);
        [$other] = $this->createUserWithWallet(100);
        $box = SavingsBox::query()->create($this->boxPayload($owner->id));
        Sanctum::actingAs($other);

        $this->getJson("/api/v1/savings-boxes/{$box->id}")
            ->assertStatus(404);
    }

    public function test_user_deposits_money_with_sufficient_balance(): void
    {
        [$user, $wallet] = $this->createUserWithWallet(250);
        $box = SavingsBox::query()->create($this->boxPayload($user->id, targetCents: 10000));
        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/savings-boxes/{$box->id}/deposit", [
            'amount' => '40.00',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.type', 'savings_deposit')
            ->assertJsonPath('data.status', 'pending');

        $wallet->refresh();
        $box->refresh();

        $this->assertSame('210.00', $wallet->balance);
        $this->assertSame('40.00', $box->current_amount);
        $this->assertDatabaseHas('transactions', [
            'type' => 'savings_deposit',
            'amount' => '40.00',
            'sender_wallet_id' => $wallet->id,
            'receiver_wallet_id' => $wallet->id,
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('savings_box_movements', [
            'savings_box_id' => $box->id,
            'type' => 'deposit',
            'amount_cents' => 4000,
        ]);
    }

    public function test_savings_box_deposit_is_queued_as_job(): void
    {
        [$user] = $this->createUserWithWallet(250);
        $box = SavingsBox::query()->create($this->boxPayload($user->id, targetCents: 10000));
        Sanctum::actingAs($user);
        Queue::fake();

        $this->postJson("/api/v1/savings-boxes/{$box->id}/deposit", [
            'amount' => '40.00',
        ])->assertOk();

        Queue::assertPushed(SavingsBoxDepositJob::class);
        $this->assertDatabaseHas('transactions', [
            'type' => 'savings_deposit',
            'amount' => '40.00',
            'status' => 'pending',
        ]);
    }

    public function test_savings_box_deposit_broadcasts_dashboard_payload(): void
    {
        [$user] = $this->createUserWithWallet(250);
        $box = SavingsBox::query()->create($this->boxPayload($user->id, targetCents: 10000));
        Sanctum::actingAs($user);
        Event::fake([WalletDashboardUpdated::class]);

        $this->postJson("/api/v1/savings-boxes/{$box->id}/deposit", [
            'amount' => '40.00',
        ])->assertOk();

        Event::assertDispatched(WalletDashboardUpdated::class, function (WalletDashboardUpdated $event) use ($user): bool {
            $payload = $event->payload();

            return $event->userId() === $user->id
                && ($payload['event_type'] ?? null) === 'savings_box_deposit_completed'
                && ($payload['wallet']['balance'] ?? null) === '210.00'
                && ($payload['savings_summary']['total_saved'] ?? null) === '40.00'
                && count($payload['savings_boxes'] ?? []) === 1
                && ($payload['savings_boxes'][0]['current_amount'] ?? null) === '40.00'
                && ($payload['transactions'][0]['type'] ?? null) === 'savings_deposit';
        });
    }

    public function test_user_cannot_deposit_with_insufficient_balance(): void
    {
        [$user, $wallet] = $this->createUserWithWallet(10);
        $box = SavingsBox::query()->create($this->boxPayload($user->id));
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/savings-boxes/{$box->id}/deposit", [
            'amount' => '20.00',
        ])->assertStatus(422);

        $wallet->refresh();
        $box->refresh();

        $this->assertSame('10.00', $wallet->balance);
        $this->assertSame('0.00', $box->current_amount);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_user_withdraws_money_from_savings_box(): void
    {
        [$user, $wallet] = $this->createUserWithWallet(100);
        $box = SavingsBox::query()->create($this->boxPayload($user->id, currentCents: 6000));
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/savings-boxes/{$box->id}/withdraw", [
            'amount' => '25.00',
        ])->assertOk();

        $wallet->refresh();
        $box->refresh();

        $this->assertSame('125.00', $wallet->balance);
        $this->assertSame('35.00', $box->current_amount);
        $this->assertDatabaseHas('transactions', ['type' => 'savings_withdraw', 'amount' => '25.00']);
        $this->assertDatabaseHas('savings_box_movements', ['type' => 'withdraw', 'balance_after_cents' => 3500]);
    }

    public function test_user_cannot_withdraw_more_than_savings_box_balance(): void
    {
        [$user, $wallet] = $this->createUserWithWallet(100);
        $box = SavingsBox::query()->create($this->boxPayload($user->id, currentCents: 1000));
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/savings-boxes/{$box->id}/withdraw", [
            'amount' => '20.00',
        ])->assertStatus(422);

        $wallet->refresh();
        $box->refresh();

        $this->assertSame('100.00', $wallet->balance);
        $this->assertSame('10.00', $box->current_amount);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_cancel_refunds_savings_box_balance_to_wallet(): void
    {
        [$user, $wallet] = $this->createUserWithWallet(75);
        $box = SavingsBox::query()->create($this->boxPayload($user->id, currentCents: 2500));
        Sanctum::actingAs($user);

        $this->deleteJson("/api/v1/savings-boxes/{$box->id}")
            ->assertOk();

        $wallet->refresh();

        $this->assertSame('100.00', $wallet->balance);
        $this->assertDatabaseHas('transactions', ['type' => 'savings_cancel_refund', 'amount' => '25.00']);
        $this->assertDatabaseHas('savings_box_movements', [
            'savings_box_id' => $box->id,
            'type' => 'cancel_refund',
            'balance_before_cents' => 2500,
            'balance_after_cents' => 0,
        ]);
    }

    public function test_deposit_marks_savings_box_completed_when_target_is_reached(): void
    {
        [$user] = $this->createUserWithWallet(100);
        $box = SavingsBox::query()->create($this->boxPayload($user->id, targetCents: 5000, currentCents: 3000));
        Sanctum::actingAs($user);

        $this->postJson("/api/v1/savings-boxes/{$box->id}/deposit", [
            'amount' => '20.00',
        ])->assertOk();

        $box->refresh();

        $this->assertSame('completed', $box->status);
    }

    public function test_general_history_displays_savings_transactions(): void
    {
        [$user, $wallet] = $this->createUserWithWallet(100);
        Sanctum::actingAs($user);

        Transaction::query()->create([
            'type' => 'savings_deposit',
            'amount' => '10.00',
            'amount_cents' => 1000,
            'sender_wallet_id' => $wallet->id,
            'receiver_wallet_id' => $wallet->id,
            'status' => 'completed',
        ]);

        $this->getJson('/api/v1/transactions?type=savings_deposit')
            ->assertOk()
            ->assertJsonPath('data.0.type', 'savings_deposit');
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

    private function boxPayload(int $userId, int $targetCents = 100000, int $currentCents = 0): array
    {
        return [
            'user_id' => $userId,
            'name' => 'Notebook novo',
            'description' => 'Meta pessoal',
            'target_amount' => Money::fromCents($targetCents)->toDecimal(),
            'target_amount_cents' => $targetCents,
            'current_amount' => Money::fromCents($currentCents)->toDecimal(),
            'current_amount_cents' => $currentCents,
            'status' => $currentCents >= $targetCents ? 'completed' : 'active',
            'icon' => 'bi-laptop',
        ];
    }
}
