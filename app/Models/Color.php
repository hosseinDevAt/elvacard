<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Color extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'color_code',
    ];

    public function designImages(): HasMany
    {
        return $this->hasMany(DesignImage::class);
    }

    public function forbiddenDesignImages(): HasMany
    {
        return $this->hasMany(DesignColorRestriction::class, 'forbidden_card_color_id');
    }

    public function cardTypes(): HasMany
    {
        return $this->hasMany(CardType::class);
    }
}
