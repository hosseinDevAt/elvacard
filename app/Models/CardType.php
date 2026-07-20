<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CardType extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'color_id',
        'base_price',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'integer',
            'is_available' => 'boolean',
        ];
    }

    public function scopeBank($query)
    {
        return $query->where('type', 'bank');
    }

    public function scopeFuel($query)
    {
        return $query->where('type', 'fuel');
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    public function customizations(): HasMany
    {
        return $this->hasMany(Customization::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
