@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-lg mx-auto">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-4 bg-blue-600 text-white">
                <h1 class="text-2xl font-bold">Subscribe to {{ $plan['name'] }}</h1>
            </div>

            <!-- Plan Details -->
            <div class="p-6 bg-gray-50 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-600">Monthly Subscription</p>
                        <p class="text-sm text-gray-500">Cancel anytime</p>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold">${{ number_format($plan['price'], 2) }}</div>
                        <div class="text-sm text-gray-500">per month</div>
                    </div>
                </div>
            </div>

            <!-- Payment Form -->
            <div class="p-6">
                <form id="payment-form" action="{{ route('subscription.process-payment') }}" method="POST">
                    @csrf
                    <input type="hidden" name="plan" value="{{ request('plan') }}">

                    <!-- Card Element -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Card Information
                        </label>
                        <div id="card-element" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <!-- Stripe Card Element -->
                        </div>
                        <div id="card-errors" class="mt-2 text-sm text-red-600" role="alert"></div>
                    </div>

                    <!-- Terms -->
                    <div class="mb-6">
                        <label class="flex items-center">
                            <input type="checkbox" required class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-600">
                                I agree to the <a href="#" class="text-blue-600 hover:text-blue-800">Terms of Service</a>
                                and <a href="#" class="text-blue-600 hover:text-blue-800">Privacy Policy</a>
                            </span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" id="submit-button" class="w-full bg-blue-600 text-white py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Subscribe Now
                    </button>
                </form>

                <p class="mt-4 text-sm text-gray-500 text-center">
                    Protected by <span class="font-medium">Stripe</span> secure payment processing
                </p>
            </div>
        </div>

        <!-- Back Link -->
        <div class="mt-4 text-center">
            <a href="{{ route('subscription.index') }}" class="text-sm text-gray-600 hover:text-gray-800">
                ← Back to Plans
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
    const stripe = Stripe('{{ config('services.stripe.key') }}');
    const elements = stripe.elements();
    const card = elements.create('card');
    
    card.mount('#card-element');

    const form = document.getElementById('payment-form');
    const submitButton = document.getElementById('submit-button');
    const errorElement = document.getElementById('card-errors');

    card.addEventListener('change', function(event) {
        if (event.error) {
            errorElement.textContent = event.error.message;
        } else {
            errorElement.textContent = '';
        }
    });

    form.addEventListener('submit', async function(event) {
        event.preventDefault();
        submitButton.disabled = true;
        submitButton.textContent = 'Processing...';

        const {setupIntent, error} = await stripe.confirmCardSetup(
            '{{ $intent->client_secret }}', {
                payment_method: {
                    card: card,
                    billing_details: {
                        name: '{{ auth()->user()->name }}'
                    }
                }
            }
        );

        if (error) {
            errorElement.textContent = error.message;
            submitButton.disabled = false;
            submitButton.textContent = 'Subscribe Now';
        } else {
            const paymentMethod = setupIntent.payment_method;
            
            // Add payment method to form
            const hiddenInput = document.createElement('input');
            hiddenInput.setAttribute('type', 'hidden');
            hiddenInput.setAttribute('name', 'payment_method');
            hiddenInput.setAttribute('value', paymentMethod);
            form.appendChild(hiddenInput);

            // Submit form
            fetch(form.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    plan: form.querySelector('[name="plan"]').value,
                    payment_method: paymentMethod
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    throw new Error(data.message || 'Payment failed');
                }
            })
            .catch(error => {
                errorElement.textContent = error.message;
                submitButton.disabled = false;
                submitButton.textContent = 'Subscribe Now';
            });
        }
    });
</script>
@endpush

@if(session('error'))
<div class="fixed bottom-4 right-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert">
    <p>{{ session('error') }}</p>
</div>
@endif
@endsection
