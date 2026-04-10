<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table): void {
            $table->bigInteger('balance_cents')->default(0)->after('balance');
            $table->index('balance_cents');
        });

        Schema::table('transactions', function (Blueprint $table): void {
            $table->bigInteger('amount_cents')->default(0)->after('amount');
            $table->index('amount_cents');
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('UPDATE wallets SET balance_cents = CAST(ROUND(balance * 100, 0) AS INTEGER)');
            DB::statement('UPDATE transactions SET amount_cents = CAST(ROUND(amount * 100, 0) AS INTEGER)');
        } else {
            DB::statement('UPDATE wallets SET balance_cents = CAST(ROUND(balance * 100, 0) AS SIGNED)');
            DB::statement('UPDATE transactions SET amount_cents = CAST(ROUND(amount * 100, 0) AS SIGNED)');
        }
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table): void {
            $table->dropIndex(['amount_cents']);
            $table->dropColumn('amount_cents');
        });

        Schema::table('wallets', function (Blueprint $table): void {
            $table->dropIndex(['balance_cents']);
            $table->dropColumn('balance_cents');
        });
    }
};
