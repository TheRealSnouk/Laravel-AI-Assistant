<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function getPaymentDetails(Request $request)
    {
        try {
            $validated = $request->validate([
                'tier' => 'required|string|in:basic,pro'
            ]);

            $paymentDetails = $this->paymentService->generatePaymentDetails($validated['tier']);

            return response()->json([
                'success' => true,
                'payment_details' => $paymentDetails,
                'note' => 'Multiple payment options available: Hedera (HBAR/USDT), PayPal, and Credit/Debit cards'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate payment details', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to generate payment details'], 500);
        }
    }

    public function verifyHederaPayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'transaction_id' => 'required|string',
                'tier' => 'required|string|in:basic,pro',
                'type' => 'required|string|in:hbar,usdt'
            ]);

            // Get expected amount for the tier
            $paymentDetails = $this->paymentService->generatePaymentDetails($validated['tier']);
            
            // Get the expected amount based on payment type
            $expectedAmount = $validated['type'] === 'hbar' 
                ? $paymentDetails['crypto']['hbar']['amount']
                : $paymentDetails['crypto']['usdt']['amount'];

            // Verify the payment
            $verified = $this->paymentService->verifyHederaPayment(
                $validated['transaction_id'],
                $expectedAmount,
                $validated['type']
            );

            if (!$verified) {
                return response()->json(['error' => 'Payment verification failed'], 400);
            }

            // Update subscription
            $this->updateSubscription($validated['tier']);

            return response()->json([
                'success' => true,
                'message' => 'Payment verified and subscription updated'
            ]);

        } catch (\Exception $e) {
            Log::error('Payment verification failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Payment verification failed'], 500);
        }
    }

    public function createPayPalOrder(Request $request)
    {
        try {
            $validated = $request->validate([
                'tier' => 'required|string|in:basic,pro'
            ]);

            $order = $this->paymentService->createPayPalOrder($validated['tier']);

            return response()->json([
                'success' => true,
                'order' => $order
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create PayPal order', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create PayPal order'], 500);
        }
    }

    public function handlePayPalSuccess(Request $request)
    {
        try {
            $orderId = $request->get('order_id');
            $tier = $request->get('tier');

            // Here you would verify the PayPal order status
            // For brevity, we'll assume it's verified
            
            $this->updateSubscription($tier);

            return redirect()->route('dashboard')->with('success', 'Subscription activated successfully!');

        } catch (\Exception $e) {
            Log::error('PayPal success handling failed', ['error' => $e->getMessage()]);
            return redirect()->route('dashboard')->with('error', 'Failed to process payment');
        }
    }

    public function createStripeSession(Request $request)
    {
        try {
            $validated = $request->validate([
                'tier' => 'required|string|in:basic,pro'
            ]);

            $session = $this->paymentService->createStripeSession($validated['tier']);

            return response()->json([
                'success' => true,
                'session_id' => $session->id
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create Stripe session', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to create Stripe session'], 500);
        }
    }

    public function handleStripeSuccess(Request $request)
    {
        try {
            $session = $request->get('session_id');
            $tier = $request->get('tier');

            // Here you would verify the Stripe session status
            // For brevity, we'll assume it's verified
            
            $this->updateSubscription($tier);

            return redirect()->route('dashboard')->with('success', 'Subscription activated successfully!');

        } catch (\Exception $e) {
            Log::error('Stripe success handling failed', ['error' => $e->getMessage()]);
            return redirect()->route('dashboard')->with('error', 'Failed to process payment');
        }
    }

    protected function updateSubscription($tier)
    {
        $subscription = Subscription::where('session_id', session()->getId())->first();
        $tierLimits = Subscription::getTierLimits()[$tier];
        
        $subscription->update([
            'tier' => $tier,
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => now()->addMonth(),
            'api_calls_limit' => $tierLimits['daily_limit'],
            'api_calls_used' => 0
        ]);
    }

    public function getPricing()
    {
        try {
            $this->paymentService->updatePrices();
            
            $tiers = [
                'free' => [
                    'price' => 0,
                    'features' => [
                        'Basic OpenRouter models',
                        '50 API calls per day',
                        '1000 max tokens per request',
                        'Basic support'
                    ]
                ],
                'basic' => [
                    'price' => 10,
                    'payment_details' => $this->paymentService->generatePaymentDetails('basic'),
                    'features' => [
                        'All Free features',
                        'More OpenRouter models',
                        'Bolt Light model access',
                        '200 API calls per day',
                        '2000 max tokens per request',
                        'Email support'
                    ]
                ],
                'pro' => [
                    'price' => 30,
                    'payment_details' => $this->paymentService->generatePaymentDetails('pro'),
                    'features' => [
                        'All Basic features',
                        'All available models',
                        'NotDiamond models access',
                        '1000 API calls per day',
                        '4000 max tokens per request',
                        'Priority support',
                        'Custom system prompts'
                    ]
                ]
            ];

            return response()->json([
                'success' => true,
                'pricing' => $tiers,
                'payment_methods' => [
                    'crypto' => [
                        'hedera' => [
                            'name' => 'Hedera Network',
                            'currencies' => ['HBAR', 'USDT'],
                            'average_fee' => '< $0.001',
                            'speed' => '~3-5 seconds'
                        ]
                    ],
                    'traditional' => [
                        'paypal' => [
                            'name' => 'PayPal',
                            'currencies' => ['USD'],
                            'methods' => ['PayPal Balance', 'Bank Account', 'Credit/Debit Card']
                        ],
                        'stripe' => [
                            'name' => 'Credit/Debit Card',
                            'currencies' => ['USD'],
                            'cards' => ['Visa', 'Mastercard', 'American Express']
                        ]
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get pricing', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to get pricing information'], 500);
        }
    }
}
