<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Webhook;
use Exception;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a new payment intent
     */
    public function createPaymentIntent($amount, $currency = 'usd', $paymentMethod = 'card')
    {
        try {
            return PaymentIntent::create([
                'amount' => $this->convertToCents($amount),
                'currency' => $currency,
                'payment_method_types' => [$paymentMethod],
                'metadata' => [
                    'integration_check' => 'accept_a_payment',
                ],
            ]);
        } catch (Exception $e) {
            throw new Exception('Failed to create payment intent: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve a payment intent
     */
    public function retrievePaymentIntent($paymentIntentId)
    {
        try {
            return PaymentIntent::retrieve($paymentIntentId);
        } catch (Exception $e) {
            throw new Exception('Failed to retrieve payment intent: ' . $e->getMessage());
        }
    }

    /**
     * Construct webhook event
     */
    public function constructWebhookEvent($payload, $sigHeader)
    {
        try {
            return Webhook::constructEvent(
                $payload,
                $sigHeader,
                config('services.stripe.webhook_secret')
            );
        } catch (Exception $e) {
            throw new Exception('Failed to construct webhook event: ' . $e->getMessage());
        }
    }

    /**
     * Convert amount to cents
     */
    private function convertToCents($amount)
    {
        return (int) ($amount * 100);
    }
}
