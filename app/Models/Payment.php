<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
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
}