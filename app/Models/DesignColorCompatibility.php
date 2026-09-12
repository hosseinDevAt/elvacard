<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DesignColorCompatibility extends Model
{
    protected $fillable = [
        'design_image_id',
        'card_color_id',
        'is_allowed',
    ];

    protected $casts = [
        'is_allowed' => 'boolean',
    ];

    public function designImage()
    {
        return $this->belongsTo(DesignImage::class);
    }

    public function cardColor()
    {
        return $this->belongsTo(Color::class, 'card_color_id');
    }
}