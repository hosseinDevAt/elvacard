<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FuelCardData extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_name',
        'car_model',
        'vin_number',
        'sys_number',
        'plate_number',
        'chip_type',
        'chip_size',
    ];

    public function customizations(): HasMany
    {
        return $this->morphMany(Customization::class, 'customizable');
    }
}
