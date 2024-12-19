<?php

use App\Http\Controllers\AIAssistantController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CryptoPaymentController;
use App\Http\Controllers\StripePaymentController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/chat', [ChatController::class, 'chat'])->name('chat.message');

// Stripe Payment Routes
Route::post('/payment/create-intent', [StripePaymentController::class, 'createPaymentIntent']);
Route::post('/payment/success', [StripePaymentController::class, 'handleSuccess']);
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);

// Crypto Payment Routes
Route::prefix('payment/crypto')->name('payment.crypto.')->group(function () {
    Route::post('/create-intent', [CryptoPaymentController::class, 'createPaymentIntent'])->name('create-intent');
    Route::post('/verify', [CryptoPaymentController::class, 'verifyPayment'])->name('verify');
    Route::post('/webhook', [CryptoPaymentController::class, 'handleWebhook'])->name('webhook');
});

// AI Assistant Routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/ai/complete', [AIAssistantController::class, 'complete']);
    Route::post('/ai/review', [AIAssistantController::class, 'review']);
    Route::post('/ai/refactor', [AIAssistantController::class, 'refactor']);
    Route::post('/ai/document', [AIAssistantController::class, 'document']);
    Route::post('/ai/tests', [AIAssistantController::class, 'generateTests']);
    Route::post('/ai/security', [AIAssistantController::class, 'analyzeSecurity']);
    Route::post('/ai/snippets', [AIAssistantController::class, 'saveSnippet']);
});
