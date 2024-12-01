<?php

namespace App\Services;

use App\Models\ChatMessage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AIService
{
    protected OpenRouterService $openRouter;
    protected int $maxRetries = 3;
    protected int $baseDelay = 1000; // Base delay in milliseconds

    public function __construct(OpenRouterService $openRouter)
    {
        $this->openRouter = $openRouter;
    }

    public function processMessage(string $message): array
    {
        try {
            Log::info('Processing new message', [
                'message_length' => strlen($message),
                'user_id' => Auth::id()
            ]);

            // Get recent context
            $context = $this->getRecentContext();
            
            // Create chat message record
            $chatMessage = ChatMessage::create([
                'user_id' => Auth::id(),
                'message' => $message,
                'context' => $context
            ]);

            Log::info('Created chat message record', [
                'chat_message_id' => $chatMessage->id,
                'context_count' => count($context)
            ]);

            // Get response from OpenRouter with retries
            $attempt = 0;
            $lastException = null;

            while ($attempt < $this->maxRetries) {
                try {
                    $response = $this->openRouter->chat($message, $context);
                    
                    Log::info('Successfully got AI response', [
                        'chat_message_id' => $chatMessage->id,
                        'attempt' => $attempt + 1
                    ]);

                    return [
                        'message_id' => $chatMessage->id,
                        'stream_url' => route('chat.stream', $chatMessage->id)
                    ];
                } catch (\Exception $e) {
                    $lastException = $e;
                    Log::warning('AI processing attempt failed', [
                        'attempt' => $attempt + 1,
                        'message' => $e->getMessage(),
                        'chat_message_id' => $chatMessage->id,
                    ]);

                    if (str_contains($e->getMessage(), 'overloaded')) {
                        // For overloaded errors, wait longer
                        usleep(($this->baseDelay * pow(2, $attempt)) * 1000);
                    }

                    $attempt++;
                    if ($attempt >= $this->maxRetries) {
                        break;
                    }
                }
            }

            // If we get here, all retries failed
            Log::error('AI processing failed after max retries', [
                'message' => $lastException ? $lastException->getMessage() : 'Unknown error',
                'chat_message_id' => $chatMessage->id,
            ]);

            // Update the chat message with the error
            $chatMessage->update([
                'error' => $lastException ? $lastException->getMessage() : 'Failed to process message after max retries'
            ]);

            throw $lastException ?? new \RuntimeException('Failed to process message after max retries');

        } catch (\Exception $e) {
            Log::error('Error processing message', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    protected function getRecentContext(): array
    {
        try {
            $recentMessages = ChatMessage::where('user_id', Auth::id())
                ->whereNull('error') // Only include successful messages
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
                ->reverse();

            $context = [];
            foreach ($recentMessages as $message) {
                $context[] = ['role' => 'user', 'content' => $message->message];
                if ($message->response) {
                    $context[] = ['role' => 'assistant', 'content' => $message->response];
                }
            }

            Log::debug('Retrieved context', [
                'message_count' => count($recentMessages),
                'context_items' => count($context)
            ]);

            return $context;
        } catch (\Exception $e) {
            Log::error('Error getting context', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return [];
        }
    }

    public function streamResponse(ChatMessage $chatMessage)
    {
        try {
            Log::info('Starting stream response', [
                'chat_message_id' => $chatMessage->id
            ]);

            $attempt = 0;
            $lastException = null;

            while ($attempt < $this->maxRetries) {
                try {
                    $response = $this->openRouter->chat(
                        $chatMessage->message,
                        $chatMessage->context ?? []
                    );

                    Log::info('Got initial stream response', [
                        'chat_message_id' => $chatMessage->id,
                        'attempt' => $attempt + 1
                    ]);

                    $fullResponse = '';
                    foreach ($this->openRouter->streamResponse($response) as $chunk) {
                        $fullResponse .= $chunk;
                        yield $chunk;
                    }

                    $chatMessage->update([
                        'response' => $fullResponse,
                        'error' => null
                    ]);

                    Log::info('Successfully completed stream', [
                        'chat_message_id' => $chatMessage->id,
                        'response_length' => strlen($fullResponse)
                    ]);

                    return;

                } catch (\Exception $e) {
                    $lastException = $e;
                    Log::warning('Stream response attempt failed', [
                        'attempt' => $attempt + 1,
                        'message' => $e->getMessage(),
                        'chat_message_id' => $chatMessage->id,
                    ]);

                    if (str_contains($e->getMessage(), 'overloaded')) {
                        // For overloaded errors, wait longer
                        usleep(($this->baseDelay * pow(2, $attempt)) * 1000);
                    }

                    $attempt++;
                    if ($attempt >= $this->maxRetries) {
                        break;
                    }
                }
            }

            // If we get here, all retries failed
            Log::error('Stream response failed after max retries', [
                'message' => $lastException ? $lastException->getMessage() : 'Unknown error',
                'chat_message_id' => $chatMessage->id,
            ]);

            // Update the chat message with the error
            $chatMessage->update([
                'error' => $lastException ? $lastException->getMessage() : 'Failed to stream response after max retries'
            ]);

            throw $lastException ?? new \RuntimeException('Failed to stream response after max retries');

        } catch (\Exception $e) {
            Log::error('Error streaming response', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'chat_message_id' => $chatMessage->id,
            ]);
            throw $e;
        }
    }
}
