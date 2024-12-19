<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Platform Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-gray-900 text-xl font-semibold mb-2">Total Users</div>
                    <div class="text-3xl font-bold">{{ number_format($stats['total_users']) }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-gray-900 text-xl font-semibold mb-2">Active Subscriptions</div>
                    <div class="text-3xl font-bold text-green-500">{{ number_format($stats['active_subscriptions']) }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-gray-900 text-xl font-semibold mb-2">Total Revenue</div>
                    <div class="text-3xl font-bold text-blue-500">${{ number_format($stats['total_revenue'], 2) }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-gray-900 text-xl font-semibold mb-2">Pending Payments</div>
                    <div class="text-3xl font-bold text-yellow-500">{{ number_format($stats['pending_payments']) }}</div>
                </div>
            </div>

            <!-- Network Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h2 class="text-xl font-semibold mb-4">Network Performance</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Network</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Transactions</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Success Rate</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Volume</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($networkStats as $stat)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ ucfirst($stat->network) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ number_format($stat->total_transactions) }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ number_format(($stat->successful_transactions / $stat->total_transactions) * 100, 1) }}%
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">${{ number_format($stat->total_volume, 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h2 class="text-xl font-semibold mb-4">Subscription Plans</h2>
                    <canvas id="planDistribution" class="w-full" height="300"></canvas>
                </div>
            </div>

            <!-- Transaction Volume Chart -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">Daily Transaction Volume (Last 30 Days)</h2>
                <canvas id="volumeChart" class="w-full" height="300"></canvas>
            </div>

            <!-- Recent Payments -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold">Recent Payments</h2>
                    <a href="{{ route('admin.payments') }}" class="text-blue-500 hover:text-blue-700">View All</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Network</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($recentPayments as $payment)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $payment->created_at->format('Y-m-d H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="{{ route('admin.users.show', $payment->user) }}" class="text-blue-500 hover:text-blue-700">
                                        {{ $payment->user->email }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <a href="{{ route('admin.payments.show', $payment) }}" class="text-blue-500 hover:text-blue-700">
                                        {{ $payment->reference }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $payment->network_name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $payment->formatted_amount }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-{{ $payment->status_color }}-100 text-{{ $payment->status_color }}-800">
                                        {{ ucfirst($payment->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    @if($payment->status === 'pending')
                                    <button 
                                        onclick="verifyPayment('{{ $payment->id }}')"
                                        class="text-blue-500 hover:text-blue-700"
                                    >
                                        Verify
                                    </button>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Plan Distribution Chart
        new Chart(document.getElementById('planDistribution'), {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($planDistribution->pluck('plan')) !!},
                datasets: [{
                    data: {!! json_encode($planDistribution->pluck('count')) !!},
                    backgroundColor: ['#10B981', '#3B82F6', '#8B5CF6', '#EC4899']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Volume Chart
        new Chart(document.getElementById('volumeChart'), {
            type: 'line',
            data: {
                labels: {!! json_encode($dailyVolume->pluck('date')) !!},
                datasets: [{
                    label: 'Transaction Count',
                    data: {!! json_encode($dailyVolume->pluck('count')) !!},
                    borderColor: '#3B82F6',
                    tension: 0.1
                }, {
                    label: 'Volume ($)',
                    data: {!! json_encode($dailyVolume->pluck('volume')) !!},
                    borderColor: '#10B981',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Payment Verification
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
