<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductColorPrice extends Model
{
    protected $fillable = [
        'product_id',
        'color_id',
        'price',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function color()
    {
        return $this->belongsTo(Color::class);
    }
}