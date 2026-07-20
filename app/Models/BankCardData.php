<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankCardData extends Model
{
    use HasFactory;

    protected $fillable = [
        'holder_name',
        'card_number',
        'cvv2',
        'expiry_date',
        'field_positions',
    ];

    protected $casts = [
        'field_positions' => 'array',
    ];

    public function customizations(): HasMany
    {
        return $this->morphMany(Customization::class, 'customizable');
    }
}
