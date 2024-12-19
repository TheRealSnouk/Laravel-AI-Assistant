<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CryptoPayment;
use App\Services\Payment\CryptoPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->search, function($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->subscription_status, function($query, $status) {
                $query->whereHas('subscription', function($q) use ($status) {
                    $q->where('status', $status);
                });
            })
            ->when($request->plan, function($query, $plan) {
                $query->whereHas('subscription', function($q) use ($plan) {
                    $q->where('plan', $plan);
                });
            })
            ->withCount(['cryptoPayments', 'cryptoPayments as completed_payments_count' => function($query) {
                $query->where('status', 'completed');
            }])
            ->withSum(['cryptoPayments as total_spent' => function($query) {
                $query->where('status', 'completed');
            }], 'amount')
            ->paginate(20);

        return view('admin.users', compact('users'));
    }

    public function show(User $user)
    {
        $user->loadCount([
            'cryptoPayments',
            'cryptoPayments as completed_payments_count' => function($query) {
                $query->where('status', 'completed');
            }
        ])->loadSum(['cryptoPayments as total_spent' => function($query) {
            $query->where('status', 'completed');
        }], 'amount');

        // Get user's payment history
        $payments = $user->cryptoPayments()
            ->with(['events' => function($query) {
                $query->latest();
            }])
            ->latest()
            ->paginate(10);

        // Get subscription history
        $subscriptionHistory = $user->subscriptionHistory()
            ->latest()
            ->get();

        // Get usage statistics
        $usageStats = [
            'api_calls' => $user->apiCalls()->count(),
            'last_active' => $user->last_active_at,
            'average_response_time' => $user->apiCalls()->avg('response_time'),
            'error_rate' => $this->calculateErrorRate($user)
        ];

        return view('admin.user-detail', compact(
            'user',
            'payments',
            'subscriptionHistory',
            'usageStats'
        ));
    }

    public function subscription(User $user)
    {
        $user->load('subscription');
        $availablePlans = config('subscription.plans');
        
        return view('admin.user-subscription', compact('user', 'availablePlans'));
    }

    private function calculateErrorRate(User $user): float
    {
        $totalCalls = $user->apiCalls()->count();
        if ($totalCalls === 0) {
            return 0;
        }

        $errorCalls = $user->apiCalls()
            ->where('status_code', '>=', 400)
            ->count();

        return ($errorCalls / $totalCalls) * 100;
    }
}
