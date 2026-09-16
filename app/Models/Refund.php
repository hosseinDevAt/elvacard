<?php

namespace App\Models;

use App\Enums\RefundStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'amount',
        'status',
        'reason',
        'metadata',
        'refunded_at',
    ];

    protected $casts = [
        'status' => RefundStatus::class,
        'amount' => 'integer',
        'metadata' => 'array',
        'refunded_at' => 'datetime',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
