<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-gray-900 text-xl font-semibold mb-2">Total Payments</div>
                    <div class="text-3xl font-bold">{{ $stats['total_payments'] }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-gray-900 text-xl font-semibold mb-2">Total Amount</div>
                    <div class="text-3xl font-bold">{{ number_format($stats['total_amount'], 2) }} USDT</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-gray-900 text-xl font-semibold mb-2">Pending Payments</div>
                    <div class="text-3xl font-bold text-yellow-500">{{ $stats['pending_payments'] }}</div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-gray-900 text-xl font-semibold mb-2">Success Rate</div>
                    <div class="text-3xl font-bold text-green-500">
                        {{ $stats['total_payments'] ? number_format(($paymentsByStatus['completed'] ?? 0) / $stats['total_payments'] * 100, 1) : 0 }}%
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                <!-- Payment Status Chart -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h2 class="text-xl font-semibold mb-4">Payment Status Distribution</h2>
                    <canvas id="statusChart" class="w-full" height="300"></canvas>
                </div>

                <!-- Network Distribution Chart -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h2 class="text-xl font-semibold mb-4">Network Distribution</h2>
                    <canvas id="networkChart" class="w-full" height="300"></canvas>
                </div>
            </div>

            <!-- Monthly Trends Chart -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-8">
                <h2 class="text-xl font-semibold mb-4">Monthly Payment Trends</h2>
                <canvas id="trendChart" class="w-full" height="300"></canvas>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold">Recent Activity</h2>
                    <a href="{{ route('dashboard.transactions') }}" class="text-blue-500 hover:text-blue-700">View All</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Network</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($stats['recent_activity'] as $payment)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $payment->created_at->format('Y-m-d H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <a href="{{ route('dashboard.payment.show', $payment) }}" class="text-blue-500 hover:text-blue-700">
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
        // Status Distribution Chart
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: {!! json_encode(array_keys($paymentsByStatus)) !!},
                datasets: [{
                    data: {!! json_encode(array_values($paymentsByStatus)) !!},
                    backgroundColor: ['#10B981', '#F59E0B', '#EF4444', '#6B7280']
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

        // Network Distribution Chart
        new Chart(document.getElementById('networkChart'), {
            type: 'pie',
            data: {
                labels: {!! json_encode(array_keys($paymentsByNetwork)) !!},
                datasets: [{
                    data: {!! json_encode(array_values($paymentsByNetwork)) !!},
                    backgroundColor: ['#3B82F6', '#8B5CF6']
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

        // Monthly Trends Chart
        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: {!! json_encode($monthlyTrends->pluck('month')) !!},
                datasets: [{
                    label: 'Number of Payments',
                    data: {!! json_encode($monthlyTrends->pluck('count')) !!},
                    borderColor: '#3B82F6',
                    tension: 0.1
                }, {
                    label: 'Total Amount (USDT)',
                    data: {!! json_encode($monthlyTrends->pluck('total_amount')) !!},
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
    </script>
    @endpush
</x-app-layout>
