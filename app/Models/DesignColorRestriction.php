<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignColorRestriction extends Model
{
    use HasFactory;

    protected $fillable = [
        'design_image_id',
        'forbidden_card_color_id',
    ];

    public function designImage(): BelongsTo
    {
        return $this->belongsTo(DesignImage::class);
    }

    public function forbiddenCardColor(): BelongsTo
    {
        return $this->belongsTo(Color::class, 'forbidden_card_color_id');
    }
}
