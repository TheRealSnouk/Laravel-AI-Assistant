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
    'bolt_api_key' => trim(env('BOLT_API_KEY', '')),
    'notdiamond_api_key' => trim(env('NOTDIAMOND_API_KEY', '')),

    /*
    |--------------------------------------------------------------------------
    | Models Configuration
    |--------------------------------------------------------------------------
    |
    | Default model settings for the application
    |
    */
    'default_model' => 'anthropic/claude-2',
    'default_bolt_model' => 'bolt-not-diamond',
    'default_notdiamond_model' => 'notdiamond-v2',

    /*
    |--------------------------------------------------------------------------
    | API Endpoints
    |--------------------------------------------------------------------------
    |
    | Base URLs for different API providers
    |
    */
    'openrouter_url' => 'https://openrouter.ai/api/v1',
    'bolt_url' => 'https://not-diamond.bolt.new/v1',
    'notdiamond_url' => 'https://api.notdiamond.ai/v1',
];
