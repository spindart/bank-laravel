<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('savings_boxes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->text('description')->nullable();
            $table->decimal('target_amount', 15, 2);
            $table->bigInteger('target_amount_cents');
            $table->decimal('current_amount', 15, 2)->default(0);
            $table->bigInteger('current_amount_cents')->default(0);
            $table->date('target_date')->nullable();
            $table->string('status', 24)->default('active');
            $table->string('icon', 40)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index('target_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('savings_boxes');
    }
};
