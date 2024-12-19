<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SubscriptionService;

class UpdateTimeBasedPrices extends Command
{
    protected $signature = 'prices:update-time-based';
    protected $description = 'Update subscription prices based on time-based rules';

    private $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        parent::__construct();
        $this->subscriptionService = $subscriptionService;
    }

    public function handle()
    {
        $this->info('Starting time-based price updates...');

        try {
            $this->subscriptionService->updateTimeBasedPrices();
            $this->info('Successfully updated time-based prices');
        } catch (\Exception $e) {
            $this->error('Failed to update time-based prices: ' . $e->getMessage());
        }
    }
}
