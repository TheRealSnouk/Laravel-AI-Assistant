<?php

namespace App\Http\Middleware;

use App\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscriptionFeature
{
    public function __construct(
        private SubscriptionService $subscriptionService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (!$this->subscriptionService->canUseFeature($feature)) {
            return response()->json([
                'error' => 'Subscription required',
                'message' => "This feature requires a subscription plan that includes '{$feature}'",
                'required_plans' => $this->getRequiredPlans($feature),
                'upgrade_url' => route('subscription.index')
            ], 402);
        }

        if ($this->subscriptionService->hasReachedLimit('requests')) {
            return response()->json([
                'error' => 'Usage limit reached',
                'message' => 'You have reached your daily API request limit',
                'upgrade_url' => route('subscription.index')
            ], 429);
        }

        $response = $next($request);

        // Record usage after successful request
        if ($response->getStatusCode() < 400) {
            $this->subscriptionService->recordUsage('request');
        }

        return $response;
    }

    /**
     * Get plans that include the feature
     */
    private function getRequiredPlans(string $feature): array
    {
        $plans = config('subscription.plans');
        $requiredPlans = [];

        foreach ($plans as $planId => $plan) {
            if (!empty($plan['features'][$feature])) {
                $requiredPlans[] = [
                    'id' => $planId,
                    'name' => $plan['name'],
                    'price' => $plan['price']
                ];
            }
        }

        return $requiredPlans;
    }
}
