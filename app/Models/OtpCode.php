<?php

namespace App\Models;

use App\Enums\OtpPurpose;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    protected $fillable = [
        'phone',
        'purpose',
        'code_hash',
        'expires_at',
        'consumed_at',
        'attempts',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public function scopeActive(Builder $query, string $phone, OtpPurpose $purpose): Builder
    {
        return $query
            ->where('phone', $phone)
            ->where('purpose', $purpose->value)
            ->whereNull('consumed_at');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}