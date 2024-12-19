<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
     * Payment Service Configurations
     */
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    'paypal' => [
        'client_id' => env('PAYPAL_CLIENT_ID'),
        'secret' => env('PAYPAL_SECRET'),
        'api_url' => env('PAYPAL_MODE', 'sandbox') === 'live'
            ? 'https://api.paypal.com'
            : 'https://api.sandbox.paypal.com',
    ],

    'hedera' => [
        'account_id' => env('HEDERA_ACCOUNT_ID'),
        'private_key' => env('HEDERA_PRIVATE_KEY'),
        'usdt_token' => env('HEDERA_USDT_TOKEN'),
        'network' => env('HEDERA_NETWORK', 'mainnet'),
    ],

    'calendarific' => [
        'api_key' => env('CALENDARIFIC_API_KEY'),
    ],

    'coingecko' => [
        'api_key' => env('COINGECKO_API_KEY'),
    ],

    'chainlink' => [
        'node_url' => env('CHAINLINK_NODE_URL'),
    ],

    'binance' => [
        'api_key' => env('BINANCE_API_KEY'),
    ],

];
