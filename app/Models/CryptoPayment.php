<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CryptoPayment extends Model
{
    protected $fillable = [
        'user_id',
        'reference',
        'network',
        'token_id',
        'amount',
        'currency',
        'recipient_address',
        'sender_address',
        'transaction_hash',
        'memo',
        'status',
        'payment_details',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:8',
        'payment_details' => 'array',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'completed' => 'green',
            'pending' => 'yellow',
            'failed' => 'red',
            default => 'gray'
        };
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount, 8) . ' ' . strtoupper($this->currency);
    }

    public function getNetworkNameAttribute(): string
    {
        return match($this->network) {
            'hedera' => 'Hedera',
            'cosmos' => 'Cosmos',
            default => ucfirst($this->network)
        };
    }

    public function getExplorerUrlAttribute(): string
    {
        return match($this->network) {
            'hedera' => "https://hashscan.io/mainnet/transaction/{$this->transaction_hash}",
            'cosmos' => "https://www.mintscan.io/cosmos/txs/{$this->transaction_hash}",
            default => '#'
        };
    }
}
