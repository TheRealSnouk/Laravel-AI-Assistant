<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use App\Models\Order;
use App\Events\PaymentSucceeded;
use App\Events\PaymentFailed;
use App\Events\SubscriptionCreated;
use App\Events\SubscriptionUpdated;
use App\Events\SubscriptionCancelled;
use App\Events\SubscriptionTrialEnding;
use Exception;

class StripeWebhookService
{
    /**
     * Handle payment_intent.succeeded event
     */
    public function handlePaymentIntentSucceeded($paymentIntent)
    {
        try {
            Log::info('Processing successful payment', ['payment_intent_id' => $paymentIntent->id]);

            // Update payment record
            $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();
            if ($payment) {
                $payment->update([
                    'status' => 'completed',
                    'processed_at' => now(),
                    'transaction_id' => $paymentIntent->charges->data[0]->id ?? null,
                    'payment_method_details' => $paymentIntent->charges->data[0]->payment_method_details ?? null,
                ]);

                // Update related order
                if ($payment->order) {
                    $payment->order->update(['status' => 'paid']);
                }

                // Emit payment succeeded event
                event(new PaymentSucceeded($payment));

                // Send confirmation email
                $this->sendPaymentConfirmation($payment);
            }

            return true;
        } catch (Exception $e) {
            Log::error('Failed to process payment success webhook', [
                'payment_intent_id' => $paymentIntent->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Handle payment_intent.payment_failed event
     */
    public function handlePaymentIntentFailed($paymentIntent)
    {
        try {
            Log::info('Processing failed payment', ['payment_intent_id' => $paymentIntent->id]);

            $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();
            if ($payment) {
                $payment->update([
                    'status' => 'failed',
                    'error_message' => $paymentIntent->last_payment_error->message ?? 'Payment failed',
                    'failure_code' => $paymentIntent->last_payment_error->code ?? null,
                    'failure_message' => $paymentIntent->last_payment_error->message ?? null,
                ]);

                // Update related order
                if ($payment->order) {
                    $payment->order->update(['status' => 'payment_failed']);
                }

                // Emit payment failed event
                event(new PaymentFailed($payment));

                // Send failure notification
                $this->sendPaymentFailureNotification($payment);
            }

            return true;
        } catch (Exception $e) {
            Log::error('Failed to process payment failure webhook', [
                'payment_intent_id' => $paymentIntent->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Handle charge.refunded event
     */
    public function handleChargeRefunded($charge)
    {
        try {
            Log::info('Processing refund', ['charge_id' => $charge->id]);

            $payment = Payment::where('transaction_id', $charge->id)->first();
            if ($payment) {
                $payment->update([
                    'status' => 'refunded',
                    'refunded_at' => now(),
                    'refund_id' => $charge->refunds->data[0]->id ?? null,
                    'refund_amount' => $charge->amount_refunded,
                ]);

                // Update related order
                if ($payment->order) {
                    $payment->order->update(['status' => 'refunded']);
                }

                // Send refund confirmation
                $this->sendRefundConfirmation($payment);
            }

            return true;
        } catch (Exception $e) {
            Log::error('Failed to process refund webhook', [
                'charge_id' => $charge->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Handle charge.dispute.created event
     */
    public function handleDisputeCreated($dispute)
    {
        try {
            Log::info('Processing dispute', ['dispute_id' => $dispute->id]);

            $payment = Payment::where('transaction_id', $dispute->charge)->first();
            if ($payment) {
                $payment->update([
                    'status' => 'disputed',
                    'dispute_id' => $dispute->id,
                    'dispute_reason' => $dispute->reason,
                    'dispute_status' => $dispute->status,
                    'disputed_at' => now(),
                ]);

                // Update related order
                if ($payment->order) {
                    $payment->order->update(['status' => 'disputed']);
                }

                // Alert admin about dispute
                $this->alertAdminAboutDispute($payment, $dispute);
            }

            return true;
        } catch (Exception $e) {
            Log::error('Failed to process dispute webhook', [
                'dispute_id' => $dispute->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Handle customer.subscription.created event
     */
    public function handleSubscriptionCreated($subscription)
    {
        try {
            Log::info('Processing subscription created', ['subscription_id' => $subscription->id]);
            event(new SubscriptionCreated($subscription, request()->event));
            return true;
        } catch (Exception $e) {
            Log::error('Failed to process subscription created webhook', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Handle customer.subscription.updated event
     */
    public function handleSubscriptionUpdated($subscription)
    {
        try {
            Log::info('Processing subscription updated', ['subscription_id' => $subscription->id]);
            event(new SubscriptionUpdated($subscription, request()->event));
            return true;
        } catch (Exception $e) {
            Log::error('Failed to process subscription updated webhook', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Handle customer.subscription.deleted event
     */
    public function handleSubscriptionDeleted($subscription)
    {
        try {
            Log::info('Processing subscription cancelled', ['subscription_id' => $subscription->id]);
            event(new SubscriptionCancelled($subscription, request()->event));
            return true;
        } catch (Exception $e) {
            Log::error('Failed to process subscription cancelled webhook', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Handle customer.subscription.trial_will_end event
     */
    public function handleSubscriptionTrialWillEnd($subscription)
    {
        try {
            Log::info('Processing subscription trial ending', ['subscription_id' => $subscription->id]);
            event(new SubscriptionTrialEnding($subscription, request()->event));
            return true;
        } catch (Exception $e) {
            Log::error('Failed to process subscription trial ending webhook', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Send payment confirmation email
     */
    private function sendPaymentConfirmation($payment)
    {
        // Implementation for sending confirmation email
        // You can use Laravel's Mail facade here
    }

    /**
     * Send payment failure notification
     */
    private function sendPaymentFailureNotification($payment)
    {
        // Implementation for sending failure notification
        // You can use Laravel's Mail facade here
    }

    /**
     * Send refund confirmation
     */
    private function sendRefundConfirmation($payment)
    {
        // Implementation for sending refund confirmation
        // You can use Laravel's Mail facade here
    }

    /**
     * Alert admin about dispute
     */
    private function alertAdminAboutDispute($payment, $dispute)
    {
        // Implementation for alerting admin about dispute
        // You can use Laravel's Mail or Notification facade here
    }
}
