<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Design extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_design_id',
        'name',
        'image_path',
    ];

    public function groupDesign(): BelongsTo
    {
        return $this->belongsTo(GroupDesign::class);
    }

    public function designImages(): HasMany
    {
        return $this->hasMany(DesignImage::class);
    }
}
