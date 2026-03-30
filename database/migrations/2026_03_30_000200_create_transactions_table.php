<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->enum('type', ['deposit', 'transfer', 'reversal']);
            $table->decimal('amount', 15, 2);
            $table->foreignId('sender_wallet_id')->nullable()->constrained('wallets');
            $table->foreignId('receiver_wallet_id')->nullable()->constrained('wallets');
            $table->enum('status', ['pending', 'completed', 'reversed'])->default('pending');
            $table->foreignId('original_transaction_id')->nullable()->unique()->constrained('transactions');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['type', 'status']);
            $table->index('sender_wallet_id');
            $table->index('receiver_wallet_id');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE transactions ADD CONSTRAINT chk_transactions_amount CHECK (amount > 0)');
            DB::statement("ALTER TABLE transactions ADD CONSTRAINT chk_transactions_type_wallets CHECK (
                (type = 'deposit' AND sender_wallet_id IS NULL AND receiver_wallet_id IS NOT NULL AND original_transaction_id IS NULL) OR
                (type = 'transfer' AND sender_wallet_id IS NOT NULL AND receiver_wallet_id IS NOT NULL AND sender_wallet_id <> receiver_wallet_id AND original_transaction_id IS NULL) OR
                (type = 'reversal' AND original_transaction_id IS NOT NULL AND (sender_wallet_id IS NOT NULL OR receiver_wallet_id IS NOT NULL))
            )");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
