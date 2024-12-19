<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Your Infura credentials
$projectId = env('INFURA_PROJECT_ID');
$projectSecret = env('INFURA_API_SECRET');

// Test endpoints
$networks = [
    'mainnet' => 'https://mainnet.infura.io/v3/',
    'polygon' => 'https://polygon-mainnet.infura.io/v3/',
    'bsc' => 'https://bsc-mainnet.infura.io/v3/'
];

echo "🔍 Testing Infura Connection...\n\n";

foreach ($networks as $network => $baseUrl) {
    try {
        $response = Http::post($baseUrl . $projectId, [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'eth_blockNumber',
            'params' => []
        ]);

        $result = $response->json();
        
        if (isset($result['result'])) {
            $blockNumber = hexdec($result['result']);
            echo "✅ {$network}: Connected successfully! Current block: {$blockNumber}\n";
        } else {
            echo "❌ {$network}: Connected but received unexpected response\n";
        }
    } catch (\Exception $e) {
        echo "❌ {$network}: Connection failed - " . $e->getMessage() . "\n";
    }
}

// Test USDT contract interaction
$usdtAddresses = [
    'ethereum' => '0xdAC17F958D2ee523a2206206994597C13D831ec7',
    'bsc' => '0x55d398326f99059fF775485246999027B3197955',
    'polygon' => '0xc2132D05D31c914a87C6611C10748AEb04B58e8F'
];

echo "\n📊 Testing USDT Contract Access...\n\n";

// Function to get token name (ERC20 standard)
$tokenNameABI = '0x06fdde03'; // keccak256('name()')

foreach ($usdtAddresses as $network => $address) {
    try {
        $response = Http::post($networks[strtolower($network)] . $projectId, [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'eth_call',
            'params' => [
                [
                    'to' => $address,
                    'data' => $tokenNameABI
                ],
                'latest'
            ]
        ]);

        $result = $response->json();
        
        if (isset($result['result'])) {
            echo "✅ {$network} USDT Contract: Accessible\n";
        } else {
            echo "❌ {$network} USDT Contract: Not accessible\n";
        }
    } catch (\Exception $e) {
        echo "❌ {$network} USDT Contract: Error - " . $e->getMessage() . "\n";
    }
}
