<?php

namespace App\Http\Controllers;

use App\Services\BlockchainMonitoringService;
use App\Services\BlockchainConfigService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class BlockchainHealthController extends Controller
{
    public function __construct(
        private BlockchainMonitoringService $monitoringService,
        private BlockchainConfigService $configService
    ) {}

    /**
     * Display the blockchain health dashboard
     */
    public function dashboard()
    {
        $networks = $this->configService::SUPPORTED_NETWORKS;
        $healthData = [];
        $historicalData = [];
        $alerts = [];

        foreach ($networks as $network) {
            // Current health status
            $healthData[$network] = $this->monitoringService->checkNetworkHealth($network);
            
            // Get historical data from cache
            $historicalData[$network] = $this->getHistoricalData($network);
            
            // Get recent alerts
            $alerts = array_merge($alerts, $this->getRecentAlerts($network));
        }

        return view('blockchain.health.dashboard', [
            'networks' => $networks,
            'healthData' => $healthData,
            'historicalData' => $historicalData,
            'alerts' => collect($alerts)->sortByDesc('timestamp')->take(10),
            'lastUpdated' => now()
        ]);
    }

    /**
     * Get detailed network status
     */
    public function networkDetails(Request $request, string $network)
    {
        $health = $this->monitoringService->checkNetworkHealth($network);
        $config = $this->configService->getNetworkConfig($network);
        $gasConfig = $this->configService->getGasConfig($network);
        $historical = $this->getHistoricalData($network);

        return view('blockchain.health.network-details', [
            'network' => $network,
            'health' => $health,
            'config' => $config,
            'gasConfig' => $gasConfig,
            'historical' => $historical,
            'lastUpdated' => now()
        ]);
    }

    /**
     * API endpoint to refresh network status
     */
    public function refreshStatus(Request $request, string $network)
    {
        $health = $this->monitoringService->checkNetworkHealth($network);
        
        return response()->json([
            'status' => $health['healthy'] ? 'healthy' : 'unhealthy',
            'issues' => $health['issues'],
            'lastUpdated' => now()->toIso8601String()
        ]);
    }

    /**
     * Get historical health data for network
     */
    private function getHistoricalData(string $network): array
    {
        $cacheKey = "health_history_{$network}";
        $history = Cache::get($cacheKey, []);
        
        // Format for chart display
        return collect($history)
            ->map(fn($entry) => [
                'timestamp' => Carbon::parse($entry['timestamp'])->format('Y-m-d H:i:s'),
                'status' => $entry['healthy'] ? 1 : 0,
                'responseTime' => $entry['responseTime'] ?? null,
                'gasPrice' => $entry['gasPrice'] ?? null
            ])
            ->values()
            ->toArray();
    }

    /**
     * Get recent alerts for network
     */
    private function getRecentAlerts(string $network): array
    {
        return Cache::get("recent_alerts_{$network}", []);
    }
}
