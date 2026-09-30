<?php

namespace App\Services;

use App\Models\DesignImage;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Storage;

class StoredFileManager
{
    /**
     * Delete a file on the public disk. Safe for database-held paths: only
     * plain relative paths are accepted, and a missing file is a silent no-op.
     */
    public function deletePublicFile(?string $path): bool
    {
        if (! $this->isSafePublicPath($path)) {
            return false;
        }

        return Storage::disk('public')->delete(trim($path));
    }

    /**
     * Delete a set of files, skipping any that are still referenced by the
     * database according to the given callback.
     */
    public function deletePublicFilesWhenUnreferenced(array $paths, callable $isReferenced): void
    {
        foreach (array_unique(array_filter($paths)) as $path) {
            $path = (string) $path;

            if (! $isReferenced($path)) {
                $this->deletePublicFile($path);
            }
        }
    }

    /**
     * Delete design image files, but only once no live design image and no
     * historical order still points at them.
     *
     * An OrderItem keeps the purchased asset path in
     * design_image_path_snapshot, and the design_images row is allowed to
     * disappear from the catalog (the foreign key is nullOnDelete). Without the
     * order check the file would be unlinked and every paid order that bought
     * that design would be left pointing at a missing asset.
     */
    public function deleteDesignImageFilesWhenUnreferenced(array $paths): void
    {
        $this->deletePublicFilesWhenUnreferenced(
            $paths,
            fn (string $path): bool => $this->isDesignImageFileReferenced($path),
        );
    }

    public function isDesignImageFileReferenced(string $path): bool
    {
        return DesignImage::query()->where('image_path', $path)->exists()
            || OrderItem::query()->where('design_image_path_snapshot', $path)->exists();
    }

    private function isSafePublicPath(?string $path): bool
    {
        if ($path === null || trim($path) === '') {
            return false;
        }

        $normalized = str_replace('\\', '/', trim($path));

        if (str_starts_with($normalized, '/')) {
            return false;
        }

        if (preg_match('/^[a-zA-Z]:/', $normalized)) {
            return false;
        }

        return ! in_array('..', explode('/', $normalized), true);
    }
}
