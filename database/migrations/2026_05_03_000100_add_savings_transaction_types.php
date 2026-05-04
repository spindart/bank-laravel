<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE transactions DROP CHECK chk_transactions_type_wallets');
        DB::statement("ALTER TABLE transactions MODIFY type ENUM('deposit', 'transfer', 'reversal', 'savings_deposit', 'savings_withdraw', 'savings_cancel_refund') NOT NULL");
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT chk_transactions_type_wallets CHECK (
            (type = 'deposit' AND sender_wallet_id IS NULL AND receiver_wallet_id IS NOT NULL AND original_transaction_id IS NULL) OR
            (type = 'transfer' AND sender_wallet_id IS NOT NULL AND receiver_wallet_id IS NOT NULL AND sender_wallet_id <> receiver_wallet_id AND original_transaction_id IS NULL) OR
            (type = 'reversal' AND original_transaction_id IS NOT NULL AND (sender_wallet_id IS NOT NULL OR receiver_wallet_id IS NOT NULL)) OR
            (type IN ('savings_deposit', 'savings_withdraw', 'savings_cancel_refund') AND sender_wallet_id IS NOT NULL AND receiver_wallet_id IS NOT NULL AND sender_wallet_id = receiver_wallet_id AND original_transaction_id IS NULL)
        )");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE transactions DROP CHECK chk_transactions_type_wallets');
        DB::statement("ALTER TABLE transactions MODIFY type ENUM('deposit', 'transfer', 'reversal') NOT NULL");
        DB::statement("ALTER TABLE transactions ADD CONSTRAINT chk_transactions_type_wallets CHECK (
            (type = 'deposit' AND sender_wallet_id IS NULL AND receiver_wallet_id IS NOT NULL AND original_transaction_id IS NULL) OR
            (type = 'transfer' AND sender_wallet_id IS NOT NULL AND receiver_wallet_id IS NOT NULL AND sender_wallet_id <> receiver_wallet_id AND original_transaction_id IS NULL) OR
            (type = 'reversal' AND original_transaction_id IS NOT NULL AND (sender_wallet_id IS NOT NULL OR receiver_wallet_id IS NOT NULL))
        )");
    }
};
