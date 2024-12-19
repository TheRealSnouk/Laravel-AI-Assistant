<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\SubscriptionModifier;

class Subscription extends Model
{
    protected $fillable = [
        'session_id',
        'tier',
        'status',
        'starts_at',
        'expires_at',
        'api_calls_limit',
        'api_calls_used'
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'api_calls_limit' => 'integer',
        'api_calls_used' => 'integer'
    ];

    public function isActive()
    {
        return $this->status === 'active' && 
               now()->between($this->starts_at, $this->expires_at) &&
               $this->api_calls_used < $this->api_calls_limit;
    }

    public function hasAvailableCalls()
    {
        return $this->api_calls_used < $this->api_calls_limit;
    }

    public function incrementApiCalls()
    {
        $this->increment('api_calls_used');
    }

    public static function getTierLimits()
    {
        return [
            'free' => [
                'daily_limit' => 50,
                'models' => ['openrouter' => ['anthropic/claude-instant-1', 'google/palm-2-chat-bison']],
                'max_tokens' => 1000
            ],
            'basic' => [
                'daily_limit' => 200,
                'models' => [
                    'openrouter' => ['anthropic/claude-instant-1', 'google/palm-2-chat-bison', 'meta-llama/llama-2-70b-chat'],
                    'bolt' => ['bolt-not-diamond-light']
                ],
                'max_tokens' => 2000
            ],
            'pro' => [
                'daily_limit' => 1000,
                'models' => [
                    'openrouter' => ['anthropic/claude-2', 'anthropic/claude-instant-1', 'google/palm-2-chat-bison', 'meta-llama/llama-2-70b-chat'],
                    'bolt' => ['bolt-not-diamond', 'bolt-not-diamond-light'],
                    'notdiamond' => ['notdiamond-v2', 'notdiamond-v1', 'notdiamond-light']
                ],
                'max_tokens' => 4000
            ]
        ];
    }

    public function getAvailableModels()
    {
        return self::getTierLimits()[$this->tier]['models'] ?? [];
    }

    public function getMaxTokens()
    {
        return self::getTierLimits()[$this->tier]['max_tokens'] ?? 1000;
    }

    public function modifiers()
    {
        return $this->hasMany(SubscriptionModifier::class);
    }
}
