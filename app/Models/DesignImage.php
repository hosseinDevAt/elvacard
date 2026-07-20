<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DesignImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'design_id',
        'color_id',
        'image_path',
    ];

    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    public function colorRestrictions(): HasMany
    {
        return $this->hasMany(DesignColorRestriction::class, 'design_image_id');
    }

    public function customizations(): HasMany
    {
        return $this->hasMany(Customization::class);
    }

    public function isForbiddenOnColor(int $cardColorId): bool
    {
        return $this->colorRestrictions()
            ->where('forbidden_card_color_id', $cardColorId)
            ->exists();
    }
}
