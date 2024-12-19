<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PriceFeed extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'provider',          // 'coingecko', 'chainlink', 'binance', etc.
        'asset_id',          // Asset identifier (e.g., 'BTC', 'ETH')
        'quote_currency',    // Quote currency (e.g., 'USD', 'EUR')
        'price',             // Current price
        'volume_24h',        // 24-hour trading volume
        'market_cap',        // Market capitalization
        'change_24h',        // 24-hour price change percentage
        'last_updated',      // Last price update timestamp
        'confidence_score',  // Data reliability score (0-100)
        'status',           // 'active', 'inactive', 'error'
        'metadata'          // Additional provider-specific data
    ];

    protected $casts = [
        'price' => 'decimal:8',
        'volume_24h' => 'decimal:2',
        'market_cap' => 'decimal:2',
        'change_24h' => 'decimal:2',
        'confidence_score' => 'integer',
        'last_updated' => 'datetime',
        'metadata' => 'array'
    ];

    /**
     * Get the latest price update
     */
    public function latestPriceUpdate()
    {
        return $this->hasOne(PriceUpdate::class)->latest();
    }

    /**
     * Get price updates history
     */
    public function priceUpdates()
    {
        return $this->hasMany(PriceUpdate::class);
    }

    /**
     * Check if the price feed is stale
     */
    public function isStale(int $maxAgeMinutes = 5): bool
    {
        return $this->last_updated->diffInMinutes(now()) > $maxAgeMinutes;
    }

    /**
     * Get confidence level description
     */
    public function getConfidenceLevelAttribute(): string
    {
        if ($this->confidence_score >= 90) return 'Very High';
        if ($this->confidence_score >= 75) return 'High';
        if ($this->confidence_score >= 50) return 'Medium';
        if ($this->confidence_score >= 25) return 'Low';
        return 'Very Low';
    }

    /**
     * Format price with appropriate decimal places
     */
    public function getFormattedPriceAttribute(): string
    {
        $price = $this->price;
        
        if ($price >= 1000) {
            return number_format($price, 2);
        } elseif ($price >= 1) {
            return number_format($price, 4);
        } elseif ($price >= 0.01) {
            return number_format($price, 6);
        } else {
            return number_format($price, 8);
        }
    }
}
