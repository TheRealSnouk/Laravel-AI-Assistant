<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Keys
    |--------------------------------------------------------------------------
    |
    | Your API keys for different providers
    |
    */
    'api_key' => trim(env('OPENROUTER_API_KEY', '')),

    /*
    |--------------------------------------------------------------------------
    | Models Configuration
    |--------------------------------------------------------------------------
    |
    | Default model settings for the application
    |
    */
    'default_model' => 'anthropic/claude-2',

    /*
    |--------------------------------------------------------------------------
    | API Endpoints
    |--------------------------------------------------------------------------
    |
    | Base URLs for different API providers
    |
    */
    'openrouter_url' => 'https://openrouter.ai/api/v1',
];
