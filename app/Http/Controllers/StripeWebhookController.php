<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\WebhookSignature;
use Stripe\Exception\SignatureVerificationException;
use Log;

class StripeWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->all();
        $signingSecret = config('services.stripe.webhook_secret');
        
        try {
            $event = WebhookSignature::verifyHeader(
                $request->getContent(),
                $request->header('Stripe-Signature'),
                $signingSecret,
                300 // Tolerance in seconds
            );

            return match ($event->type) {
                'customer.subscription.created' => $this->handleSubscriptionCreated($event),
                'customer.subscription.updated' => $this->handleSubscriptionUpdated($event),
                'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event),
                'invoice.payment_succeeded' => $this->handleInvoicePaymentSucceeded($event),
                'invoice.payment_failed' => $this->handleInvoicePaymentFailed($event),
                'payment_intent.succeeded' => $this->handlePaymentIntentSucceeded($event),
                'payment_intent.failed' => $this->handlePaymentIntentFailed($event),
                'customer.created' => $this->handleCustomerCreated($event),
                'customer.updated' => $this->handleCustomerUpdated($event),
                default => $this->handleUnknownEvent($event),
            };
        } catch (SignatureVerificationException $e) {
            Log::error('Webhook signature verification failed: ' . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\Exception $e) {
            Log::error('Webhook error: ' . $e->getMessage());
            return response()->json(['error' => 'Webhook handling failed'], 500);
        }
    }

    protected function handleSubscriptionCreated($event)
    {
        $subscription = $event->data->object;
        Log::info('Subscription created:', ['subscription_id' => $subscription->id]);
        
        // Add your subscription creation logic here
        // Example: Update user's subscription status in database
        
        return response()->json(['status' => 'success']);
    }

    protected function handleSubscriptionUpdated($event)
    {
        $subscription = $event->data->object;
        Log::info('Subscription updated:', ['subscription_id' => $subscription->id]);
        
        // Add your subscription update logic here
        
        return response()->json(['status' => 'success']);
    }

    protected function handleSubscriptionDeleted($event)
    {
        $subscription = $event->data->object;
        Log::info('Subscription deleted:', ['subscription_id' => $subscription->id]);
        
        // Add your subscription deletion logic here
        
        return response()->json(['status' => 'success']);
    }

    protected function handleInvoicePaymentSucceeded($event)
    {
        $invoice = $event->data->object;
        Log::info('Invoice payment succeeded:', ['invoice_id' => $invoice->id]);
        
        // Add your successful payment handling logic here
        
        return response()->json(['status' => 'success']);
    }

    protected function handleInvoicePaymentFailed($event)
    {
        $invoice = $event->data->object;
        Log::info('Invoice payment failed:', ['invoice_id' => $invoice->id]);
        
        // Add your failed payment handling logic here
        // Example: Send notification to user about failed payment
        
        return response()->json(['status' => 'success']);
    }

    protected function handlePaymentIntentSucceeded($event)
    {
        $paymentIntent = $event->data->object;
        Log::info('Payment intent succeeded:', ['payment_intent_id' => $paymentIntent->id]);
        
        // Add your successful payment intent handling logic here
        
        return response()->json(['status' => 'success']);
    }

    protected function handlePaymentIntentFailed($event)
    {
        $paymentIntent = $event->data->object;
        Log::info('Payment intent failed:', ['payment_intent_id' => $paymentIntent->id]);
        
        // Add your failed payment intent handling logic here
        
        return response()->json(['status' => 'success']);
    }

    protected function handleCustomerCreated($event)
    {
        $customer = $event->data->object;
        Log::info('Customer created:', ['customer_id' => $customer->id]);
        
        // Add your customer creation logic here
        
        return response()->json(['status' => 'success']);
    }

    protected function handleCustomerUpdated($event)
    {
        $customer = $event->data->object;
        Log::info('Customer updated:', ['customer_id' => $customer->id]);
        
        // Add your customer update logic here
        
        return response()->json(['status' => 'success']);
    }

    protected function handleUnknownEvent($event)
    {
        Log::info('Unknown webhook event received:', ['type' => $event->type]);
        return response()->json(['status' => 'success']);
    }
}
