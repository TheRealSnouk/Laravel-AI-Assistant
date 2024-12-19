<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cryptocurrency Payment Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for cryptocurrency payments including
    | supported networks, contract addresses, and network settings.
    |
    */

    'networks' => [
        'polygon' => [
            'name' => 'Polygon Network',
            'enabled' => true,
            'rpc_url' => env('POLYGON_RPC_URL'),
            'chain_id' => 137,
            'explorer_url' => 'https://polygonscan.com',
            'native_currency' => [
                'name' => 'MATIC',
                'symbol' => 'MATIC',
                'decimals' => 18,
            ],
            'tokens' => [
                'usdt' => [
                    'name' => 'Tether USD',
                    'symbol' => 'USDT',
                    'decimals' => 6,
                    'contract' => env('POLYGON_USDT_CONTRACT'),
                ],
            ],
        ],
        'ethereum' => [
            'name' => 'Ethereum Network',
            'enabled' => true,
            'rpc_url' => env('ETH_RPC_URL', 'https://mainnet.infura.io/v3/' . env('INFURA_PROJECT_ID')),
            'chain_id' => 1,
            'explorer_url' => 'https://etherscan.io',
            'native_currency' => [
                'name' => 'Ethereum',
                'symbol' => 'ETH',
                'decimals' => 18,
            ],
            'tokens' => [
                'usdt' => [
                    'name' => 'Tether USD',
                    'symbol' => 'USDT',
                    'decimals' => 6,
                    'contract' => env('ETH_USDT_CONTRACT', '0xdAC17F958D2ee523a2206206994597C13D831ec7'),
                ],
            ],
        ],
        'bsc' => [
            'name' => 'BNB Smart Chain',
            'enabled' => true,
            'rpc_url' => env('BSC_RPC_URL', 'https://bsc-mainnet.infura.io/v3/' . env('INFURA_PROJECT_ID')),
            'chain_id' => 56,
            'explorer_url' => 'https://bscscan.com',
            'native_currency' => [
                'name' => 'BNB',
                'symbol' => 'BNB',
                'decimals' => 18,
            ],
            'tokens' => [
                'usdt' => [
                    'name' => 'Tether USD',
                    'symbol' => 'USDT',
                    'decimals' => 18, // Note: BSC USDT uses 18 decimals
                    'contract' => env('BSC_USDT_CONTRACT', '0x55d398326f99059fF775485246999027B3197955'),
                ],
            ],
        ],
        'hedera' => [
            'name' => 'Hedera Network',
            'enabled' => true,
            'network' => env('HEDERA_NETWORK', 'mainnet'),
            'mirror_node' => env('HEDERA_MIRROR_NODE', 'https://mainnet-public.mirrornode.hedera.com'),
            'operator' => [
                'id' => env('HEDERA_OPERATOR_ID'),
                'key' => env('HEDERA_OPERATOR_KEY'),
            ],
            'tokens' => [
                'usdt' => [
                    'name' => 'Hedera USDT',
                    'symbol' => 'USDT',
                    'token_id' => env('HEDERA_USDT_TOKEN_ID'),
                    'decimals' => 6,
                ],
            ],
            'wallets' => [
                'hashpack' => [
                    'name' => 'HashPack',
                    'enabled' => true,
                    'deep_link' => 'hashpack://',
                ],
                'metamask' => [
                    'name' => 'MetaMask',
                    'enabled' => true,
                    'deep_link' => 'metamask://',
                ],
            ],
        ],
        'cosmos' => [
            'name' => 'Cosmos Network',
            'enabled' => true,
            'rpc_url' => env('COSMOS_RPC_URL', 'https://rpc.cosmos.network'),
            'rest_url' => env('COSMOS_REST_URL', 'https://api.cosmos.network'),
            'chain_id' => env('COSMOS_CHAIN_ID', 'cosmoshub-4'),
            'bech32_prefix' => 'cosmos',
            'tokens' => [
                'atom' => [
                    'name' => 'Cosmos',
                    'symbol' => 'ATOM',
                    'decimals' => 6,
                ],
                'usdt' => [
                    'name' => 'Cosmos USDT',
                    'symbol' => 'USDT',
                    'contract' => env('COSMOS_USDT_CONTRACT'),
                    'decimals' => 6,
                ],
            ],
            'wallets' => [
                'keplr' => [
                    'name' => 'Keplr',
                    'enabled' => true,
                    'deep_link' => 'keplr://',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Merchant Wallet Settings
    |--------------------------------------------------------------------------
    |
    | Configure the merchant wallet addresses for receiving payments
    |
    */
    'merchant_addresses' => [
        'polygon' => env('MERCHANT_POLYGON_ADDRESS'),
        'ethereum' => env('MERCHANT_ETH_ADDRESS'),
        'bsc' => env('MERCHANT_BSC_ADDRESS'),
        'hedera' => env('MERCHANT_HEDERA_ID'),  // Hedera account ID
        'cosmos' => env('MERCHANT_COSMOS_ADDRESS'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    */
    'payment' => [
        'confirmation_blocks' => [
            'polygon' => 5,  // Faster confirmations on Polygon
            'ethereum' => 12, // More confirmations for security
            'bsc' => 5,      // BSC has faster blocks
            'hedera' => 1,    // Hedera has fast finality
            'cosmos' => 2,    // Cosmos has fast finality
        ],
        'timeout_minutes' => 30,    // Payment timeout in minutes
        'price_update_interval' => 60, // Price update interval in seconds
        'gas_price_multiplier' => 1.1,  // Multiply estimated gas price by this factor
        'max_gas_price' => [
            'polygon' => '300', // Max gas price in GWEI
            'ethereum' => '150',
            'bsc' => '10',
            'cosmos' => '0.025', // in ATOM
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Keys and External Services
    |--------------------------------------------------------------------------
    */
    'services' => [
        'infura' => [
            'project_id' => env('INFURA_PROJECT_ID'),
        ],
        'coingecko' => [
            'api_url' => 'https://api.coingecko.com/api/v3',
            'supported_tokens' => [
                'ethereum' => 'ethereum',
                'matic-network' => 'polygon-pos',
                'binancecoin' => 'binance-smart-chain',
                'tether' => 'tether',
                'cosmos' => 'cosmos',
                'hedera-hashgraph' => 'hedera-hashgraph',
            ],
        ],
        'block_explorers' => [
            'ethereum' => [
                'api_key' => env('ETHERSCAN_API_KEY'),
                'api_url' => 'https://api.etherscan.io/api',
            ],
            'polygon' => [
                'api_key' => env('POLYGONSCAN_API_KEY'),
                'api_url' => 'https://api.polygonscan.com/api',
            ],
            'bsc' => [
                'api_key' => env('BSCSCAN_API_KEY'),
                'api_url' => 'https://api.bscscan.com/api',
            ],
        ],
        'stripe' => [
            'enabled' => true,
            'key' => env('STRIPE_KEY'),
            'secret' => env('STRIPE_SECRET'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
            'currency' => 'usd',
            'supported_payment_methods' => [
                'card',
                'apple_pay',
                'google_pay',
            ],
        ],

        'paypal' => [
            'enabled' => true,
            'client_id' => env('PAYPAL_CLIENT_ID'),
            'secret' => env('PAYPAL_SECRET'),
            'mode' => env('PAYPAL_MODE', 'sandbox'), // sandbox or live
            'currency' => 'USD',
        ],
    ],

    'traditional_payments' => [
        'enabled' => true,
        'default_currency' => 'USD',
        'supported_currencies' => ['USD', 'EUR', 'GBP', 'JPY', 'AUD', 'CAD'],
        'minimum_amount' => [
            'USD' => 5.00,
            'EUR' => 5.00,
            'GBP' => 4.00,
            'JPY' => 500,
            'AUD' => 7.00,
            'CAD' => 7.00,
        ],
        'stripe_payment_methods' => [
            'card' => [
                'enabled' => true,
                'supported_cards' => ['visa', 'mastercard', 'amex'],
            ],
            'apple_pay' => [
                'enabled' => true,
            ],
            'google_pay' => [
                'enabled' => true,
            ],
        ],
    ],
];
