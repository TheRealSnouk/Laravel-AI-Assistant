<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubscriptionModifier extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'type', // 'addon' or 'discount'
        'stripe_price_id',
        'stripe_coupon_id',
        'name',
        'description',
        'amount',
        'currency',
        'billing_type', // 'one_time' or 'recurring'
        'status',
        'starts_at',
        'ends_at',
        'metadata'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'metadata' => 'array'
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function rules()
    {
        return $this->hasMany(ModifierRule::class, 'modifier_id');
    }

    public function timeBasedRules()
    {
        return $this->hasMany(TimeBasedRule::class, 'modifier_id');
    }

    public function holidayRules()
    {
        return $this->hasMany(HolidayRule::class, 'modifier_id');
    }
}
