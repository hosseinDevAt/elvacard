<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Menu extends Model
{
    use HasFactory;

    /**
     * In-memory request-level cache for resolved menu structures.
     *
     * @var array<string, array{id: int, name: string, location: string, items: array<int, array{id: int, title: string, url: ?string, target: string, sort_order: int}>}|null>
     */
    protected static array $runtimeCache = [];

    protected $fillable = [
        'name',
        'location',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    /**
     * Cache key for a given menu location.
     */
    public static function cacheKeyForLocation(string $location): string
    {
        return "menus.location.{$location}";
    }

    /**
     * Clear cache for a specific location (both persistent and request-level).
     */
    public static function clearLocationCache(?string $location): void
    {
        if ($location === null || $location === '') {
            return;
        }

        unset(static::$runtimeCache[$location]);
        Cache::forget(static::cacheKeyForLocation($location));
    }

    /**
     * Clear all menu location caches.
     */
    public static function clearAllCache(): void
    {
        static::$runtimeCache = [];
        Cache::forget(static::cacheKeyForLocation('header'));
        Cache::forget(static::cacheKeyForLocation('footer'));
    }

    /**
     * Clear in-memory runtime cache only.
     */
    public static function clearRuntimeCache(): void
    {
        static::$runtimeCache = [];
    }

    /**
     * Retrieve the cached active menu and its active items for a given location.
     *
     * @return array{id: int, name: string, location: string, items: array<int, array{id: int, title: string, url: ?string, target: string, sort_order: int}>}|null
     */
    public static function getCachedForLocation(string $location): ?array
    {
        if (array_key_exists($location, static::$runtimeCache)) {
            return static::$runtimeCache[$location];
        }

        $cacheKey = static::cacheKeyForLocation($location);

        $data = Cache::remember($cacheKey, 86400, function () use ($location) {
            $menu = static::query()
                ->where('location', $location)
                ->with([
                    'items' => fn ($query) => $query
                        ->active()
                        ->orderBy('sort_order'),
                ])
                ->first();

            if (! $menu) {
                return null;
            }

            $items = [];
            foreach ($menu->items as $item) {
                $items[] = [
                    'id' => $item->id,
                    'title' => $item->title,
                    'url' => $item->resolveUrl(),
                    'target' => $item->target ?? '_self',
                    'sort_order' => (int) $item->sort_order,
                ];
            }

            return [
                'id' => $menu->id,
                'name' => $menu->name,
                'location' => $menu->location,
                'items' => $items,
            ];
        });

        static::$runtimeCache[$location] = $data;

        return $data;
    }

    protected static function booted(): void
    {
        static::saved(function (Menu $menu) {
            $originalLocation = $menu->getOriginal('location');
            $currentLocation = $menu->location;

            static::clearLocationCache($originalLocation);
            if ($currentLocation && $currentLocation !== $originalLocation) {
                static::clearLocationCache($currentLocation);
            }
        });

        static::deleted(function (Menu $menu) {
            static::clearLocationCache($menu->location);
            static::clearLocationCache($menu->getOriginal('location'));
        });
    }
}
