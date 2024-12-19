<?php

namespace App\Listeners\Subscription;

use App\Events\Subscription\SubscriptionCreated;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class HandleSubscriptionCreated
{
    public function handle(SubscriptionCreated $event)
    {
        try {
            $stripeSubscription = $event->stripeEvent->data->object;
            
            // Find or create the subscription
            $subscription = Subscription::updateOrCreate(
                ['stripe_subscription_id' => $stripeSubscription->id],
                [
                    'user_id' => User::where('stripe_customer_id', $stripeSubscription->customer)->value('id'),
                    'stripe_price_id' => $stripeSubscription->items->data[0]->price->id,
                    'stripe_status' => $stripeSubscription->status,
                    'trial_ends_at' => $stripeSubscription->trial_end ? now()->createFromTimestamp($stripeSubscription->trial_end) : null,
                    'ends_at' => $stripeSubscription->cancel_at ? now()->createFromTimestamp($stripeSubscription->cancel_at) : null,
                    'next_billing_date' => $stripeSubscription->current_period_end ? now()->createFromTimestamp($stripeSubscription->current_period_end) : null,
                ]
            );

            // Update user subscription status
            $subscription->user->updateSubscriptionStatus();

            Log::info('Subscription created successfully', [
                'subscription_id' => $subscription->id,
                'stripe_subscription_id' => $stripeSubscription->id
            ]);
        } catch (\Exception $e) {
            Log::error('Error handling subscription.created event', [
                'error' => $e->getMessage(),
                'stripe_event_id' => $event->stripeEvent->id
            ]);
            throw $e;
        }
    }
}
