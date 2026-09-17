<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageSlugHistory extends Model
{
    protected $fillable = [
        'slug',
        'page_id',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
