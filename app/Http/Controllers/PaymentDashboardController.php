<?php

namespace App\Http\Controllers;

use App\Models\CryptoPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Get payment statistics
        $stats = [
            'total_payments' => $user->cryptoPayments()->count(),
            'total_amount' => $user->cryptoPayments()
                ->where('status', 'completed')
                ->sum('amount'),
            'pending_payments' => $user->cryptoPayments()
                ->where('status', 'pending')
                ->count(),
            'recent_activity' => $user->cryptoPayments()
                ->latest()
                ->take(5)
                ->get(),
        ];

        // Get payments by status
        $paymentsByStatus = $user->cryptoPayments()
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        // Get payments by network
        $paymentsByNetwork = $user->cryptoPayments()
            ->select('network', DB::raw('count(*) as count'))
            ->groupBy('network')
            ->get()
            ->pluck('count', 'network')
            ->toArray();

        // Get monthly payment trends
        $monthlyTrends = $user->cryptoPayments()
            ->where('status', 'completed')
            ->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->take(12)
            ->get();

        return view('dashboard.payments', compact(
            'stats',
            'paymentsByStatus',
            'paymentsByNetwork',
            'monthlyTrends'
        ));
    }

    public function show(Request $request, CryptoPayment $payment)
    {
        $this->authorize('view', $payment);

        // Get payment verification status
        $verificationStatus = app(CryptoPaymentService::class)
            ->checkPaymentStatus($payment);

        return view('dashboard.payment-details', compact(
            'payment',
            'verificationStatus'
        ));
    }

    public function transactions(Request $request)
    {
        $payments = $request->user()
            ->cryptoPayments()
            ->when($request->status, function($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->network, function($query, $network) {
                return $query->where('network', $network);
            })
            ->when($request->search, function($query, $search) {
                return $query->where(function($q) use ($search) {
                    $q->where('reference', 'like', "%{$search}%")
                      ->orWhere('transaction_hash', 'like', "%{$search}%")
                      ->orWhere('sender_address', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10);

        if ($request->wantsJson()) {
            return response()->json([
                'payments' => $payments,
                'pagination' => [
                    'total' => $payments->total(),
                    'per_page' => $payments->perPage(),
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                ],
            ]);
        }

        return view('dashboard.transactions', compact('payments'));
    }

    public function export(Request $request)
    {
        $this->authorize('export-payments', User::class);

        return Excel::download(
            new CryptoPaymentsExport($request->user()),
            'crypto-payments.xlsx'
        );
    }
}
