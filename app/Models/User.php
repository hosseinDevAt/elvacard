<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'phone',
        'email',
        'password',
        'address',
        'postal_code',
        'plaque',
        'is_active',
        'blocked_at',
        'blocked_reason',
    ];

    protected $guarded = [
        'role',
        'email_verified_at',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'blocked_at' => 'datetime',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Display name: first + last when available, otherwise the full name.
     */
    public function displayName(): string
    {
        if (filled($this->first_name) || filled($this->last_name)) {
            return trim(($this->first_name ?? '').' '.($this->last_name ?? ''));
        }

        return (string) $this->name;
    }

    /**
     * Relative path to the role-appropriate dashboard after login.
     */
    public function dashboardRoute(): string
    {
        return $this->role === 'admin'
            ? route('admin.dashboard', absolute: false)
            : route('account.dashboard', absolute: false);
    }
}
