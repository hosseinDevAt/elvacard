<?php

namespace App\Support\Concerns;

use Illuminate\Support\Str;

trait GeneratesUniqueSlug
{
    protected function uniqueSlug(string $name, string $modelClass, ?int $exceptId = null, string $fallback = 'item'): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = $fallback.'-'.Str::random(8);
        }

        $slug = $base;
        $counter = 2;

        while ($modelClass::where('slug', $slug)->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}