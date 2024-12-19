<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

// Payment Events
use App\Events\PaymentSucceeded;
use App\Events\PaymentFailed;
use App\Listeners\HandlePaymentSuccess;
use App\Listeners\HandlePaymentFailure;

// Subscription Events
use App\Events\Subscription\SubscriptionCreated;
use App\Events\Subscription\SubscriptionUpdated;
use App\Events\Subscription\SubscriptionCancelled;
use App\Events\Subscription\SubscriptionTrialEnding;
use App\Listeners\Subscription\HandleSubscriptionCreated;
use App\Listeners\Subscription\HandleSubscriptionUpdated;
use App\Listeners\Subscription\HandleSubscriptionCancelled;
use App\Listeners\Subscription\HandleSubscriptionTrialEnding;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        
        // Payment Events
        PaymentSucceeded::class => [
            HandlePaymentSuccess::class,
        ],
        PaymentFailed::class => [
            HandlePaymentFailure::class,
        ],

        // Subscription Events
        SubscriptionCreated::class => [
            HandleSubscriptionCreated::class,
        ],
        SubscriptionUpdated::class => [
            HandleSubscriptionUpdated::class,
        ],
        SubscriptionCancelled::class => [
            HandleSubscriptionCancelled::class,
        ],
        SubscriptionTrialEnding::class => [
            HandleSubscriptionTrialEnding::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
