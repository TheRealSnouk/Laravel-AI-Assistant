<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LaravelAssistant;
use Illuminate\Support\Facades\Cache;

class AssistantController extends Controller
{
    private $assistant;

    public function __construct(LaravelAssistant $assistant)
    {
        $this->assistant = $assistant;
    }

    public function dashboard()
    {
        // Get metrics
        $metrics = $this->getMetrics();

        // Get chart data
        $charts = $this->getChartData();

        // Get API routes
        $routes = $this->getApiRoutes();

        return view('assistant.dashboard', compact('metrics', 'charts', 'routes'));
    }

    private function getMetrics()
    {
        return [
            'total_requests' => Cache::get('metrics:total_requests', 0),
            'avg_response_time' => Cache::get('metrics:avg_response_time', 0),
            'error_rate' => Cache::get('metrics:error_rate', '0%'),
            'uptime' => Cache::get('metrics:uptime', '100%'),
        ];
    }

    private function getChartData()
    {
        // Get the last 24 hours of data
        $hours = range(0, 23);
        $now = now();

        $requestData = array_map(function($hour) use ($now) {
            $time = $now->copy()->subHours($hour);
            return Cache::get("metrics:requests:{$time->format('Y-m-d-H')}", 0);
        }, $hours);

        $responseData = array_map(function($hour) use ($now) {
            $time = $now->copy()->subHours($hour);
            return Cache::get("metrics:response_time:{$time->format('Y-m-d-H')}", 0);
        }, $hours);

        $labels = array_map(function($hour) use ($now) {
            return $now->copy()->subHours($hour)->format('H:i');
        }, $hours);

        return [
            'requests' => [
                'labels' => array_reverse($labels),
                'data' => array_reverse($requestData),
            ],
            'response_times' => [
                'labels' => array_reverse($labels),
                'data' => array_reverse($responseData),
            ],
        ];
    }

    private function getApiRoutes()
    {
        return Cache::remember('api:routes', 3600, function() {
            $routes = [];
            foreach (app('router')->getRoutes() as $route) {
                if (str_starts_with($route->uri(), 'api/')) {
                    $routes[] = [
                        'method' => implode('|', $route->methods()),
                        'path' => $route->uri(),
                        'description' => $this->assistant->generateRouteDescription($route),
                        'version' => $this->assistant->extractVersion($route),
                        'deprecated' => $this->assistant->isDeprecated($route),
                    ];
                }
            }
            return $routes;
        });
    }

    public function generateClient(Request $request)
    {
        $language = $request->input('language', 'python');
        $routes = $this->getApiRoutes();
        
        $client = $this->assistant->generateClientLibraries($routes)[$language];
        
        return response()->json([
            'success' => true,
            'client' => $client,
        ]);
    }

    public function analyzePerformance(Request $request)
    {
        $routes = $this->getApiRoutes();
        $results = $this->assistant->runPerformanceBenchmarks($routes);
        
        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }

    public function analyzeVersioning(Request $request)
    {
        $routes = $this->getApiRoutes();
        $analysis = $this->assistant->analyzeApiVersioning($routes);
        
        return response()->json([
            'success' => true,
            'analysis' => $analysis,
        ]);
    }
}
