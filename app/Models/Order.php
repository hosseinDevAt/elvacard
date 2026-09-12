<?php

namespace App\Models;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_name',
        'customer_phone',
        'shipping_address',
        'shipping_postal_code',
        'shipping_plaque',
        'shipping_description',
        'notes',
    ];

    protected $guarded = [
        'user_id',
        'reference',
        'token',
        'status',
        'payment_status',
        'total_price',
    ];

    protected $casts = [
        'status' => OrderStatusEnum::class,
        'payment_status' => PaymentStatusEnum::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (blank($order->reference)) {
                $order->reference = self::nextReference();
            }

            if (blank($order->token)) {
                $order->token = Str::random(40);
            }
        });
    }

    /**
     * Generate the next human readable order number for the current year.
     * Format: ORD-YYYY-NNNNNN (e.g. ORD-2026-000001).
     */
    public static function nextReference(): string
    {
        $year = now()->year;
        $prefix = "ORD-{$year}-";

        $query = DB::table('orders')
            ->where('reference', 'like', $prefix.'%')
            ->orderByDesc('reference')
            ->limit(1);

        // FOR UPDATE (row lock) is MySQL-specific; guarded for SQLite test DBs.
        if (DB::getDriverName() === 'mysql') {
            $query->lockForUpdate();
        }

        $max = $query->value('reference');
        $sequence = $max ? ((int) substr($max, -6)) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeStatus(Builder $query, OrderStatusEnum|string $status): Builder
    {
        return $query->where('status', $status instanceof OrderStatusEnum ? $status->value : $status);
    }
}
