<?php

namespace App\Models;

use App\Enums\HomepageSectionTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomepageSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_type',
        'title',
        'content',
        'settings',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'section_type' => HomepageSectionTypeEnum::class,
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
