<?php

namespace App\Models;

use App\Enums\OrderStatusEnum;
use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
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
     * Generate the next human readable 5-digit order number (10001 to 99999).
     * Preserves historical alphanumeric references like 'ORD-2026-000001'.
     *
     * @throws \RuntimeException when the 5-digit sequence limit (99999) is reached.
     */
    public static function nextReference(): string
    {
        $query = DB::table('orders')
            ->whereNotNull('reference')
            ->whereRaw('LENGTH(reference) = 5')
            ->whereRaw("reference >= '10000'")
            ->whereRaw("reference <= '99999'")
            ->orderByDesc('reference')
            ->limit(1);

        if (DB::getDriverName() === 'mysql') {
            $query->lockForUpdate();
        }

        $max = $query->value('reference');
        $next = $max !== null ? ((int) $max) + 1 : 10001;

        if ($next < 10001) {
            $next = 10001;
        }

        if ($next > 99999) {
            throw new \RuntimeException('ظرفیت شماره سفارش‌های ۵ رقمی تکمیل شده است (حداکثر ۹۹۹۹۹).');
        }

        return (string) $next;
    }

    /**
     * Customer-facing status label mapped from internal order and payment status.
     * Guaranteed strictly in 4 stages:
     * 1. در حال بررسی
     * 2. تایید پرداخت
     * 3. درحال آماده سازی
     * 4. تکمیل شد
     * (With a safe cancelled fallback if cancelled).
     */
    public function customerStatusLabel(): string
    {
        if ($this->status === OrderStatusEnum::CANCELLED) {
            return 'لغو شده';
        }

        if ($this->status === OrderStatusEnum::COMPLETED) {
            return 'تکمیل شد';
        }

        if ($this->status === OrderStatusEnum::PRODUCTION || $this->status === OrderStatusEnum::PROCESSING) {
            return 'درحال آماده سازی';
        }

        if ($this->status === OrderStatusEnum::CONFIRMED && $this->payment_status === PaymentStatusEnum::PAID) {
            return 'تایید پرداخت';
        }

        if ($this->payment_status === PaymentStatusEnum::PAID && $this->status === OrderStatusEnum::PENDING) {
            return 'تایید پرداخت';
        }

        return 'در حال بررسی';
    }

    /**
     * Numerical step of the customer tracking stepper (1 to 4).
     */
    public function customerStatusStep(): int
    {
        if ($this->status === OrderStatusEnum::CANCELLED) {
            return 0;
        }

        return match ($this->customerStatusLabel()) {
            'در حال بررسی' => 1,
            'تایید پرداخت' => 2,
            'درحال آماده سازی' => 3,
            'تکمیل شد' => 4,
            default => 1,
        };
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

    public function refunds(): HasManyThrough
    {
        return $this->hasManyThrough(Refund::class, Payment::class);
    }

    public function scopeStatus(Builder $query, OrderStatusEnum|string $status): Builder
    {
        return $query->where('status', $status instanceof OrderStatusEnum ? $status->value : $status);
    }
}
