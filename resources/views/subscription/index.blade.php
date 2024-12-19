@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="text-center mb-12">
        <h1 class="text-4xl font-bold mb-4">Choose Your Plan</h1>
        <p class="text-lg text-gray-600">Unlock powerful AI coding features with our subscription plans</p>
    </div>

    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-8">
        @foreach($plans as $planId => $plan)
        <div class="bg-white rounded-lg shadow-lg overflow-hidden {{ $currentPlan === $planId ? 'ring-2 ring-blue-500' : '' }}">
            <div class="p-6">
                <h2 class="text-2xl font-bold mb-2">{{ $plan['name'] }}</h2>
                <div class="text-3xl font-bold mb-4">
                    ${{ number_format($plan['price'], 2) }}
                    <span class="text-sm text-gray-500 font-normal">/month</span>
                </div>

                <div class="space-y-4">
                    @foreach($features as $featureId => $feature)
                        <div class="flex items-start">
                            @if($plan['features'][$featureId])
                                <svg class="w-5 h-5 text-green-500 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-gray-400 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            @endif
                            <div class="ml-3">
                                <p class="text-sm font-medium">{{ $feature['name'] }}</p>
                                <p class="text-xs text-gray-500">{{ $feature['description'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-8">
                    @if($currentPlan === $planId)
                        <button disabled class="w-full bg-gray-100 text-gray-600 py-3 rounded-md font-semibold">
                            Current Plan
                        </button>
                    @else
                        <form action="{{ route('subscription.change-plan') }}" method="POST">
                            @csrf
                            <input type="hidden" name="plan" value="{{ $planId }}">
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-md font-semibold transition duration-150">
                                {{ $plan['price'] > 0 ? 'Upgrade Now' : 'Switch to Free' }}
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            @if($plan['limits'])
            <div class="bg-gray-50 px-6 py-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">Plan Limits</h3>
                <ul class="space-y-2 text-sm text-gray-600">
                    @foreach($plan['limits'] as $limitType => $limit)
                        <li class="flex justify-between">
                            <span>{{ ucwords(str_replace('_', ' ', $limitType)) }}</span>
                            <span class="font-medium">{{ $limit === -1 ? 'Unlimited' : number_format($limit) }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
        @endforeach
    </div>

    @if($currentPlan !== 'free')
    <div class="mt-12 text-center">
        <p class="text-gray-600 mb-4">Need to cancel your subscription?</p>
        <form action="{{ route('subscription.cancel') }}" method="POST" class="inline">
            @csrf
            <button type="submit" class="text-red-600 hover:text-red-800 font-medium">
                Cancel Subscription
            </button>
        </form>
    </div>
    @endif
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
