<?php

namespace App\Models;

use App\Enums\MenuItemTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'menu_id',
        'item_type',
        'route_key',
        'title',
        'target_id',
        'custom_url',
        'target',
        'settings',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'item_type' => MenuItemTypeEnum::class,
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Resolve the URL for this menu item based on its route_key or type+target.
     * Returns safe URL or null if item cannot be resolved.
     */
    public function resolveUrl(): ?string
    {
        if ($this->route_key) {
            return match ($this->route_key) {
                'home' => route('home'),
                'about' => route('pages.show', 'about-us'),
                'contact' => route('pages.show', 'contact-us'),
                'shop' => route('catalog.products.index'),
                'custom_card_design' => route('custom-card.design'),
                default => null,
            };
        }

        return match ($this->item_type?->value) {
            'url' => safe_url($this->custom_url),

            'page' => $this->resolvePageUrl(),

            'product' => $this->resolveProductUrl(),

            'design' => $this->resolveDesignUrl(),

            'article' => $this->resolveArticleUrl(),

            'category' => null,

            default => null,
        };
    }

    private function resolvePageUrl(): ?string
    {
        if (! $this->target_id) {
            return null;
        }

        $page = \App\Models\Page::query()
            ->active()
            ->find($this->target_id);

        return $page ? route('pages.show', $page->slug) : null;
    }

    private function resolveProductUrl(): ?string
    {
        if (! $this->target_id) {
            return null;
        }

        $product = \App\Models\Product::query()
            ->active()
            ->find($this->target_id);

        return $product ? route('catalog.products.show', $product->slug) : null;
    }

    private function resolveDesignUrl(): ?string
    {
        if (! $this->target_id) {
            return null;
        }

        $design = \App\Models\Design::query()
            ->active()
            ->find($this->target_id);

        return $design ? route('catalog.designs.index', ['category' => $design->category?->slug]) . '#design-' . $design->id : null;
    }

    private function resolveArticleUrl(): ?string
    {
        if (! $this->target_id) {
            return null;
        }

        $article = \App\Models\Article::query()
            ->where('status', \App\Enums\ArticleStatusEnum::PUBLISHED->value)
            ->where(function ($q) {
                $q->whereNull('published_at')
                  ->orWhere('published_at', '<=', now());
            })
            ->find($this->target_id);

        return $article ? route('articles.show', $article->slug) : null;
    }
}
