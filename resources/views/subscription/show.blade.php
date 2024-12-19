@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Subscription Header -->
            <div class="bg-blue-600 px-6 py-8 text-white">
                <h1 class="text-3xl font-bold mb-2">{{ config("subscription.plans.{$subscription->plan}.name") }}</h1>
                <p class="text-blue-100">
                    Next billing date: {{ $subscription->ends_at ? 'Cancelled' : $subscription->next_billing_date->format('F j, Y') }}
                </p>
            </div>

            <!-- Usage Stats -->
            <div class="p-6">
                <h2 class="text-xl font-semibold mb-4">Usage Statistics</h2>
                <div class="grid md:grid-cols-3 gap-6">
                    @foreach($usage as $type => $stats)
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-500 mb-2">{{ ucwords($type) }}</h3>
                        <div class="flex items-end space-x-2">
                            <div class="text-2xl font-bold">{{ number_format($stats['used']) }}</div>
                            <div class="text-sm text-gray-500">/ {{ $stats['limit'] === -1 ? '∞' : number_format($stats['limit']) }}</div>
                        </div>
                        @if($stats['limit'] !== -1)
                        <div class="mt-2 h-2 bg-gray-200 rounded-full overflow-hidden">
                            <div class="h-full bg-blue-600 rounded-full" style="width: {{ ($stats['used'] / $stats['limit']) * 100 }}%"></div>
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Active Features -->
            <div class="border-t border-gray-200 p-6">
                <h2 class="text-xl font-semibold mb-4">Active Features</h2>
                <div class="grid md:grid-cols-2 gap-4">
                    @foreach($features as $featureId => $enabled)
                    <div class="flex items-center space-x-3">
                        @if($enabled)
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        @else
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        @endif
                        <span class="{{ $enabled ? 'text-gray-900' : 'text-gray-500' }}">
                            {{ config("subscription.features.{$featureId}.name") }}
                        </span>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Recent Usage -->
            <div class="border-t border-gray-200 p-6">
                <h2 class="text-xl font-semibold mb-4">Recent Usage</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($history as $log)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ ucwords($log->type) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ number_format($log->amount) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $log->created_at->format('M j, Y H:i') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Actions -->
            <div class="border-t border-gray-200 p-6 bg-gray-50">
                <div class="flex justify-between items-center">
                    <a href="{{ route('subscription.index') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                        Change Plan
                    </a>
                    <div class="space-x-4">
                        <a href="{{ route('subscription.billing') }}" class="text-gray-600 hover:text-gray-800 font-medium">
                            Billing History
                        </a>
                        @if($subscription->cancelled())
                            <form action="{{ route('subscription.resume') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-green-600 hover:text-green-800 font-medium">
                                    Resume Subscription
                                </button>
                            </form>
                        @else
                            <form action="{{ route('subscription.cancel') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="text-red-600 hover:text-red-800 font-medium">
                                    Cancel Subscription
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(session('error'))
<div class="fixed bottom-4 right-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
    <p>{{ session('error') }}</p>
</div>
@endif

@if(session('success'))
<div class="fixed bottom-4 right-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4" role="alert">
    <p>{{ session('success') }}</p>
</div>
@endif
@endsection
