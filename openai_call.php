<?php
require_once __DIR__ . "/config.php";

/* Load .env file */
loadEnv(__DIR__ . "/.env");

/**
 * Call OpenAI Chat Completion API
 *
 * @param array $messages  Chat messages array
 * @param string $model    OpenAI model (default: gpt-4o-mini)
 * @param float $temperature
 * @return string
 */
function callOpenAI(array $messages, string $model = "gpt-4o-mini", float $temperature = 0.2): string
{
    $apiKey = getenv("OPENAI_API_KEY");

    if (!$apiKey) {
        return " OPENAI_API_KEY not found in .env";
    }

    $payload = [
        "model" => $model,
        "messages" => $messages,
        "temperature" => $temperature
    ];

    $ch = curl_init("https://api.openai.com/v1/chat/completions");

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Authorization: Bearer " . $apiKey
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_TIMEOUT => 60,

    // DEV FIX FOR WINDOWS SSL
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false
]);


    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return " Curl error: " . $error;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);

    if ($httpCode !== 200) {
        return " OpenAI API error (" . $httpCode . "): " . ($data['error']['message'] ?? 'Unknown error');
    }

    return $data['choices'][0]['message']['content'] ?? " Empty response from OpenAI";
}
