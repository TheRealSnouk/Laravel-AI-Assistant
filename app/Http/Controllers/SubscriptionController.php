<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionService $subscriptionService
    ) {}

    /**
     * Display subscription plans
     */
    public function index()
    {
        $plans = config('subscription.plans');
        $features = config('subscription.features');
        $currentSubscription = $this->subscriptionService->getCurrentSubscription();

        return view('subscription.index', [
            'plans' => $plans,
            'features' => $features,
            'currentPlan' => $currentSubscription?->plan ?? 'free',
            'usage' => $this->subscriptionService->getCurrentUsage(),
        ]);
    }

    /**
     * Show subscription details
     */
    public function show()
    {
        $subscription = $this->subscriptionService->getCurrentSubscription();
        
        if (!$subscription) {
            return redirect()->route('subscription.index');
        }

        return view('subscription.show', [
            'subscription' => $subscription,
            'usage' => $this->subscriptionService->getCurrentUsage(),
            'features' => $this->subscriptionService->getActiveFeatures(),
            'limits' => $this->subscriptionService->getCurrentLimits(),
            'history' => $this->subscriptionService->getUsageHistory()
        ]);
    }

    /**
     * Handle plan upgrade/downgrade
     */
    public function changePlan(Request $request)
    {
        try {
            $request->validate([
                'plan' => 'required|string|in:' . implode(',', array_keys(config('subscription.plans')))
            ]);

            $result = $this->subscriptionService->changePlan($request->plan);

            if ($result['requires_payment']) {
                return redirect()->route('subscription.checkout', ['plan' => $request->plan]);
            }

            return redirect()
                ->route('subscription.show')
                ->with('success', 'Subscription plan updated successfully');

        } catch (\Exception $e) {
            Log::error('Subscription Change Error', [
                'error' => $e->getMessage(),
                'plan' => $request->plan
            ]);

            return back()->with('error', 'Failed to change subscription plan');
        }
    }

    /**
     * Show checkout page
     */
    public function checkout(Request $request)
    {
        $plan = config('subscription.plans.' . $request->plan);
        
        if (!$plan) {
            return redirect()->route('subscription.index');
        }

        return view('subscription.checkout', [
            'plan' => $plan,
            'intent' => $this->subscriptionService->createPaymentIntent($plan['price'])
        ]);
    }

    /**
     * Process subscription payment
     */
    public function processPayment(Request $request)
    {
        try {
            $request->validate([
                'plan' => 'required|string',
                'payment_method' => 'required|string'
            ]);

            $result = $this->subscriptionService->processSubscription(
                $request->plan,
                $request->payment_method
            );

            return response()->json([
                'success' => true,
                'redirect' => route('subscription.show')
            ]);

        } catch (\Exception $e) {
            Log::error('Payment Processing Error', [
                'error' => $e->getMessage(),
                'plan' => $request->plan
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed'
            ], 400);
        }
    }

    /**
     * Cancel subscription
     */
    public function cancel(Request $request)
    {
        try {
            $this->subscriptionService->cancelSubscription();

            return redirect()
                ->route('subscription.index')
                ->with('success', 'Subscription cancelled successfully');

        } catch (\Exception $e) {
            Log::error('Subscription Cancellation Error', [
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to cancel subscription');
        }
    }

    /**
     * Resume cancelled subscription
     */
    public function resume(Request $request)
    {
        try {
            $this->subscriptionService->resumeSubscription();

            return redirect()
                ->route('subscription.show')
                ->with('success', 'Subscription resumed successfully');

        } catch (\Exception $e) {
            Log::error('Subscription Resume Error', [
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Failed to resume subscription');
        }
    }

    /**
     * Show billing history
     */
    public function billingHistory()
    {
        return view('subscription.billing', [
            'invoices' => $this->subscriptionService->getBillingHistory(),
            'subscription' => $this->subscriptionService->getCurrentSubscription()
        ]);
    }
}
