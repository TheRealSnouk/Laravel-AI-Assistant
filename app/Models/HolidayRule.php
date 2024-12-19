<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class HolidayRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'modifier_id',
        'name',
        'description',
        'country_code',        // ISO country code
        'holiday_types',       // JSON array of holiday types (public, bank, religious, etc.)
        'specific_holidays',   // JSON array of specific holiday names to include
        'excluded_holidays',   // JSON array of specific holiday names to exclude
        'price_multiplier',    // Multiplier for base price
        'fixed_adjustment',    // Fixed amount adjustment
        'days_before',         // Apply rule X days before holiday
        'days_after',         // Apply rule X days after holiday
        'priority',           // Higher priority rules take precedence
        'status',            // active, inactive
        'metadata'           // JSON encoded additional data
    ];

    protected $casts = [
        'holiday_types' => 'array',
        'specific_holidays' => 'array',
        'excluded_holidays' => 'array',
        'price_multiplier' => 'float',
        'fixed_adjustment' => 'decimal:2',
        'days_before' => 'integer',
        'days_after' => 'integer',
        'metadata' => 'array'
    ];

    public function modifier()
    {
        return $this->belongsTo(SubscriptionModifier::class);
    }

    /**
     * Check if today is within the holiday period
     */
    public function isHolidayPeriod(array $holidays): bool
    {
        $today = Carbon::today();
        
        foreach ($holidays as $holiday) {
            $holidayDate = Carbon::parse($holiday['date']);
            
            // Skip if holiday is excluded
            if ($this->excluded_holidays && in_array($holiday['name'], $this->excluded_holidays)) {
                continue;
            }

            // Check if specific holidays are set and this holiday is not in the list
            if ($this->specific_holidays && !in_array($holiday['name'], $this->specific_holidays)) {
                continue;
            }

            // Check if holiday type matches
            if ($this->holiday_types && !in_array($holiday['type'], $this->holiday_types)) {
                continue;
            }

            // Calculate the valid date range for this holiday
            $startDate = $holidayDate->copy()->subDays($this->days_before ?? 0);
            $endDate = $holidayDate->copy()->addDays($this->days_after ?? 0);

            // Check if today falls within the holiday period
            if ($today->between($startDate, $endDate)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate price adjustment for holiday
     */
    public function calculateAdjustment(float $basePrice): float
    {
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
}
