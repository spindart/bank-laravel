<?php

namespace App\Models;

use App\ValueObjects\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SavingsBox extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'target_amount',
        'target_amount_cents',
        'current_amount',
        'current_amount_cents',
        'target_date',
        'status',
        'icon',
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'target_amount_cents' => 'integer',
        'current_amount' => 'decimal:2',
        'current_amount_cents' => 'integer',
        'target_date' => 'date:Y-m-d',
    ];

    protected $appends = [
        'progress_percent',
        'remaining_amount',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(SavingsBoxMovement::class);
    }

    public function getProgressPercentAttribute(): float
    {
        if ((int) $this->target_amount_cents <= 0) {
            return 0.0;
        }

        return round(min(100, ((int) $this->current_amount_cents / (int) $this->target_amount_cents) * 100), 2);
    }

    public function getRemainingAmountAttribute(): string
    {
        $remainingCents = max(0, (int) $this->target_amount_cents - (int) $this->current_amount_cents);

        return Money::fromCents($remainingCents)->toDecimal();
    }
}
