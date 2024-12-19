<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class BlockchainConfigService
{
    private const CACHE_PREFIX = 'blockchain_config_';
    private const CACHE_TTL = 300; // 5 minutes

    private const SUPPORTED_NETWORKS = [
        'ethereum',
        'bsc',
        'polygon',
        'cosmos',
        'hedera'
    ];

    /**
     * Get configuration for a specific network
     */
    public function getNetworkConfig(string $network): array
    {
        $network = strtolower($network);
        
        if (!in_array($network, self::SUPPORTED_NETWORKS)) {
            throw new Exception("Unsupported network: {$network}");
        }

        return Cache::remember(
            self::CACHE_PREFIX . $network,
            self::CACHE_TTL,
            fn() => $this->loadNetworkConfig($network)
        );
    }

    /**
     * Get merchant address for a specific network
     */
    public function getMerchantAddress(string $network): string
    {
        $config = $this->getNetworkConfig($network);
        return $config['merchant_address'] ?? '';
    }

    /**
     * Get RPC URL with fallback support
     */
    public function getRpcUrl(string $network): string
    {
        $config = $this->getNetworkConfig($network);
        
        // Try primary RPC
        if ($this->isRpcAvailable($config['rpc_url'])) {
            return $config['rpc_url'];
        }

        // Try fallback RPC
        if (isset($config['fallback_rpc']) && $this->isRpcAvailable($config['fallback_rpc'])) {
            Log::warning("Using fallback RPC for {$network}");
            return $config['fallback_rpc'];
        }

        throw new Exception("No available RPC endpoint for {$network}");
    }

    /**
     * Get gas configuration for a network
     */
    public function getGasConfig(string $network): array
    {
        $config = $this->getNetworkConfig($network);
        return [
            'gas_limit' => $config['gas_limit'],
            'max_gas_price' => $config['max_gas_price'],
            'price_unit' => $config['gas_price_unit']
        ];
    }

    /**
     * Get transaction configuration
     */
    public function getTransactionConfig(): array
    {
        return [
            'timeout' => (int)env('MAX_TRANSACTION_TIMEOUT', 60),
            'confirmation_blocks' => (int)env('TRANSACTION_CONFIRMATION_BLOCKS', 12),
            'retry_attempts' => (int)env('RETRY_ATTEMPTS', 3),
            'webhook_retry_delay' => (int)env('WEBHOOK_RETRY_DELAY', 5)
        ];
    }

    /**
     * Load network specific configuration
     */
    private function loadNetworkConfig(string $network): array
    {
        return match($network) {
            'ethereum' => [
                'name' => 'Ethereum',
                'rpc_url' => env('ETH_RPC_URL'),
                'fallback_rpc' => env('ETH_FALLBACK_RPC'),
                'merchant_address' => env('MERCHANT_ETH_ADDRESS'),
                'usdt_contract' => env('ETH_USDT_CONTRACT'),
                'gas_limit' => (int)env('ETH_GAS_LIMIT', 21000),
                'max_gas_price' => (float)env('ETH_MAX_GAS_PRICE', 100),
                'gas_price_unit' => 'Gwei',
                'scan_api_key' => env('ETHERSCAN_API_KEY'),
                'network_id' => 1
            ],
            'bsc' => [
                'name' => 'Binance Smart Chain',
                'rpc_url' => env('BSC_RPC_URL'),
                'fallback_rpc' => env('BSC_FALLBACK_RPC'),
                'merchant_address' => env('MERCHANT_BSC_ADDRESS'),
                'usdt_contract' => env('BSC_USDT_CONTRACT'),
                'gas_limit' => (int)env('BSC_GAS_LIMIT', 21000),
                'max_gas_price' => (float)env('BSC_MAX_GAS_PRICE', 10),
                'gas_price_unit' => 'Gwei',
                'scan_api_key' => env('BSCSCAN_API_KEY'),
                'network_id' => 56
            ],
            'polygon' => [
                'name' => 'Polygon',
                'rpc_url' => env('POLYGON_RPC_URL'),
                'fallback_rpc' => env('POLYGON_FALLBACK_RPC'),
                'merchant_address' => env('MERCHANT_POLYGON_ADDRESS'),
                'usdt_contract' => env('POLYGON_USDT_CONTRACT'),
                'gas_limit' => (int)env('POLYGON_GAS_LIMIT', 21000),
                'max_gas_price' => (float)env('POLYGON_MAX_GAS_PRICE', 100),
                'gas_price_unit' => 'Gwei',
                'scan_api_key' => env('POLYGONSCAN_API_KEY'),
                'network_id' => 137
            ],
            'cosmos' => [
                'name' => 'Cosmos',
                'rpc_url' => env('COSMOS_RPC_URL'),
                'rest_url' => env('COSMOS_REST_URL'),
                'fallback_rpc' => env('COSMOS_FALLBACK_RPC'),
                'merchant_address' => env('MERCHANT_COSMOS_ADDRESS'),
                'usdt_contract' => env('COSMOS_USDT_CONTRACT'),
                'gas_limit' => (int)env('COSMOS_GAS_LIMIT', 200000),
                'max_gas_price' => (float)env('COSMOS_GAS_PRICE', 0.025),
                'gas_price_unit' => 'ATOM',
                'chain_id' => env('COSMOS_CHAIN_ID', 'cosmoshub-4')
            ],
            'hedera' => [
                'name' => 'Hedera',
                'network' => env('HEDERA_NETWORK', 'mainnet'),
                'operator_id' => env('HEDERA_OPERATOR_ID'),
                'operator_key' => env('HEDERA_OPERATOR_KEY'),
                'merchant_id' => env('MERCHANT_HEDERA_ID'),
                'usdt_token' => env('HEDERA_USDT_TOKEN'),
                'mirror_node' => env('HEDERA_MIRROR_NODE', 'mainnet-public.mirrornode.hedera.com'),
                'consensus_node' => env('HEDERA_CONSENSUS_NODE', 'mainnet.consensus.hedera.com'),
                'fallback_mirror' => env('HEDERA_FALLBACK_MIRROR'),
                'gas_limit' => (int)env('HEDERA_GAS_LIMIT', 1000000),
                'max_transaction_fee' => (float)env('HEDERA_MAX_TRANSACTION_FEE', 5.0)
            ],
            default => throw new Exception("Unsupported network configuration: {$network}")
        };
    }

    /**
     * Check if RPC endpoint is available
     */
    private function isRpcAvailable(string $url): bool
    {
        try {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_exec($ch);
            $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            return $responseCode >= 200 && $responseCode < 300;
        } catch (Exception $e) {
            Log::error("RPC availability check failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate all network configurations
     */
    public function validateConfigurations(): array
    {
        $issues = [];
        
        foreach (self::SUPPORTED_NETWORKS as $network) {
            try {
                $config = $this->loadNetworkConfig($network);
                
                // Check required fields
                $requiredFields = $this->getRequiredFields($network);
                foreach ($requiredFields as $field) {
                    if (empty($config[$field])) {
                        $issues[] = "{$network}: Missing required field '{$field}'";
                    }
                }
                
                // Check RPC availability
                if (isset($config['rpc_url']) && !$this->isRpcAvailable($config['rpc_url'])) {
                    $issues[] = "{$network}: Primary RPC endpoint not available";
                }
            } catch (Exception $e) {
                $issues[] = "{$network}: " . $e->getMessage();
            }
        }
        
        return $issues;
    }

    /**
     * Get required fields for each network
     */
    private function getRequiredFields(string $network): array
    {
        $common = ['merchant_address'];
        
        return match($network) {
            'ethereum', 'bsc', 'polygon' => array_merge($common, [
                'rpc_url',
                'usdt_contract',
                'gas_limit',
                'max_gas_price'
            ]),
            'cosmos' => array_merge($common, [
                'rpc_url',
                'rest_url',
                'usdt_contract',
                'gas_limit',
                'max_gas_price',
                'chain_id'
            ]),
            'hedera' => [
                'network',
                'operator_id',
                'operator_key',
                'merchant_id',
                'usdt_token',
                'mirror_node',
                'consensus_node'
            ],
            default => $common
        };
    }
}
