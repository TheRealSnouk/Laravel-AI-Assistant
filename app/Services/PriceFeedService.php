<?php

namespace App\Services;

use App\Models\PriceFeed;
use App\Models\PriceUpdate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class PriceFeedService
{
    private $coingeckoApiKey;
    private $chainlinkNode;
    private $binanceApiKey;

    public function __construct()
    {
        $this->coingeckoApiKey = config('services.coingecko.api_key');
        $this->chainlinkNode = config('services.chainlink.node_url');
        $this->binanceApiKey = config('services.binance.api_key');
    }

    /**
     * Update price feed from multiple sources
     */
    public function updatePriceFeed(PriceFeed $priceFeed): PriceFeed
    {
        try {
            $prices = [];
            $totalConfidence = 0;

            // Get prices from different sources based on provider
            switch ($priceFeed->provider) {
                case 'aggregated':
                    // Collect prices from multiple sources
                    $prices = array_merge(
                        $this->getCoingeckoPrice($priceFeed),
                        $this->getChainlinkPrice($priceFeed),
                        $this->getBinancePrice($priceFeed)
                    );
                    break;

                case 'coingecko':
                    $prices[] = $this->getCoingeckoPrice($priceFeed);
                    break;

                case 'chainlink':
                    $prices[] = $this->getChainlinkPrice($priceFeed);
                    break;

                case 'binance':
                    $prices[] = $this->getBinancePrice($priceFeed);
                    break;
            }

            // Calculate weighted average price and confidence
            $weightedPrice = 0;
            foreach ($prices as $price) {
                $weightedPrice += $price['price'] * $price['confidence'];
                $totalConfidence += $price['confidence'];
            }

            $finalPrice = $totalConfidence > 0 ? $weightedPrice / $totalConfidence : null;
            $averageConfidence = $totalConfidence > 0 ? $totalConfidence / count($prices) : 0;

            if ($finalPrice === null) {
                throw new Exception("No valid price data available");
            }

            // Create price update record
            PriceUpdate::create([
                'price_feed_id' => $priceFeed->id,
                'price' => $finalPrice,
                'confidence_score' => $averageConfidence,
                'timestamp' => now(),
                'metadata' => ['sources' => $prices]
            ]);

            // Update price feed
            $priceFeed->update([
                'price' => $finalPrice,
                'confidence_score' => $averageConfidence,
                'last_updated' => now(),
                'status' => 'active'
            ]);

            Log::info('Price feed updated successfully', [
                'feed_id' => $priceFeed->id,
                'asset' => $priceFeed->asset_id,
                'price' => $finalPrice,
                'confidence' => $averageConfidence
            ]);

            return $priceFeed->fresh();

        } catch (Exception $e) {
            Log::error('Failed to update price feed', [
                'feed_id' => $priceFeed->id,
                'error' => $e->getMessage()
            ]);

            $priceFeed->update([
                'status' => 'error',
                'metadata' => array_merge(
                    $priceFeed->metadata ?? [],
                    ['last_error' => $e->getMessage()]
                )
            ]);

            throw $e;
        }
    }

    /**
     * Get price from CoinGecko
     */
    private function getCoingeckoPrice(PriceFeed $priceFeed): array
    {
        $response = Http::withHeaders([
            'x-cg-pro-api-key' => $this->coingeckoApiKey
        ])->get('https://pro-api.coingecko.com/api/v3/simple/price', [
            'ids' => $priceFeed->asset_id,
            'vs_currencies' => strtolower($priceFeed->quote_currency),
            'include_24hr_vol' => true,
            'include_24hr_change' => true,
            'include_market_cap' => true
        ]);

        if (!$response->successful()) {
            throw new Exception("CoinGecko API error: " . $response->body());
        }

        $data = $response->json();
        $assetData = $data[$priceFeed->asset_id] ?? null;

        if (!$assetData) {
            throw new Exception("No data available for asset: " . $priceFeed->asset_id);
        }

        return [
            'source' => 'coingecko',
            'price' => $assetData[strtolower($priceFeed->quote_currency)],
            'confidence' => 80, // Base confidence score for CoinGecko
            'metadata' => $assetData
        ];
    }

    /**
     * Get price from Chainlink
     */
    private function getChainlinkPrice(PriceFeed $priceFeed): array
    {
        // Implementation depends on specific Chainlink node setup
        // This is a placeholder for the actual implementation
        return [
            'source' => 'chainlink',
            'price' => 0,
            'confidence' => 90, // Chainlink typically has high reliability
            'metadata' => []
        ];
    }

    /**
     * Get price from Binance
     */
    private function getBinancePrice(PriceFeed $priceFeed): array
    {
        $symbol = $priceFeed->asset_id . $priceFeed->quote_currency;
        
        $response = Http::withHeaders([
            'X-MBX-APIKEY' => $this->binanceApiKey
        ])->get('https://api.binance.com/api/v3/ticker/24hr', [
            'symbol' => $symbol
        ]);

        if (!$response->successful()) {
            throw new Exception("Binance API error: " . $response->body());
        }

        $data = $response->json();

        return [
            'source' => 'binance',
            'price' => (float)$data['lastPrice'],
            'confidence' => 85, // Base confidence score for Binance
            'metadata' => [
                'volume' => $data['volume'],
                'change_24h' => $data['priceChangePercent']
            ]
        ];
    }

    /**
     * Get current price for an asset
     */
    public function getCurrentPrice(string $assetId, string $quoteCurrency = 'USD'): ?float
    {
        $priceFeed = PriceFeed::where([
            'asset_id' => $assetId,
            'quote_currency' => $quoteCurrency,
            'status' => 'active'
        ])->first();

        if (!$priceFeed || $priceFeed->isStale()) {
            $this->updatePriceFeed($priceFeed);
        }

        return $priceFeed->price ?? null;
    }

    /**
     * Create a new price feed
     */
    public function createPriceFeed(array $data): PriceFeed
    {
        $priceFeed = PriceFeed::create(array_merge($data, [
            'status' => 'active',
            'last_updated' => now()
        ]));

        // Initial price update
        $this->updatePriceFeed($priceFeed);

        return $priceFeed;
    }

    /**
     * Get price history for an asset
     */
    public function getPriceHistory(
        string $assetId, 
        string $quoteCurrency = 'USD',
        int $days = 30
    ): array {
        $priceFeed = PriceFeed::where([
            'asset_id' => $assetId,
            'quote_currency' => $quoteCurrency
        ])->first();

        if (!$priceFeed) {
            return [];
        }

        return $priceFeed->priceUpdates()
            ->where('timestamp', '>=', now()->subDays($days))
            ->orderBy('timestamp')
            ->get()
            ->map(function ($update) {
                return [
                    'timestamp' => $update->timestamp,
                    'price' => $update->price,
                    'confidence' => $update->confidence_score
                ];
            })
            ->toArray();
    }
}
