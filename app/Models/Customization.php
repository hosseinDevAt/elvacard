<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Customization extends Model
{
    use HasFactory;

    protected $fillable = [
        'card_type_id',
        'design_image_id',
        'customizable_id',
        'customizable_type',
    ];

    public function cardType(): BelongsTo
    {
        return $this->belongsTo(CardType::class);
    }

    public function designImage(): BelongsTo
    {
        return $this->belongsTo(DesignImage::class);
    }

    public function customizable(): MorphTo
    {
        return $this->morphTo();
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
