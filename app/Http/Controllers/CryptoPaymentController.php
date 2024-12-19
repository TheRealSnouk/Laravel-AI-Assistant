<?php

namespace App\Http\Controllers;

use App\Services\Payment\CryptoPaymentService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CryptoPaymentController extends Controller
{
    public function __construct(
        private CryptoPaymentService $cryptoPaymentService,
        private SubscriptionService $subscriptionService
    ) {}

    /**
     * Create crypto payment intent
     */
    public function createPaymentIntent(Request $request)
    {
        try {
            $request->validate([
                'network' => 'required|string|in:hedera,cosmos',
                'plan' => 'required|string',
                'currency' => 'required|string|in:USDT,HBAR,ATOM'
            ]);

            $plan = config("subscription.plans.{$request->plan}");
            if (!$plan) {
                return response()->json([
                    'error' => 'Invalid subscription plan'
                ], 400);
            }

            $intent = $this->cryptoPaymentService->createPaymentIntent(
                $request->network,
                $plan['price'],
                $request->currency
            );

            return response()->json([
                'success' => true,
                'payment' => $intent
            ]);

        } catch (\Exception $e) {
            Log::error('Crypto payment intent creation failed', [
                'error' => $e->getMessage(),
                'network' => $request->network ?? null
            ]);

            return response()->json([
                'error' => 'Failed to create payment intent',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify crypto payment
     */
    public function verifyPayment(Request $request)
    {
        try {
            $request->validate([
                'reference' => 'required|string'
            ]);

            $verification = $this->cryptoPaymentService->verifyPayment($request->reference);

            if ($verification['verified']) {
                // Activate subscription
                $this->subscriptionService->activateSubscription(
                    $verification['details']['plan'],
                    'crypto',
                    $verification['details']['reference']
                );

                return response()->json([
                    'success' => true,
                    'redirect' => route('subscription.show')
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Payment not verified yet'
            ]);

        } catch (\Exception $e) {
            Log::error('Payment verification failed', [
                'error' => $e->getMessage(),
                'reference' => $request->reference ?? null
            ]);

            return response()->json([
                'error' => 'Failed to verify payment',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle crypto payment webhook
     */
    public function handleWebhook(Request $request)
    {
        try {
            $payload = $request->all();
            Log::info('Crypto payment webhook received', $payload);

            // Verify webhook signature
            if (!$this->verifyWebhookSignature($request)) {
                return response()->json(['error' => 'Invalid signature'], 400);
            }

            // Process based on network
            $processed = match($payload['network']) {
                'hedera' => $this->processHederaWebhook($payload),
                'cosmos' => $this->processCosmosWebhook($payload),
                default => false
            };

            if ($processed) {
                return response()->json(['success' => true]);
            }

            return response()->json(['error' => 'Processing failed'], 400);

        } catch (\Exception $e) {
            Log::error('Crypto webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Process Hedera webhook
     */
    private function processHederaWebhook(array $payload): bool
    {
        // Implement Hedera-specific webhook processing
        return true;
    }

    /**
     * Process Cosmos webhook
     */
    private function processCosmosWebhook(array $payload): bool
    {
        // Implement Cosmos-specific webhook processing
        return true;
    }

    /**
     * Verify webhook signature
     */
    private function verifyWebhookSignature(Request $request): bool
    {
        // Implement signature verification
        return true;
    }
}
