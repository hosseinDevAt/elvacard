<?php

namespace App\Models;

use App\Services\StoredFileManager;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'color_id',
        'image_path',
        'sort_order',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Remove a variant image's physical file once no product, product gallery
     * or product color variant still references its path. This is the single
     * reference-aware cleanup used by the model lifecycle hook and by the
     * application-level deletion flows that take image rows out through a
     * database cascade (product/color delete), where Eloquent events cannot
     * fire for the children.
     */
    public static function deleteFilesWhenUnreferenced(array $paths): void
    {
        app(StoredFileManager::class)->deletePublicFilesWhenUnreferenced(
            $paths,
            fn (string $path) => self::query()->where('image_path', $path)->exists()
                || Product::query()->where('main_image', $path)->exists()
                || Product::query()->where('og_image', $path)->exists(),
        );
    }

    protected static function booted(): void
    {
        // A single-row delete leaves the row gone, but the file must outlive
        // the row only if nothing else still points at it. Runs after the row
        // is removed so the reference check sees the true remaining set.
        static::deleted(function (ProductImage $image) {
            self::deleteFilesWhenUnreferenced([$image->image_path]);
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function color()
    {
        return $this->belongsTo(Color::class);
    }
}
