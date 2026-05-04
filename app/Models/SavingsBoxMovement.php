<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavingsBoxMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'savings_box_id',
        'user_id',
        'transaction_id',
        'type',
        'amount',
        'amount_cents',
        'balance_before',
        'balance_before_cents',
        'balance_after',
        'balance_after_cents',
        'description',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_cents' => 'integer',
        'balance_before' => 'decimal:2',
        'balance_before_cents' => 'integer',
        'balance_after' => 'decimal:2',
        'balance_after_cents' => 'integer',
    ];

    public function savingsBox(): BelongsTo
    {
        return $this->belongsTo(SavingsBox::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
