<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestInfuraConnection extends Command
{
    protected $signature = 'test:infura';
    protected $description = 'Test Infura connection and contract accessibility';

    public function handle()
    {
        $this->info('Starting Infura connection test...');
        require_once base_path('tests/infura_test.php');
        return Command::SUCCESS;
    }
}
