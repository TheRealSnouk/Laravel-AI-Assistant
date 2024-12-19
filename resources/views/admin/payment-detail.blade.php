<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Payment Overview -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">Payment Details</h2>
                        <div class="grid grid-cols-2 gap-x-8 gap-y-4">
                            <div>
                                <div class="text-sm font-medium text-gray-500">Reference</div>
                                <div class="mt-1 text-sm text-gray-900">{{ $payment->reference }}</div>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-500">Status</div>
                                <div class="mt-1">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-{{ $payment->status_color }}-100 text-{{ $payment->status_color }}-800">
                                        {{ ucfirst($payment->status) }}
                                    </span>
                                </div>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-500">Amount</div>
                                <div class="mt-1 text-sm text-gray-900">{{ $payment->formatted_amount }}</div>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-500">Network</div>
                                <div class="mt-1 text-sm text-gray-900">{{ $payment->network_name }}</div>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-500">Created At</div>
                                <div class="mt-1 text-sm text-gray-900">{{ $payment->created_at->format('Y-m-d H:i:s') }}</div>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-500">Updated At</div>
                                <div class="mt-1 text-sm text-gray-900">{{ $payment->updated_at->format('Y-m-d H:i:s') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="flex space-x-3">
                        @if($payment->transaction_hash)
                        <a href="{{ $payment->explorer_url }}" target="_blank"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            View in Explorer
                        </a>
                        @endif
                        @if($payment->status === 'pending')
                        <button onclick="verifyPayment('{{ $payment->id }}')"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            Verify Payment
                        </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- User Information -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">User Information</h3>
                <div class="grid grid-cols-2 gap-x-8 gap-y-4">
                    <div>
                        <div class="text-sm font-medium text-gray-500">Name</div>
                        <div class="mt-1 text-sm text-gray-900">{{ $payment->user->name }}</div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">Email</div>
                        <div class="mt-1 text-sm text-gray-900">
                            <a href="{{ route('admin.users.show', $payment->user) }}" class="text-blue-500 hover:text-blue-700">
                                {{ $payment->user->email }}
                            </a>
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">Subscription Status</div>
                        <div class="mt-1">
                            @if($payment->user->subscription)
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $payment->user->subscription->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ ucfirst($payment->user->subscription->status) }}
                            </span>
                            @else
                            <span class="text-sm text-gray-500">No subscription</span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">Total Payments</div>
                        <div class="mt-1 text-sm text-gray-900">
                            {{ number_format($payment->user->completed_payments_count) }} completed,
                            ${{ number_format($payment->user->total_spent, 2) }} total
                        </div>
                    </div>
                </div>
            </div>

            <!-- Transaction Details -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Transaction Details</h3>
                <div class="grid grid-cols-2 gap-x-8 gap-y-4">
                    <div>
                        <div class="text-sm font-medium text-gray-500">Transaction Hash</div>
                        <div class="mt-1 text-sm text-gray-900 break-all">
                            {{ $payment->transaction_hash ?: 'Not available' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">From Address</div>
                        <div class="mt-1 text-sm text-gray-900 break-all">
                            {{ $payment->from_address ?: 'Not available' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">To Address</div>
                        <div class="mt-1 text-sm text-gray-900 break-all">
                            {{ $payment->to_address ?: 'Not available' }}
                        </div>
                    </div>
                    <div>
                        <div class="text-sm font-medium text-gray-500">Token ID</div>
                        <div class="mt-1 text-sm text-gray-900">
                            {{ $payment->token_id ?: 'Not available' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Events -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Payment Events</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($payment->events as $event)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $event->created_at->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ ucfirst($event->event) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ $event->description }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    @if($event->data)
                                    <pre class="text-xs">{{ json_encode($event->data, JSON_PRETTY_PRINT) }}</pre>
                                    @else
                                    -
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function verifyPayment(paymentId) {
            fetch(`/admin/payments/${paymentId}/verify`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.verified) {
                        window.location.reload();
                    } else {
                        alert('Payment not verified yet. Please try again later.');
                    }
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to verify payment. Please try again.');
            });
        }
    </script>
    @endpush
</x-app-layout>
