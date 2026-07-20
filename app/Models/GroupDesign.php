<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupDesign extends Model
{
    use HasFactory;

    protected $fillable = [
        'cate_design_id',
        'name',
    ];

    public function cateDesign(): BelongsTo
    {
        return $this->belongsTo(CateDesign::class);
    }

    public function designs(): HasMany
    {
        return $this->hasMany(Design::class);
    }
}
