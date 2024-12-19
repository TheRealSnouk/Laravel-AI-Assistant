<?php

namespace App\Listeners\Subscription;

use App\Events\Subscription\SubscriptionUpdated;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;

class HandleSubscriptionUpdated
{
    public function handle(SubscriptionUpdated $event)
    {
        try {
            $stripeSubscription = $event->stripeEvent->data->object;
            
            $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription->id)->first();
            
            if (!$subscription) {
                Log::error('Subscription not found for update', [
                    'stripe_subscription_id' => $stripeSubscription->id
                ]);
                return;
            }

            // Update subscription details
            $subscription->update([
                'stripe_price_id' => $stripeSubscription->items->data[0]->price->id,
                'stripe_status' => $stripeSubscription->status,
                'trial_ends_at' => $stripeSubscription->trial_end ? now()->createFromTimestamp($stripeSubscription->trial_end) : null,
                'ends_at' => $stripeSubscription->cancel_at ? now()->createFromTimestamp($stripeSubscription->cancel_at) : null,
                'next_billing_date' => $stripeSubscription->current_period_end ? now()->createFromTimestamp($stripeSubscription->current_period_end) : null,
            ]);

            // Update user subscription status
            $subscription->user->updateSubscriptionStatus();

            Log::info('Subscription updated successfully', [
                'subscription_id' => $subscription->id,
                'stripe_subscription_id' => $stripeSubscription->id
            ]);
        } catch (\Exception $e) {
            Log::error('Error handling subscription.updated event', [
                'error' => $e->getMessage(),
                'stripe_event_id' => $event->stripeEvent->id
            ]);
            throw $e;
        }
    }
}
