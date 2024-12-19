<?php

// Your Infura credentials - replace these with your actual values
$projectId = '4c7b35f5778344edbcf5063c06a9fdd7'; // Your Project ID from the dashboard
$networks = [
    'mainnet' => 'https://mainnet.infura.io/v3/',
    'polygon' => 'https://polygon-mainnet.infura.io/v3/',
    'bsc' => 'https://bsc-mainnet.infura.io/v3/'
];

echo "🔍 Testing Infura Connection...\n\n";

foreach ($networks as $network => $baseUrl) {
    $url = $baseUrl . $projectId;
    
    $data = json_encode([
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'eth_blockNumber',
        'params' => []
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo "❌ {$network}: Connection failed - {$error}\n";
        continue;
    }

    $result = json_decode($response, true);
    
    if (isset($result['result'])) {
        $blockNumber = hexdec($result['result']);
        echo "✅ {$network}: Connected successfully! Current block: {$blockNumber}\n";
    } else {
        echo "❌ {$network}: Connected but received unexpected response: " . $response . "\n";
    }
}

// Test USDT contract interaction
$usdtAddresses = [
    'ethereum' => '0xdAC17F958D2ee523a2206206994597C13D831ec7',
    'bsc' => '0x55d398326f99059fF775485246999027B3197955',
    'polygon' => '0xc2132D05D31c914a87C6611C10748AEb04B58e8F'
];

echo "\n📊 Testing USDT Contract Access...\n\n";

$tokenNameABI = '0x06fdde03'; // keccak256('name()')

foreach ($usdtAddresses as $network => $address) {
    $url = $networks[strtolower($network)] . $projectId;
    
    $data = json_encode([
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

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo "❌ {$network} USDT Contract: Error - {$error}\n";
        continue;
    }

    $result = json_decode($response, true);
    
    if (isset($result['result'])) {
        echo "✅ {$network} USDT Contract: Accessible\n";
    } else {
        echo "❌ {$network} USDT Contract: Not accessible - " . $response . "\n";
    }
}
