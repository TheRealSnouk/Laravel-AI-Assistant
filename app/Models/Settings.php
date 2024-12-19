<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Settings extends Model
{
    protected $fillable = [
        'api_provider',
        'api_key',
        'selected_model',
        'use_default_key',
        'session_id'
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if ($model->api_key && !$model->use_default_key) {
                $model->api_key = Crypt::encryptString($model->api_key);
            }
        });
    }

    public function getApiKeyAttribute($value)
    {
        if ($value && !$this->use_default_key) {
            return Crypt::decryptString($value);
        }
        return $value;
    }

    public static function getAvailableModels()
    {
        return [
            'openrouter' => [
                'anthropic/claude-2' => 'Claude 2',
                'anthropic/claude-instant-1' => 'Claude Instant',
                'google/palm-2-chat-bison' => 'PaLM 2 Chat',
                'meta-llama/llama-2-70b-chat' => 'Llama 2 70B',
                'mistral-ai/mistral-7b-instruct' => 'Mistral 7B'
            ],
            'bolt' => [
                'bolt-not-diamond' => 'Bolt Not Diamond',
                'bolt-not-diamond-light' => 'Bolt Not Diamond Light'
            ],
            'notdiamond' => [
                'notdiamond-v1' => 'NotDiamond v1',
                'notdiamond-v2' => 'NotDiamond v2',
                'notdiamond-light' => 'NotDiamond Light'
            ]
        ];
    }
}
