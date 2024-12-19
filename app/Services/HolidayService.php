<?php

namespace App\Services;

use App\Models\HolidayRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Exception;

class HolidayService
{
    private $apiKey;
    private $baseUrl = 'https://calendarific.com/api/v2';

    public function __construct()
    {
        $this->apiKey = config('services.calendarific.api_key');
    }

    /**
     * Get holidays for a specific country and year
     */
    public function getHolidays(string $countryCode, int $year = null): array
    {
        $year = $year ?? Carbon::now()->year;
        $cacheKey = "holidays:{$countryCode}:{$year}";

        return Cache::remember($cacheKey, Carbon::now()->addDays(1), function () use ($countryCode, $year) {
            $response = Http::get("{$this->baseUrl}/holidays", [
                'api_key' => $this->apiKey,
                'country' => $countryCode,
                'year' => $year
            ]);

            if (!$response->successful()) {
                throw new Exception("Failed to fetch holidays: " . $response->body());
            }

            $data = $response->json();
            return $data['response']['holidays'] ?? [];
        });
    }

    /**
     * Get holidays for multiple countries
     */
    public function getMultiCountryHolidays(array $countryCodes, int $year = null): array
    {
        $holidays = [];
        foreach ($countryCodes as $countryCode) {
            $holidays[$countryCode] = $this->getHolidays($countryCode, $year);
        }
        return $holidays;
    }

    /**
     * Check if a specific date is a holiday
     */
    public function isHoliday(string $countryCode, string $date): bool
    {
        $carbonDate = Carbon::parse($date);
        $holidays = $this->getHolidays($countryCode, $carbonDate->year);

        foreach ($holidays as $holiday) {
            if ($carbonDate->isSameDay(Carbon::parse($holiday['date']['iso']))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get upcoming holidays
     */
    public function getUpcomingHolidays(string $countryCode, int $days = 30): array
    {
        $today = Carbon::today();
        $endDate = $today->copy()->addDays($days);
        $holidays = $this->getHolidays($countryCode, $today->year);

        // If the period spans into next year, get next year's holidays too
        if ($endDate->year > $today->year) {
            $holidays = array_merge(
                $holidays,
                $this->getHolidays($countryCode, $endDate->year)
            );
        }

        return array_filter($holidays, function ($holiday) use ($today, $endDate) {
            $holidayDate = Carbon::parse($holiday['date']['iso']);
            return $holidayDate->between($today, $endDate);
        });
    }

    /**
     * Get active holiday rules for a date
     */
    public function getActiveHolidayRules(array $rules, string $countryCode, string $date = null): array
    {
        $date = $date ?? Carbon::today();
        $carbonDate = Carbon::parse($date);
        
        // Get holidays for the current year
        $holidays = $this->getHolidays($countryCode, $carbonDate->year);

        return array_filter($rules, function ($rule) use ($holidays) {
            return $rule->status === 'active' && $rule->isHolidayPeriod($holidays);
        });
    }
}
