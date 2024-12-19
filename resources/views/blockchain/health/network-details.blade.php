<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-semibold text-gray-900">
                    {{ ucfirst($network) }} Network Details
                </h2>
                <a href="{{ route('blockchain.health.dashboard') }}" class="text-indigo-600 hover:text-indigo-900">
                    ← Back to Dashboard
                </a>
            </div>

            <!-- Current Status -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            @if($health['healthy'])
                                <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                                    <svg class="h-8 w-8 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            @else
                                <div class="h-12 w-12 rounded-full bg-red-100 flex items-center justify-center">
                                    <svg class="h-8 w-8 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            @endif
                        </div>
                        <div class="ml-5">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">
                                Current Status: {{ $health['healthy'] ? 'Healthy' : 'Issues Detected' }}
                            </h3>
                            <div class="mt-2 text-sm text-gray-500">
                                Last updated: {{ $lastUpdated->diffForHumans() }}
                            </div>
                        </div>
                    </div>

                    @if(!$health['healthy'])
                        <div class="mt-4">
                            <h4 class="text-sm font-medium text-gray-900">Current Issues:</h4>
                            <ul class="mt-2 list-disc pl-5 space-y-1 text-sm text-red-600">
                                @foreach($health['issues'] as $issue)
                                    <li>{{ $issue }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Network Configuration -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        Network Configuration
                    </h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @foreach($config as $key => $value)
                            @if(!in_array($key, ['operator_key', 'private_key']))
                                <div class="sm:col-span-1">
                                    <dt class="text-sm font-medium text-gray-500">
                                        {{ ucwords(str_replace('_', ' ', $key)) }}
                                    </dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ is_array($value) ? json_encode($value) : $value }}
                                    </dd>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Gas Price History -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        Gas Price History
                    </h3>
                    <div class="h-64" id="gasPriceChart"></div>
                </div>
            </div>

            <!-- Response Time History -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        Response Time History
                    </h3>
                    <div class="h-64" id="responseTimeChart"></div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const historicalData = @json($historical);
            
            // Gas Price Chart
            const gasPriceData = historicalData
                .filter(entry => entry.gasPrice !== null)
                .map(entry => ({
                    x: new Date(entry.timestamp).getTime(),
                    y: entry.gasPrice
                }));

            const gasPriceOptions = {
                series: [{
                    name: 'Gas Price',
                    data: gasPriceData
                }],
                chart: {
                    type: 'line',
                    height: 250,
                    animations: {
                        enabled: false
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                xaxis: {
                    type: 'datetime'
                },
                yaxis: {
                    title: {
                        text: '{{ $gasConfig['price_unit'] }}'
                    }
                },
                tooltip: {
                    x: {
                        format: 'dd MMM yyyy HH:mm'
                    }
                }
            };

            const gasPriceChart = new ApexCharts(document.querySelector("#gasPriceChart"), gasPriceOptions);
            gasPriceChart.render();

            // Response Time Chart
            const responseTimeData = historicalData
                .filter(entry => entry.responseTime !== null)
                .map(entry => ({
                    x: new Date(entry.timestamp).getTime(),
                    y: entry.responseTime
                }));

            const responseTimeOptions = {
                series: [{
                    name: 'Response Time',
                    data: responseTimeData
                }],
                chart: {
                    type: 'line',
                    height: 250,
                    animations: {
                        enabled: false
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: 2
                },
                xaxis: {
                    type: 'datetime'
                },
                yaxis: {
                    title: {
                        text: 'Seconds'
                    }
                },
                tooltip: {
                    x: {
                        format: 'dd MMM yyyy HH:mm'
                    }
                }
            };

            const responseTimeChart = new ApexCharts(document.querySelector("#responseTimeChart"), responseTimeOptions);
            responseTimeChart.render();

            // Auto-refresh every minute
            setInterval(() => {
                fetch(`/blockchain/health/${@json($network)}/refresh`)
                    .then(response => response.json())
                    .then(data => {
                        location.reload();
                    });
            }, 60000);
        });
    </script>
    @endpush
</x-app-layout>
