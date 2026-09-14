<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'method',
        'status',
        'amount',
        'paid_amount',
        'tracking_code',
        'transaction_id',
        'receipt_path',
        'gateway',
        'metadata',
        'paid_at',
    ];

    protected $casts = [
        'method' => PaymentMethod::class,
        'status' => PaymentStatus::class,
        'amount' => 'integer',
        'paid_amount' => 'integer',
        'metadata' => 'array',
        'paid_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Payments that represent an in-progress active attempt: a gateway payment
     * still pending at the provider, a manual receipt awaiting review, or an
     * already successful payment. These must block a new payment attempt.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [
            PaymentStatus::PENDING->value,
            PaymentStatus::PENDING_REVIEW->value,
            PaymentStatus::SUCCESS->value,
        ]);
    }
}
