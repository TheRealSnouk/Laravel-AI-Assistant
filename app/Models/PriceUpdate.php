<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PriceUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'price_feed_id',
        'price',
        'source',           // Specific source within provider
        'confidence_score',
        'timestamp',
        'metadata'
    ];

    protected $casts = [
        'price' => 'decimal:8',
        'confidence_score' => 'integer',
        'timestamp' => 'datetime',
        'metadata' => 'array'
    ];

    public function priceFeed()
    {
        return $this->belongsTo(PriceFeed::class);
    }
}
