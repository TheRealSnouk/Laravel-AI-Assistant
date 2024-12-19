<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubscriptionMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $sessionId = session()->getId();
        
        // Get or create subscription
        $subscription = Subscription::firstOrCreate(
            ['session_id' => $sessionId],
            [
                'tier' => 'free',
                'status' => 'active',
                'starts_at' => now(),
                'expires_at' => now()->addDays(30),
                'api_calls_limit' => 50
            ]
        );

        if (!$subscription->isActive()) {
            return response()->json([
                'error' => 'Subscription inactive or expired',
                'upgrade_url' => route('subscription.upgrade')
            ], 403);
        }

        if (!$subscription->hasAvailableCalls()) {
            return response()->json([
                'error' => 'API call limit reached',
                'upgrade_url' => route('subscription.upgrade')
            ], 429);
        }

        // Check if selected model is available for the subscription tier
        $settings = $request->get('settings');
        if ($settings) {
            $availableModels = $subscription->getAvailableModels();
            if (!isset($availableModels[$settings->api_provider]) || 
                !in_array($settings->selected_model, $availableModels[$settings->api_provider])) {
                return response()->json([
                    'error' => 'Selected model not available in your subscription tier',
                    'upgrade_url' => route('subscription.upgrade')
                ], 403);
            }
        }

        // Add subscription to request for use in controller
        $request->merge(['subscription' => $subscription]);

        return $next($request);
    }
}
