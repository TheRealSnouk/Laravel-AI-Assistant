<?php

namespace App\Events\Subscription;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionTrialEnding
{
    use Dispatchable, SerializesModels;

    public $subscription;
    public $stripeEvent;

    public function __construct($subscription, $stripeEvent)
    {
        $this->subscription = $subscription;
        $this->stripeEvent = $stripeEvent;
    }
}
