<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AIAssistantController extends Controller
{
    protected function getHeaders()
    {
        return [
            'Authorization' => 'Bearer ' . trim(config('openai.api_key')),
            'HTTP-Referer' => config('app.url', 'http://localhost'),
            'X-Title' => 'Laravel AI Assistant',
            'Content-Type' => 'application/json',
        ];
    }

    protected function makeRequest($messages, $maxTokens = 1000)
    {
        $response = Http::withHeaders($this->getHeaders())
            ->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => config('openai.model', 'anthropic/claude-2'),
                'messages' => $messages,
                'max_tokens' => $maxTokens
            ]);

        if (!$response->successful()) {
            throw new \Exception($response->json('error.message', 'API request failed'));
        }

        $responseData = $response->json();
        if (!isset($responseData['choices'][0]['message']['content'])) {
            throw new \Exception('Unexpected response structure from API');
        }

        return $responseData;
    }

    public function index()
    {
        return view('assistant.chat');
    }

    public function testConnection()
    {
        try {
            $responseData = $this->makeRequest([
                ['role' => 'user', 'content' => 'Say "Connection successful!"']
            ], 150);

            return response()->json([
                'message' => $responseData['choices'][0]['message']['content'],
                'status' => 'success',
                'model' => $responseData['model']
            ]);

        } catch (\Exception $e) {
            \Log::error('Test Connection Error: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    public function chat(Request $request)
    {
        try {
            $request->validate(['message' => 'required|string']);

            $responseData = $this->makeRequest([
                ['role' => 'system', 'content' => 'You are a helpful AI coding assistant with expertise in PHP, Laravel, and web development.'],
                ['role' => 'user', 'content' => $request->message]
            ]);

            return response()->json([
                'message' => $responseData['choices'][0]['message']['content'],
                'status' => 'success'
            ]);

        } catch (\Exception $e) {
            \Log::error('Chat Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error processing your request: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }
}
