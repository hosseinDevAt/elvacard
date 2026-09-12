<?php

namespace App\Models;

use App\Enums\ArticleStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'cover_image',
        'status',
        'published_at',
        'meta_title',
        'meta_description',
        'canonical_url',
        'robots_index',
    ];

    protected $casts = [
        'status' => ArticleStatusEnum::class,
        'robots_index' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ArticleCategory::class, 'article_category_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ArticleStatusEnum::PUBLISHED->value);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', ArticleStatusEnum::DRAFT->value);
    }
}
