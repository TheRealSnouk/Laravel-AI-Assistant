<?php

namespace App\Listeners\Subscription;

use App\Events\Subscription\SubscriptionCancelled;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;

class HandleSubscriptionCancelled
{
    public function handle(SubscriptionCancelled $event)
    {
        try {
            $stripeSubscription = $event->stripeEvent->data->object;
            
            $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription->id)->first();
            
            if (!$subscription) {
                Log::error('Subscription not found for cancellation', [
                    'stripe_subscription_id' => $stripeSubscription->id
                ]);
                return;
            }

            // Update subscription status and end date
            $subscription->update([
                'stripe_status' => $stripeSubscription->status,
                'ends_at' => now()->createFromTimestamp($stripeSubscription->canceled_at),
            ]);

            // Update user subscription status
            $subscription->user->updateSubscriptionStatus();

            // Handle any cleanup or notification tasks
            if ($subscription->user) {
                // You might want to notify the user
                // $subscription->user->notify(new SubscriptionCancelledNotification($subscription));
            }

            Log::info('Subscription cancelled successfully', [
                'subscription_id' => $subscription->id,
                'stripe_subscription_id' => $stripeSubscription->id
            ]);
        } catch (\Exception $e) {
            Log::error('Error handling subscription.cancelled event', [
                'error' => $e->getMessage(),
                'stripe_event_id' => $event->stripeEvent->id
            ]);
            throw $e;
        }
    }
}
