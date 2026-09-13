<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name_snapshot',
        'color_id',
        'color_name_snapshot',
        'design_id',
        'design_name_snapshot',
        'design_image_id',
        'design_image_path_snapshot',
        'unit_price_snapshot',
        'quantity',
        'final_price',
        'customization_json',
    ];

    protected $casts = [
        'customization_json' => 'array',
        'color_id' => 'integer',
        'design_id' => 'integer',
        'design_image_id' => 'integer',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    public function design(): BelongsTo
    {
        return $this->belongsTo(Design::class);
    }

    public function designImage(): BelongsTo
    {
        return $this->belongsTo(DesignImage::class);
    }
}
