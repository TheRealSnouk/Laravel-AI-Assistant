<?php

namespace App\Services\AI;

use App\Models\Subscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AIModelService
{
    /**
     * Get available AI models for subscription level
     */
    public function getAvailableModels(Subscription $subscription): array
    {
        return [
            'gpt-4' => [
                'name' => 'GPT-4',
                'description' => 'Most capable model for complex coding tasks',
                'available' => $subscription->hasFeature('gpt4'),
                'token_limit' => 8192,
                'pricing' => [
                    'input' => 0.03,
                    'output' => 0.06
                ]
            ],
            'gpt-3.5-turbo' => [
                'name' => 'GPT-3.5 Turbo',
                'description' => 'Fast and efficient for most coding tasks',
                'available' => true,
                'token_limit' => 4096,
                'pricing' => [
                    'input' => 0.0015,
                    'output' => 0.002
                ]
            ],
            'code-davinci-002' => [
                'name' => 'Codex',
                'description' => 'Specialized in code generation and analysis',
                'available' => $subscription->hasFeature('codex'),
                'token_limit' => 8000,
                'pricing' => [
                    'input' => 0.024,
                    'output' => 0.024
                ]
            ]
        ];
    }

    /**
     * Get AI completion response
     */
    public function getCompletion(string $prompt, string $model, int $maxTokens): array
    {
        $cacheKey = $this->getCacheKey($prompt, $model);
        
        return Cache::remember($cacheKey, 3600, function () use ($prompt, $model, $maxTokens) {
            $response = $this->makeApiRequest($prompt, $model, $maxTokens);
            return $this->formatResponse($response);
        });
    }

    /**
     * Make API request to AI provider
     */
    private function makeApiRequest(string $prompt, string $model, int $maxTokens): array
    {
        $apiKey = $this->getApiKey();
        $baseUrl = config('ai.api_url');

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type' => 'application/json'
        ])->post($baseUrl . '/chat/completions', [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->getSystemPrompt()
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => $maxTokens,
            'temperature' => 0.7,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0
        ]);

        if (!$response->successful()) {
            Log::error('AI API Error', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);
            throw new \Exception('Failed to get AI response');
        }

        return $response->json();
    }

    /**
     * Format API response
     */
    private function formatResponse(array $response): array
    {
        return [
            'content' => $response['choices'][0]['message']['content'],
            'usage' => $response['usage'],
            'model' => $response['model']
        ];
    }

    /**
     * Get system prompt for AI context
     */
    private function getSystemPrompt(): string
    {
        return "You are an expert coding assistant specialized in Laravel, PHP, and web development. 
                You provide clear, secure, and efficient solutions following best practices. 
                Your responses should be practical and include relevant code examples when appropriate.
                Focus on modern development practices, security, and performance optimization.";
    }

    /**
     * Get API key based on configuration
     */
    private function getApiKey(): string
    {
        $key = config('ai.api_key');
        
        if (empty($key)) {
            throw new \Exception('AI API key not configured');
        }
        
        return $key;
    }

    /**
     * Generate cache key for response
     */
    private function getCacheKey(string $prompt, string $model): string
    {
        return 'ai_response_' . md5($prompt . $model);
    }
}
