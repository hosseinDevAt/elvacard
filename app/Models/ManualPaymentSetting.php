<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManualPaymentSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'card_number',
        'iban',
        'account_name',
        'instruction_message',
        'success_message',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}