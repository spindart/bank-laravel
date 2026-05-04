<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_box_movements', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('savings_box_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->string('type', 32);
            $table->decimal('amount', 15, 2);
            $table->bigInteger('amount_cents');
            $table->decimal('balance_before', 15, 2);
            $table->bigInteger('balance_before_cents');
            $table->decimal('balance_after', 15, 2);
            $table->bigInteger('balance_after_cents');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['savings_box_id', 'created_at']);
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_box_movements');
    }
};
