<?php

namespace App\Services;

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
