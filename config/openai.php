<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Open Router API Key
    |--------------------------------------------------------------------------
    |
    | Your Open Router API key from https://openrouter.ai/keys
    | Should start with 'sk-or-'
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
    'model' => 'anthropic/claude-2',
];
