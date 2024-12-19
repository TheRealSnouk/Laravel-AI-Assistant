@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h1 class="text-2xl font-bold">Billing History</h1>
                    <a href="{{ route('subscription.show') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                        Back to Subscription
                    </a>
                </div>
            </div>

            <!-- Current Subscription -->
            <div class="p-6 bg-gray-50 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-lg font-semibold">Current Plan: {{ config("subscription.plans.{$subscription->plan}.name") }}</h2>
                        <p class="text-gray-600">
                            @if($subscription->cancelled())
                                Cancelled - Ends {{ $subscription->ends_at->format('F j, Y') }}
                            @else
                                Next billing date: {{ $subscription->next_billing_date->format('F j, Y') }}
                            @endif
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold">${{ number_format(config("subscription.plans.{$subscription->plan}.price"), 2) }}</div>
                        <div class="text-sm text-gray-500">per month</div>
                    </div>
                </div>
            </div>

            <!-- Invoices -->
            <div class="divide-y divide-gray-200">
                @forelse($invoices as $invoice)
                <div class="p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="font-semibold">Invoice #{{ $invoice->number }}</div>
                            <div class="text-sm text-gray-500">{{ $invoice->date()->format('F j, Y') }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold">${{ number_format($invoice->total() / 100, 2) }}</div>
                            <div class="text-sm">
                                @if($invoice->paid)
                                    <span class="text-green-600">Paid</span>
                                @else
                                    <span class="text-red-600">Unpaid</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <!-- Invoice Items -->
                    <div class="mt-4">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                    <th class="text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($invoice->items() as $item)
                                <tr>
                                    <td class="py-2 text-sm text-gray-900">{{ $item->description }}</td>
                                    <td class="py-2 text-sm text-gray-900 text-right">${{ number_format($item->total() / 100, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Invoice Actions -->
                    <div class="mt-4 flex justify-end space-x-4">
                        <a href="{{ $invoice->invoice_pdf }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            Download PDF
                        </a>
                        @unless($invoice->paid)
                        <form action="{{ route('subscription.pay-invoice', $invoice->id) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-green-600 hover:text-green-800 text-sm font-medium">
                                Pay Now
                            </button>
                        </form>
                        @endunless
                    </div>
                </div>
                @empty
                <div class="p-6 text-center text-gray-500">
                    No billing history available
                </div>
                @endforelse
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
