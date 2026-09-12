<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'page_type',
        'title',
        'slug',
        'content',
        'image_path',
        'meta_title',
        'meta_description',
        'canonical_url',
        'robots_index',
        'is_active',
    ];

    protected $casts = [
        'robots_index' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
