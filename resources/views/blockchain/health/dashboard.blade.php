<x-app-layout>
    <div class="py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">
                Blockchain Network Health
            </h2>

            <!-- Network Status Overview -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                @foreach ($networks as $network)
                    <div class="bg-white overflow-hidden shadow rounded-lg">
                        <div class="p-5">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    @if($healthData[$network]['healthy'])
                                        <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center">
                                            <svg class="h-5 w-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    @else
                                        <div class="h-8 w-8 rounded-full bg-red-100 flex items-center justify-center">
                                            <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                <div class="ml-5 w-0 flex-1">
                                    <dl>
                                        <dt class="text-sm font-medium text-gray-500 truncate">
                                            {{ ucfirst($network) }}
                                        </dt>
                                        <dd class="flex items-baseline">
                                            <div class="text-lg font-semibold text-gray-900">
                                                {{ $healthData[$network]['healthy'] ? 'Healthy' : 'Issues Detected' }}
                                            </div>
                                        </dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-5 py-3">
                            <div class="text-sm">
                                <a href="{{ route('blockchain.health.network', $network) }}" class="font-medium text-indigo-600 hover:text-indigo-900">
                                    View details
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Recent Alerts -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        Recent Alerts
                    </h3>
                    @if(count($alerts) > 0)
                        <div class="flow-root">
                            <ul role="list" class="-mb-8">
                                @foreach($alerts as $alert)
                                    <li>
                                        <div class="relative pb-8">
                                            @if(!$loop->last)
                                                <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                            @endif
                                            <div class="relative flex space-x-3">
                                                <div>
                                                    <span class="h-8 w-8 rounded-full bg-red-100 flex items-center justify-center ring-8 ring-white">
                                                        <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                        </svg>
                                                    </span>
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <div>
                                                        <div class="text-sm text-gray-500">
                                                            <span class="font-medium text-gray-900">{{ ucfirst($alert['network']) }}</span>
                                                            <span class="ml-2 text-gray-500">{{ Carbon\Carbon::parse($alert['timestamp'])->diffForHumans() }}</span>
                                                        </div>
                                                    </div>
                                                    <div class="mt-2 text-sm text-gray-700">
                                                        <ul class="list-disc pl-5 space-y-1">
                                                            @foreach($alert['issues'] as $issue)
                                                                <li>{{ $issue }}</li>
                                                            @endforeach
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No recent alerts</p>
                    @endif
                </div>
            </div>

            <!-- Historical Charts -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        Network Health History
                    </h3>
                    <div class="h-96" id="healthChart"></div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const historicalData = @json($historicalData);
            const networks = @json($networks);
            
            // Prepare data series for each network
            const series = networks.map(network => ({
                name: network,
                data: historicalData[network].map(entry => ({
                    x: new Date(entry.timestamp).getTime(),
                    y: entry.status
                }))
            }));

            const options = {
                series: series,
                chart: {
                    type: 'line',
                    height: 350,
                    animations: {
                        enabled: false
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    curve: 'straight',
                    width: 2
                },
                xaxis: {
                    type: 'datetime'
                },
                yaxis: {
                    min: 0,
                    max: 1,
                    tickAmount: 1,
                    labels: {
                        formatter: function(val) {
                            return val === 1 ? 'Healthy' : 'Unhealthy';
                        }
                    }
                },
                tooltip: {
                    x: {
                        format: 'dd MMM yyyy HH:mm'
                    }
                },
                legend: {
                    position: 'top'
                }
            };

            const chart = new ApexCharts(document.querySelector("#healthChart"), options);
            chart.render();

            // Auto-refresh every 5 minutes
            setInterval(() => {
                networks.forEach(network => {
                    fetch(`/blockchain/health/${network}/refresh`)
                        .then(response => response.json())
                        .then(data => {
                            // Update status cards
                            const statusCard = document.querySelector(`[data-network="${network}"]`);
                            if (statusCard) {
                                statusCard.querySelector('.status').textContent = data.status;
                                statusCard.querySelector('.last-updated').textContent = 
                                    `Last updated: ${new Date(data.lastUpdated).toLocaleString()}`;
                            }
                        });
                });
            }, 300000);
        });
    </script>
    @endpush
</x-app-layout>
