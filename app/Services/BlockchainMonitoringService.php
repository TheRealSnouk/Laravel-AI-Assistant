<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use App\Notifications\BlockchainHealthAlert;
use Exception;
use Carbon\Carbon;

class BlockchainMonitoringService
{
    private const CACHE_PREFIX = 'blockchain_health_';
    private const ALERT_THRESHOLD = 3; // Number of failures before alerting
    private const CHECK_INTERVAL = 300; // 5 minutes in seconds

    public function __construct(
        private BlockchainConfigService $configService
    ) {}

    /**
     * Run all health checks
     */
    public function runHealthCheck(): array
    {
        $results = [];
        $alerts = [];

        foreach ($this->configService::SUPPORTED_NETWORKS as $network) {
            try {
                $networkHealth = $this->checkNetworkHealth($network);
                $results[$network] = $networkHealth;

                if (!$networkHealth['healthy']) {
                    $this->processUnhealthyNetwork($network, $networkHealth['issues']);
                    $alerts[] = [
                        'network' => $network,
                        'issues' => $networkHealth['issues']
                    ];
                }
            } catch (Exception $e) {
                Log::error("Health check failed for {$network}: " . $e->getMessage());
                $results[$network] = [
                    'healthy' => false,
                    'issues' => [$e->getMessage()]
                ];
            }
        }

        $this->sendAlerts($alerts);
        return $results;
    }

    /**
     * Check health of specific network
     */
    public function checkNetworkHealth(string $network): array
    {
        $issues = [];
        
        // Get network configuration
        $config = $this->configService->getNetworkConfig($network);

        // Check RPC availability
        if (isset($config['rpc_url'])) {
            $rpcHealth = $this->checkRpcHealth($network, $config['rpc_url']);
            if (!$rpcHealth['healthy']) {
                $issues = array_merge($issues, $rpcHealth['issues']);
            }
        }

        // Network-specific checks
        $networkIssues = match($network) {
            'hedera' => $this->checkHederaHealth($config),
            'cosmos' => $this->checkCosmosHealth($config),
            default => $this->checkEVMHealth($network, $config)
        };
        
        $issues = array_merge($issues, $networkIssues);

        // Check gas prices
        if ($gasIssues = $this->checkGasPrices($network)) {
            $issues = array_merge($issues, $gasIssues);
        }

        // Check balance thresholds
        if ($balanceIssues = $this->checkBalanceThresholds($network)) {
            $issues = array_merge($issues, $balanceIssues);
        }

        return [
            'healthy' => empty($issues),
            'issues' => $issues,
            'timestamp' => now()
        ];
    }

    /**
     * Check RPC endpoint health
     */
    private function checkRpcHealth(string $network, string $rpcUrl): array
    {
        $issues = [];
        
        try {
            $response = Http::timeout(5)->post($rpcUrl, [
                'jsonrpc' => '2.0',
                'method' => 'net_version',
                'params' => [],
                'id' => 1
            ]);

            if (!$response->successful()) {
                $issues[] = "{$network} RPC endpoint returned status: " . $response->status();
            }

            // Check response time
            $responseTime = $response->handlerStats()['total_time'] ?? 0;
            if ($responseTime > 2) { // More than 2 seconds
                $issues[] = "{$network} RPC high latency: {$responseTime}s";
            }
        } catch (Exception $e) {
            $issues[] = "{$network} RPC connection failed: " . $e->getMessage();
        }

        return [
            'healthy' => empty($issues),
            'issues' => $issues
        ];
    }

    /**
     * Check Hedera-specific health
     */
    private function checkHederaHealth(array $config): array
    {
        $issues = [];

        try {
            // Check mirror node
            $mirrorUrl = "https://{$config['mirror_node']}/api/v1/network/nodes";
            $response = Http::timeout(5)->get($mirrorUrl);
            
            if (!$response->successful()) {
                $issues[] = "Hedera mirror node unavailable: " . $response->status();
            }

            // Check consensus node
            $consensusUrl = "https://{$config['consensus_node']}/api/v1/status";
            $response = Http::timeout(5)->get($consensusUrl);
            
            if (!$response->successful()) {
                $issues[] = "Hedera consensus node unavailable: " . $response->status();
            }
        } catch (Exception $e) {
            $issues[] = "Hedera node check failed: " . $e->getMessage();
        }

        return $issues;
    }

    /**
     * Check Cosmos-specific health
     */
    private function checkCosmosHealth(array $config): array
    {
        $issues = [];

        try {
            // Check REST API
            $response = Http::timeout(5)->get($config['rest_url'] . '/cosmos/base/tendermint/v1beta1/syncing');
            
            if (!$response->successful()) {
                $issues[] = "Cosmos REST API unavailable: " . $response->status();
            }

            // Check node sync status
            $syncData = $response->json();
            if ($syncData['syncing'] ?? false) {
                $issues[] = "Cosmos node is still syncing";
            }
        } catch (Exception $e) {
            $issues[] = "Cosmos health check failed: " . $e->getMessage();
        }

        return $issues;
    }

    /**
     * Check EVM-based network health
     */
    private function checkEVMHealth(string $network, array $config): array
    {
        $issues = [];

        try {
            $response = Http::timeout(5)->post($config['rpc_url'], [
                'jsonrpc' => '2.0',
                'method' => 'eth_blockNumber',
                'params' => [],
                'id' => 1
            ]);

            if ($response->successful()) {
                // Check if block number is recent
                $blockNumber = hexdec($response->json()['result']);
                $lastBlockTime = $this->getLastBlockTime($network);
                
                if ($lastBlockTime && Carbon::now()->diffInMinutes($lastBlockTime) > 5) {
                    $issues[] = "{$network} chain might be stalled. No new blocks in 5 minutes.";
                }

                Cache::put("{$network}_last_block", $blockNumber, now()->addMinutes(10));
            }
        } catch (Exception $e) {
            $issues[] = "{$network} EVM health check failed: " . $e->getMessage();
        }

        return $issues;
    }

    /**
     * Check gas prices against thresholds
     */
    private function checkGasPrices(string $network): array
    {
        $issues = [];
        
        try {
            $gasConfig = $this->configService->getGasConfig($network);
            $currentGasPrice = $this->getCurrentGasPrice($network);

            if ($currentGasPrice > $gasConfig['max_gas_price']) {
                $issues[] = "{$network} gas price ({$currentGasPrice} {$gasConfig['price_unit']}) exceeds threshold ({$gasConfig['max_gas_price']} {$gasConfig['price_unit']})";
            }
        } catch (Exception $e) {
            $issues[] = "{$network} gas price check failed: " . $e->getMessage();
        }

        return $issues;
    }

    /**
     * Check merchant address balances
     */
    private function checkBalanceThresholds(string $network): array
    {
        $issues = [];
        
        try {
            $merchantAddress = $this->configService->getMerchantAddress($network);
            $balance = $this->getAddressBalance($network, $merchantAddress);
            $minBalance = $this->getMinimumRequiredBalance($network);

            if ($balance < $minBalance) {
                $issues[] = "{$network} merchant address balance ({$balance}) below minimum threshold ({$minBalance})";
            }
        } catch (Exception $e) {
            $issues[] = "{$network} balance check failed: " . $e->getMessage();
        }

        return $issues;
    }

    /**
     * Process unhealthy network status
     */
    private function processUnhealthyNetwork(string $network, array $issues): void
    {
        $cacheKey = self::CACHE_PREFIX . $network . '_failures';
        $failures = Cache::get($cacheKey, 0) + 1;
        Cache::put($cacheKey, $failures, now()->addHours(1));

        if ($failures >= self::ALERT_THRESHOLD) {
            Log::error("Network {$network} health check failed {$failures} times", [
                'issues' => $issues
            ]);
            Cache::put($cacheKey, 0, now()->addHours(1));
        }
    }

    /**
     * Send alerts for issues
     */
    private function sendAlerts(array $alerts): void
    {
        if (empty($alerts)) {
            return;
        }

        // Log alerts
        Log::warning('Blockchain health alerts detected', ['alerts' => $alerts]);

        // Send notifications
        try {
            Notification::route('mail', config('blockchain.alert_email'))
                ->notify(new BlockchainHealthAlert($alerts));
        } catch (Exception $e) {
            Log::error('Failed to send blockchain health alert: ' . $e->getMessage());
        }
    }

    /**
     * Get current gas price for network
     */
    private function getCurrentGasPrice(string $network): float
    {
        // Implementation varies by network
        return match($network) {
            'ethereum' => $this->getEthereumGasPrice(),
            'bsc' => $this->getBscGasPrice(),
            'polygon' => $this->getPolygonGasPrice(),
            default => 0
        };
    }

    /**
     * Get minimum required balance for network
     */
    private function getMinimumRequiredBalance(string $network): float
    {
        return match($network) {
            'ethereum' => 0.1, // 0.1 ETH
            'bsc' => 0.1, // 0.1 BNB
            'polygon' => 10, // 10 MATIC
            'cosmos' => 1, // 1 ATOM
            'hedera' => 100, // 100 HBAR
            default => 0
        };
    }

    /**
     * Get last block time for network
     */
    private function getLastBlockTime(string $network): ?Carbon
    {
        $lastBlock = Cache::get("{$network}_last_block");
        return $lastBlock ? Carbon::createFromTimestamp($lastBlock) : null;
    }

    /**
     * Get address balance
     */
    private function getAddressBalance(string $network, string $address): float
    {
        // Implementation varies by network
        // This is a placeholder that should be implemented based on each network's specifics
        return 0.0;
    }

    // Network-specific gas price getters
    private function getEthereumGasPrice(): float
    {
        $response = Http::get('https://api.etherscan.io/api', [
            'module' => 'gastracker',
            'action' => 'gasoracle',
            'apikey' => config('services.etherscan.key')
        ]);

        if ($response->successful()) {
            return $response->json()['result']['SafeGasPrice'];
        }

        throw new Exception('Failed to fetch Ethereum gas price');
    }

    private function getBscGasPrice(): float
    {
        $response = Http::get('https://api.bscscan.com/api', [
            'module' => 'gastracker',
            'action' => 'gasoracle',
            'apikey' => config('services.bscscan.key')
        ]);

        if ($response->successful()) {
            return $response->json()['result']['SafeGasPrice'];
        }

        throw new Exception('Failed to fetch BSC gas price');
    }

    private function getPolygonGasPrice(): float
    {
        $response = Http::get('https://api.polygonscan.com/api', [
            'module' => 'gastracker',
            'action' => 'gasoracle',
            'apikey' => config('services.polygonscan.key')
        ]);

        if ($response->successful()) {
            return $response->json()['result']['SafeGasPrice'];
        }

        throw new Exception('Failed to fetch Polygon gas price');
    }
}
