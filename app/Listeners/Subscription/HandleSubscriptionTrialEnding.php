<?php

namespace App\Listeners\Subscription;

use App\Events\Subscription\SubscriptionTrialEnding;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;
use App\Notifications\SubscriptionTrialEndingNotification;

class HandleSubscriptionTrialEnding
{
    public function handle(SubscriptionTrialEnding $event)
    {
        try {
            $stripeSubscription = $event->stripeEvent->data->object;
            
            $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription->id)->first();
            
            if (!$subscription) {
                Log::error('Subscription not found for trial ending notification', [
                    'stripe_subscription_id' => $stripeSubscription->id
                ]);
                return;
            }

            // Update trial end date if needed
            $subscription->update([
                'trial_ends_at' => now()->createFromTimestamp($stripeSubscription->trial_end)
            ]);

            // Notify the user about trial ending
            if ($subscription->user) {
                // You might want to implement this notification
                // $subscription->user->notify(new SubscriptionTrialEndingNotification($subscription));
            }

            Log::info('Trial ending notification processed successfully', [
                'subscription_id' => $subscription->id,
                'stripe_subscription_id' => $stripeSubscription->id,
                'trial_ends_at' => $subscription->trial_ends_at
            ]);
        } catch (\Exception $e) {
            Log::error('Error handling customer.subscription.trial_will_end event', [
                'error' => $e->getMessage(),
                'stripe_event_id' => $event->stripeEvent->id
            ]);
            throw $e;
        }
    }
}
