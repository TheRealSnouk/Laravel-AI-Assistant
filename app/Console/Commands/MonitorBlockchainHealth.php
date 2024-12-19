<?php

namespace App\Console\Commands;

use App\Services\BlockchainMonitoringService;
use Illuminate\Console\Command;

class MonitorBlockchainHealth extends Command
{
    protected $signature = 'blockchain:monitor
                          {--network= : Specific network to monitor}
                          {--verbose : Show detailed output}';

    protected $description = 'Monitor blockchain network health';

    public function __construct(
        private BlockchainMonitoringService $monitoringService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $network = $this->option('network');
        $verbose = $this->option('verbose');

        if ($network) {
            $result = $this->monitoringService->checkNetworkHealth($network);
            $this->displayResults([$network => $result], $verbose);
        } else {
            $results = $this->monitoringService->runHealthCheck();
            $this->displayResults($results, $verbose);
        }

        return self::SUCCESS;
    }

    private function displayResults(array $results, bool $verbose): void
    {
        foreach ($results as $network => $health) {
            $status = $health['healthy'] ? '✅' : '❌';
            $this->info("\n{$status} {$network}");

            if (!$health['healthy'] || $verbose) {
                foreach ($health['issues'] as $issue) {
                    $this->warn("  - {$issue}");
                }
            }
        }
    }
}
