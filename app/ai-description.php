<?php

use OpenAI\Client;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';

function generate_ai_description($productName)
{
    $apiKey = OPENAI_API_KEY;

    if (empty($apiKey)) {
        return "AI description unavailable: Missing API key.";
    }

    try {
        $client = OpenAI::client($apiKey);

        $prompt = "Write a detailed, SEO-friendly product description for: $productName.
                   Include features, benefits, and usage. Write in simple, clean English.";

        $response = $client->chat()->create([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => 'You are an expert e-commerce product description writer.'],
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 200
        ]);

        return trim($response['choices'][0]['message']['content']);

    } catch (Exception $e) {
        return "AI Error: " . $e->getMessage();
    }
}
