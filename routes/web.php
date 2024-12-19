<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AIAssistantController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\BlockchainHealthController;
use App\Http\Controllers\PaymentDashboardController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;

// Public routes
Route::get('/', [AIAssistantController::class, 'index'])->name('home');
Route::get('/subscription/upgrade', [SubscriptionController::class, 'upgrade'])->name('subscription.upgrade');
Route::get('/subscription/status', [SubscriptionController::class, 'status'])->name('subscription.status');
Route::post('/subscription/process', [SubscriptionController::class, 'process'])->name('subscription.process');

// Payment Routes
Route::prefix('payment')->name('payment.')->group(function () {
    Route::get('/details', [PaymentController::class, 'getPaymentDetails'])->name('details');
    Route::get('/pricing', [PaymentController::class, 'getPricing'])->name('pricing');
    
    // Hedera payment routes
    Route::post('/hedera/verify', [PaymentController::class, 'verifyHederaPayment'])->name('hedera.verify');
    
    // PayPal routes
    Route::post('/paypal/create', [PaymentController::class, 'createPayPalOrder'])->name('paypal.create');
    Route::get('/paypal/success', [PaymentController::class, 'handlePayPalSuccess'])->name('paypal.success');
    Route::get('/paypal/cancel', function () {
        return redirect()->route('dashboard')->with('error', 'Payment cancelled');
    })->name('paypal.cancel');
    
    // Stripe routes
    Route::post('/stripe/create', [PaymentController::class, 'createStripeSession'])->name('stripe.create');
    Route::get('/stripe/success', [PaymentController::class, 'handleStripeSuccess'])->name('stripe.success');
    Route::get('/stripe/cancel', function () {
        return redirect()->route('dashboard')->with('error', 'Payment cancelled');
    })->name('stripe.cancel');
});

// Payment Dashboard Routes
Route::middleware(['auth'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/payments', [PaymentDashboardController::class, 'index'])->name('payments');
    Route::get('/payments/{payment}', [PaymentDashboardController::class, 'show'])->name('payment.show');
    Route::get('/transactions', [PaymentDashboardController::class, 'transactions'])->name('transactions');
    Route::get('/payments/export', [PaymentDashboardController::class, 'export'])->name('payments.export');
});

// Subscription Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/subscription', [SubscriptionController::class, 'index'])->name('subscription.index');
    Route::get('/subscription/show', [SubscriptionController::class, 'show'])->name('subscription.show');
    Route::post('/subscription/change-plan', [SubscriptionController::class, 'changePlan'])->name('subscription.change-plan');
    Route::get('/subscription/checkout', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
    Route::post('/subscription/process-payment', [SubscriptionController::class, 'processPayment'])->name('subscription.process-payment');
    Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
    Route::post('/subscription/resume', [SubscriptionController::class, 'resume'])->name('subscription.resume');
    Route::get('/subscription/billing', [SubscriptionController::class, 'billingHistory'])->name('subscription.billing');
    Route::post('/subscription/pay-invoice/{invoice}', [SubscriptionController::class, 'payInvoice'])->name('subscription.pay-invoice');
});

// Blockchain Health Dashboard Routes
Route::prefix('blockchain/health')->name('blockchain.health.')->middleware(['auth'])->group(function () {
    Route::get('/', [BlockchainHealthController::class, 'dashboard'])->name('dashboard');
    Route::get('/{network}', [BlockchainHealthController::class, 'networkDetails'])->name('network');
    Route::get('/{network}/refresh', [BlockchainHealthController::class, 'refreshStatus'])->name('refresh');
});

// Admin Routes
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    
    // User Management
    Route::get('/users', [UserController::class, 'index'])->name('users');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/subscription', [UserController::class, 'subscription'])->name('users.subscription');
    
    // Payment Management
    Route::get('/payments', [DashboardController::class, 'payments'])->name('payments');
    Route::get('/payments/{payment}', [DashboardController::class, 'paymentDetail'])->name('payments.show');
    Route::post('/payments/{payment}/verify', [DashboardController::class, 'verifyPayment'])->name('payments.verify');
    Route::get('/payments/export', [DashboardController::class, 'exportPayments'])->name('payments.export');
});

// Protected routes with rate limiting and subscription checks
Route::middleware(['throttle:api', 'subscription'])->group(function () {
    Route::post('/chat', [AIAssistantController::class, 'chat'])->name('chat');
    Route::post('/settings', [AIAssistantController::class, 'updateSettings'])->name('settings.update');
    Route::post('/test-connection', [AIAssistantController::class, 'testConnection'])->name('test.connection');
});
