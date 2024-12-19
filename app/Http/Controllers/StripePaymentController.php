<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\StripeService;
use App\Services\StripeWebhookService;
use Exception;

class StripePaymentController extends Controller
{
    protected $stripeService;
    protected $webhookService;

    public function __construct(StripeService $stripeService, StripeWebhookService $webhookService)
    {
        $this->stripeService = $stripeService;
        $this->webhookService = $webhookService;
    }

    /**
     * Create a payment intent
     */
    public function createPaymentIntent(Request $request)
    {
        try {
            $amount = $request->input('amount');
            $currency = $request->input('currency', 'usd');
            $paymentMethod = $request->input('payment_method', 'card');

            $paymentIntent = $this->stripeService->createPaymentIntent($amount, $currency, $paymentMethod);

            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
                'publicKey' => config('services.stripe.key'),
            ]);
        } catch (Exception $e) {
            Log::error('Stripe payment intent creation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Payment initialization failed'], 500);
        }
    }

    /**
     * Handle successful payment
     */
    public function handleSuccess(Request $request)
    {
        try {
            $paymentIntentId = $request->input('payment_intent');
            $paymentIntent = $this->stripeService->retrievePaymentIntent($paymentIntentId);

            // Process successful payment (e.g., update order status, send confirmation)
            return response()->json(['status' => 'success']);
        } catch (Exception $e) {
            Log::error('Payment success handling failed: ' . $e->getMessage());
            return response()->json(['error' => 'Payment processing failed'], 500);
        }
    }

    /**
     * Handle Stripe webhooks
     */
    public function handleWebhook(Request $request)
    {
        try {
            $payload = $request->getContent();
            $sig_header = $request->header('Stripe-Signature');

            $event = $this->stripeService->constructWebhookEvent($payload, $sig_header);

            // Handle the event
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->webhookService->handlePaymentIntentSucceeded($event->data->object);
                    break;

                case 'payment_intent.payment_failed':
                    $this->webhookService->handlePaymentIntentFailed($event->data->object);
                    break;

                case 'charge.refunded':
                    $this->webhookService->handleChargeRefunded($event->data->object);
                    break;

                case 'charge.dispute.created':
                    $this->webhookService->handleDisputeCreated($event->data->object);
                    break;

                default:
                    Log::info('Unhandled webhook event: ' . $event->type);
            }

            return response()->json(['status' => 'success']);
        } catch (Exception $e) {
            Log::error('Webhook handling failed: ' . $e->getMessage(), [
                'event_type' => $event->type ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Webhook failed'], 400);
        }
    }
}
