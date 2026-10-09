<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderProcessStage extends Model
{
    use HasFactory;

    public const SUPPORTED_ICONS = [
        'palette' => 'پالت رنگ / طراحی',
        'credit-card' => 'کارت بانکی',
        'card' => 'کارت',
        'cog' => 'تولید و ساخت',
        'check-badge' => 'کنترل کیفیت و تأیید',
        'box' => 'بسته‌بندی و آماده‌سازی',
        'shopping-bag' => 'ثبت سفارش',
        'sparkles' => 'شخصی‌سازی ویژه',
        'document-text' => 'بررسی اطلاعات',
        'banknote' => 'پرداخت',
        'wallet' => 'کیف پول',
    ];

    protected $fillable = [
        'guide_id',
        'title',
        'description',
        'duration_hint',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function guide(): BelongsTo
    {
        return $this->belongsTo(OrderProcessGuide::class, 'guide_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
