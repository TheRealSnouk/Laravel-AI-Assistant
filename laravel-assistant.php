#!/usr/bin/env php
<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;

class LaravelAssistant
{
    private Client $client;
    private string $baseUrl = 'http://localhost:8080';

    public function __construct()
    {
        $this->client = new Client(['base_uri' => $this->baseUrl]);
    }

    public function generateCode(string $prompt): string
    {
        $response = $this->client->post('/v1/chat/completions', [
            'json' => [
                'model' => 'laravel-assistant',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.7
            ]
        ]);

        $result = json_decode($response->getBody()->getContents(), true);
        return $result['choices'][0]['message']['content'];
    }

    public function handleCommand(): void
    {
        $prompt = implode(' ', array_slice($GLOBALS['argv'], 1));
        if (empty($prompt)) {
            echo "Please provide a Laravel development task\n";
            echo "Example: php laravel-assistant.php 'Create a User model with email verification'\n";
            return;
        }

        try {
            $response = $this->generateCode($prompt);
            echo $response . "\n";
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
        }
    }
}

$assistant = new LaravelAssistant();
$assistant->handleCommand();