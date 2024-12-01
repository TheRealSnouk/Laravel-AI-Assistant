<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

class OpenRouterService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://openrouter.ai/api/v1';
    protected int $maxRetries = 3;
    protected int $baseDelay = 1000;

    public function __construct()
    {
        $this->apiKey = config('services.openrouter.key');
        if (empty($this->apiKey)) {
            throw new \RuntimeException('OpenRouter API key is not configured');
        }
    }

    private function makeRequest($endpoint, $data, $stream = false)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'HTTP-Referer' => config('app.url'),
                'X-Title' => config('app.name'),
            ])->post($endpoint, $data);

            if (!$response->successful()) {
                Log::error('OpenRouter API error', [
                    'status' => $response->status(),
                    'body' => $response->json()
                ]);
                throw new Exception('Failed to get response from AI service');
            }

            return $response->json();
        } catch (Exception $e) {
            Log::error('OpenRouter service error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function chat(string $message, array $context = [], string $model = 'anthropic/claude-2'): Response
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->maxRetries) {
            try {
                Log::info('Attempting OpenRouter API call', [
                    'attempt' => $attempt + 1,
                    'model' => $model,
                    'message_length' => strlen($message)
                ]);

                $endpoint = $this->baseUrl . '/chat/completions';
                
                $data = [
                    'model' => $model,
                    'messages' => array_merge($context, [
                        ['role' => 'user', 'content' => $message]
                    ]),
                    'stream' => false,
                    'temperature' => 0.7,
                    'max_tokens' => 500
                ];

                $response = $this->makeRequest($endpoint, $data);

                if ($response['status'] === 'success') {
                    Log::info('OpenRouter API success', [
                        'model' => $model,
                        'tokens' => $response['usage']['total_tokens'] ?? 0
                    ]);

                    // Convert to streaming format for compatibility
                    $streamContent = "data: " . json_encode([
                        'choices' => [
                            [
                                'delta' => [
                                    'content' => $response['choices'][0]['message']['content']
                                ]
                            ]
                        ]
                    ]) . "\n\ndata: [DONE]\n\n";

                    return new Response(new \GuzzleHttp\Psr7\Response(
                        200,
                        ['Content-Type' => 'text/event-stream'],
                        $streamContent
                    ));
                }

                if ($response['status'] === 429) {
                    Log::warning('Rate limited by OpenRouter API', [
                        'attempt' => $attempt + 1,
                        'model' => $model
                    ]);
                    $this->sleep(pow(2, $attempt) * 1000);
                    continue;
                }

                if ($response['status'] === 503 || str_contains($response['message'], 'overloaded')) {
                    Log::warning('Service overloaded, trying with different model', [
                        'attempt' => $attempt + 1,
                        'current_model' => $model
                    ]);
                    $model = $this->getFallbackModel($model);
                    continue;
                }

                Log::error('OpenRouter API error', [
                    'status' => $response['status'],
                    'body' => $response['message'],
                    'attempt' => $attempt + 1
                ]);

                throw new \RuntimeException("API Error (HTTP {$response['status']}): " . $response['message']);

            } catch (\Exception $e) {
                $lastException = $e;
                Log::warning('OpenRouter API attempt failed', [
                    'attempt' => $attempt + 1,
                    'message' => $e->getMessage(),
                    'model' => $model
                ]);

                $delay = min($this->baseDelay * pow(2, $attempt), 5000);
                $this->sleep($delay);
                $attempt++;
            }
        }

        Log::error('OpenRouter API failed after max retries', [
            'message' => $lastException ? $lastException->getMessage() : 'Unknown error',
            'last_model' => $model
        ]);

        throw new \RuntimeException(
            'Failed to get response after ' . $this->maxRetries . ' attempts: ' . 
            ($lastException ? $lastException->getMessage() : 'Unknown error')
        );
    }

    protected function getFallbackModel(string $currentModel): string
    {
        $models = [
            'anthropic/claude-2',
            'google/palm-2-chat-bison',
            'meta-llama/llama-2-70b-chat',
            'mistralai/mistral-7b-instruct'
        ];

        $currentIndex = array_search($currentModel, $models);
        if ($currentIndex === false || $currentIndex >= count($models) - 1) {
            return $models[0];
        }

        return $models[$currentIndex + 1];
    }

    protected function sleep(int $milliseconds): void
    {
        usleep($milliseconds * 1000);
    }

    public function streamResponse(Response $response): \Generator
    {
        try {
            $buffer = '';
            
            foreach ($response->toPsrResponse()->getBody() as $chunk) {
                $buffer .= $chunk;
                
                if (str_contains($chunk, "data: ")) {
                    $lines = explode("\n", $buffer);
                    $buffer = '';
                    
                    foreach ($lines as $line) {
                        if (str_starts_with($line, 'data: ')) {
                            $data = substr($line, 6);
                            if ($data === '[DONE]') {
                                return;
                            }
                            
                            $decoded = json_decode($data, true);
                            if (isset($decoded['choices'][0]['delta']['content'])) {
                                yield $decoded['choices'][0]['delta']['content'];
                            } elseif (isset($decoded['error'])) {
                                Log::error('OpenRouter streaming error', [
                                    'error' => $decoded['error'],
                                    'data' => $data
                                ]);
                                throw new \RuntimeException('Error in stream: ' . ($decoded['error']['message'] ?? 'Unknown error'));
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Stream processing error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
