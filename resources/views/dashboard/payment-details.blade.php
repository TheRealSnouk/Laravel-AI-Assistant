<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
                <a href="{{ route('dashboard.payments') }}" class="text-blue-500 hover:text-blue-700">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Dashboard
                </a>
            </div>

            <!-- Payment Status -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-2xl font-semibold mb-2">Payment Details</h2>
                        <p class="text-gray-600">Reference: {{ $payment->reference }}</p>
                    </div>
                    <span class="px-4 py-2 rounded-full text-sm font-semibold bg-{{ $payment->status_color }}-100 text-{{ $payment->status_color }}-800">
                        {{ ucfirst($payment->status) }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Transaction Details -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">Transaction Details</h3>
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Amount</dt>
                            <dd class="mt-1 text-lg font-semibold">{{ $payment->formatted_amount }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Network</dt>
                            <dd class="mt-1">{{ $payment->network_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Token ID</dt>
                            <dd class="mt-1 font-mono text-sm">{{ $payment->token_id }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Created</dt>
                            <dd class="mt-1">{{ $payment->created_at->format('Y-m-d H:i:s') }}</dd>
                        </div>
                        @if($payment->paid_at)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Paid At</dt>
                            <dd class="mt-1">{{ $payment->paid_at->format('Y-m-d H:i:s') }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>

                <!-- Blockchain Details -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold mb-4">Blockchain Details</h3>
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Recipient Address</dt>
                            <dd class="mt-1 font-mono text-sm break-all">{{ $payment->recipient_address }}</dd>
                        </div>
                        @if($payment->sender_address)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Sender Address</dt>
                            <dd class="mt-1 font-mono text-sm break-all">{{ $payment->sender_address }}</dd>
                        </div>
                        @endif
                        @if($payment->transaction_hash)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Transaction Hash</dt>
                            <dd class="mt-1 font-mono text-sm break-all">
                                <a href="{{ $payment->explorer_url }}" target="_blank" class="text-blue-500 hover:text-blue-700">
                                    {{ $payment->transaction_hash }}
                                    <i class="fas fa-external-link-alt ml-1"></i>
                                </a>
                            </dd>
                        </div>
                        @endif
                        @if($payment->memo)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Memo</dt>
                            <dd class="mt-1">{{ $payment->memo }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            <!-- Verification Status -->
            @if($payment->status === 'pending')
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
                <h3 class="text-lg font-semibold mb-4">Payment Verification</h3>
                <div class="flex items-center">
                    <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-500 mr-3"></div>
                    <p class="text-gray-600">
                        Checking payment status... This may take a few moments.
                    </p>
                </div>
                <div class="mt-4">
                    <p class="text-sm text-gray-500">
                        Transaction verification can take several minutes. You'll receive a notification once the payment is confirmed.
                    </p>
                </div>
            </div>
            @endif

            <!-- Additional Details -->
            @if($payment->payment_details)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mt-6">
                <h3 class="text-lg font-semibold mb-4">Additional Details</h3>
                <pre class="bg-gray-50 p-4 rounded-lg overflow-x-auto">
                    <code>{{ json_encode($payment->payment_details, JSON_PRETTY_PRINT) }}</code>
                </pre>
            </div>
            @endif
        </div>
    </div>

    @if($payment->status === 'pending')
    @push('scripts')
    <script>
        function checkPaymentStatus() {
            fetch(`/api/payments/{{ $payment->reference }}/status`)
                .then(response => response.json())
                .then(data => {
                    if (data.status !== 'pending') {
                        window.location.reload();
                    }
                });
        }

        // Check status every 10 seconds
        setInterval(checkPaymentStatus, 10000);
    </script>
    @endpush
    @endif
</x-app-layout>
