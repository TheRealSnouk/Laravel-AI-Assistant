<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class TimeBasedRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'modifier_id',
        'name',
        'description',
        'rule_type',          // 'peak_pricing', 'seasonal', 'time_window', 'recurring'
        'start_date',         // For seasonal rules
        'end_date',          // For seasonal rules
        'days_of_week',      // JSON array [0-6] for specific days
        'start_time',        // For daily time windows
        'end_time',          // For daily time windows
        'timezone',          // User's timezone
        'price_multiplier',  // Multiplier for base price
        'fixed_adjustment',  // Fixed amount adjustment
        'priority',          // Higher priority rules take precedence
        'conditions',        // JSON encoded additional conditions
        'status'            // 'active', 'inactive'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'days_of_week' => 'array',
        'conditions' => 'array',
        'price_multiplier' => 'float',
        'fixed_adjustment' => 'decimal:2'
    ];

    public function modifier()
    {
        return $this->belongsTo(SubscriptionModifier::class);
    }

    /**
     * Check if the rule is currently active based on time conditions
     */
    public function isActiveNow(): bool
    {
        $now = Carbon::now($this->timezone);

        // Check if rule is active
        if ($this->status !== 'active') {
            return false;
        }

        // Check date range for seasonal rules
        if ($this->start_date && $this->end_date) {
            if ($now->lt($this->start_date) || $now->gt($this->end_date)) {
                return false;
            }
        }

        // Check days of week
        if ($this->days_of_week && !in_array($now->dayOfWeek, $this->days_of_week)) {
            return false;
        }

        // Check time window
        if ($this->start_time && $this->end_time) {
            $startTime = Carbon::parse($this->start_time, $this->timezone);
            $endTime = Carbon::parse($this->end_time, $this->timezone);
            
            $currentTime = $now->copy()->setTimeFrom($now);
            
            // Handle overnight windows (e.g., 22:00 - 06:00)
            if ($startTime->gt($endTime)) {
                return $currentTime->gte($startTime) || $currentTime->lte($endTime);
            }
            
            return $currentTime->between($startTime, $endTime);
        }

        return true;
    }

    /**
     * Calculate price adjustment based on the rule
     */
    public function calculateAdjustment(float $basePrice): float
    {
        if (!$this->isActiveNow()) {
            return $basePrice;
        }

        $adjustedPrice = $basePrice;

        // Apply multiplier if set
        if ($this->price_multiplier) {
            $adjustedPrice *= $this->price_multiplier;
        }

        // Apply fixed adjustment if set
        if ($this->fixed_adjustment) {
            $adjustedPrice += $this->fixed_adjustment;
        }

        return max(0, $adjustedPrice); // Ensure price doesn't go negative
    }

    /**
     * Get human-readable schedule description
     */
    public function getScheduleDescription(): string
    {
        $description = [];

        if ($this->start_date && $this->end_date) {
            $description[] = "Valid from " . $this->start_date->format('M d, Y') . 
                           " to " . $this->end_date->format('M d, Y');
        }

        if ($this->days_of_week) {
            $days = collect($this->days_of_week)->map(function($day) {
                return Carbon::create()->dayOfWeek($day)->format('l');
            })->join(', ', ' and ');
            $description[] = "Active on $days";
        }

        if ($this->start_time && $this->end_time) {
            $description[] = "Between " . 
                           Carbon::parse($this->start_time)->format('g:i A') . 
                           " and " . 
                           Carbon::parse($this->end_time)->format('g:i A') . 
                           " ({$this->timezone})";
        }

        return implode(", ", $description);
    }
}
