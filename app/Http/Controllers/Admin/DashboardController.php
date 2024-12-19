<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CryptoPayment;
use App\Models\User;
use App\Services\Payment\CryptoPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Get overall platform statistics
        $stats = [
            'total_users' => User::count(),
            'active_subscriptions' => User::whereHas('subscription', function($query) {
                $query->where('status', 'active');
            })->count(),
            'total_revenue' => CryptoPayment::where('status', 'completed')->sum('amount'),
            'pending_payments' => CryptoPayment::where('status', 'pending')->count(),
        ];

        // Get payment statistics by network
        $networkStats = CryptoPayment::select('network', 
            DB::raw('COUNT(*) as total_transactions'),
            DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as successful_transactions'),
            DB::raw('SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END) as total_volume')
        )
        ->groupBy('network')
        ->get();

        // Get daily transaction volume for the last 30 days
        $dailyVolume = CryptoPayment::where('created_at', '>=', now()->subDays(30))
            ->where('status', 'completed')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as volume')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get subscription plan distribution
        $planDistribution = User::whereHas('subscription')
            ->select('plan', DB::raw('COUNT(*) as count'))
            ->groupBy('plan')
            ->get();

        // Get recent payments
        $recentPayments = CryptoPayment::with('user')
            ->latest()
            ->take(10)
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'networkStats',
            'dailyVolume',
            'planDistribution',
            'recentPayments'
        ));
    }

    public function users(Request $request)
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

    public function payments(Request $request)
    {
        $payments = CryptoPayment::with('user')
            ->when($request->status, function($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->network, function($query, $network) {
                $query->where('network', $network);
            })
            ->when($request->date_range, function($query, $range) {
                $dates = explode(' - ', $range);
                $query->whereBetween('created_at', [
                    $dates[0] . ' 00:00:00',
                    $dates[1] . ' 23:59:59'
                ]);
            })
            ->when($request->search, function($query, $search) {
                $query->where(function($q) use ($search) {
                    $q->where('reference', 'like', "%{$search}%")
                      ->orWhere('transaction_hash', 'like', "%{$search}%")
                      ->orWhereHas('user', function($uq) use ($search) {
                          $uq->where('email', 'like', "%{$search}%");
                      });
                });
            })
            ->latest()
            ->paginate(20);

        return view('admin.payments', compact('payments'));
    }

    public function verifyPayment(CryptoPayment $payment)
    {
        try {
            $service = app(CryptoPaymentService::class);
            $verified = $service->verifyPayment($payment->reference);

            return response()->json([
                'success' => true,
                'verified' => $verified['verified'],
                'message' => $verified['verified'] ? 'Payment verified successfully' : 'Payment not verified yet'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify payment: ' . $e->getMessage()
            ], 500);
        }
    }

    public function analytics()
    {
        // Get monthly revenue trends
        $monthlyRevenue = CryptoPayment::where('status', 'completed')
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as revenue')
            )
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Get user growth trends
        $userGrowth = User::select(
            DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
            DB::raw('COUNT(*) as new_users')
        )
        ->groupBy('month')
        ->orderBy('month')
        ->get();

        // Get conversion rates
        $conversionStats = [
            'trial_to_paid' => $this->calculateConversionRate('trial', 'paid'),
            'basic_to_pro' => $this->calculateConversionRate('basic', 'professional'),
            'pro_to_enterprise' => $this->calculateConversionRate('professional', 'enterprise')
        ];

        // Get average revenue per user
        $arpu = CryptoPayment::where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->avg('amount');

        return view('admin.analytics', compact(
            'monthlyRevenue',
            'userGrowth',
            'conversionStats',
            'arpu'
        ));
    }

    private function calculateConversionRate(string $fromPlan, string $toPlan): float
    {
        $totalFromPlan = User::whereHas('subscription', function($query) use ($fromPlan) {
            $query->where('plan', $fromPlan);
        })->count();

        $conversions = User::whereHas('subscription', function($query) use ($toPlan) {
            $query->where('plan', $toPlan);
        })
        ->whereHas('subscriptionHistory', function($query) use ($fromPlan) {
            $query->where('plan', $fromPlan);
        })
        ->count();

        return $totalFromPlan > 0 ? ($conversions / $totalFromPlan) * 100 : 0;
    }
}
