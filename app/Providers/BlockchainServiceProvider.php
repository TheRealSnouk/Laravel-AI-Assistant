<?php

namespace App\Providers;

use App\Services\BlockchainConfigService;
use Illuminate\Support\ServiceProvider;

class BlockchainServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(BlockchainConfigService::class, function ($app) {
            return new BlockchainConfigService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
