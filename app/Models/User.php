<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Subscription;
use App\Models\CryptoPayment;
use App\Models\ApiCall;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'last_active_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_active_at' => 'datetime',
        'is_admin' => 'boolean'
    ];

    public function subscription()
    {
        return $this->hasOne(Subscription::class)->latest();
    }

    public function subscriptionHistory()
    {
        return $this->hasMany(Subscription::class);
    }

    public function cryptoPayments()
    {
        return $this->hasMany(CryptoPayment::class);
    }

    public function apiCalls()
    {
        return $this->hasMany(ApiCall::class);
    }
}
