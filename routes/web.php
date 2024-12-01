<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AIAssistantController;
use App\Http\Controllers\TestController;

Route::get('/', [AIAssistantController::class, 'index'])->name('home');
Route::post('/chat', [AIAssistantController::class, 'chat'])->name('chat');
Route::get('/test-box', [TestController::class, 'showBox'])->name('test.box');
Route::get('/test-connection', [AIAssistantController::class, 'testConnection'])->name('test.connection');
