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

    /**
     * Reserve the page's current slug forever when it is deleted, so a released
     * pages.slug can never be silently reused while the old public URL still
     * exists somewhere. The reservation carries no page_id, so a deleted page's
     * slug stays permanently dead (404) and never redirects elsewhere.
     */
    protected static function booted(): void
    {
        static::deleting(function (Page $page) {
            if ($page->slug !== null && $page->slug !== '') {
                PageSlugHistory::query()->firstOrCreate(['slug' => $page->slug]);
            }
        });
    }
}
