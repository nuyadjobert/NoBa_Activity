<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'address',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password'          => 'hashed',
    ];

    // Roles
    const ROLE_ADMIN    = 'admin';
    const ROLE_CUSTOMER = 'customer';

    // Relationships
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function isAdmin(): bool
{
    return $this->role === self::ROLE_ADMIN;
}

public function isCustomer(): bool
{
    return $this->role === self::ROLE_CUSTOMER;
}
}