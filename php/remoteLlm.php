<?php

function remoteQuery($key, $model, $url, $prompt, $context, $query): array
{
    // Build the messages array
    $messages = [
        ["role" => "system", "content" => $prompt]
    ];

    foreach ($context as $entry) {
        $messages[] = ["role" => "user", "content" => $entry['user']];
        $messages[] = ["role" => "assistant", "content" => $entry['assistant']];
    }

    // Add the current user query
    $messages[] = ["role" => "user", "content" => $query];

    // Prepare request payload
    $payload = json_encode([
        "model" => $model,
        "messages" => $messages
    ]);

    // Set up cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer $key"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    // Execute and get the response
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Handle the response
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        $reply = $result['choices'][0]['message']['content'] ?? "No reply.";
        $result = [
            'status' => 'ok',
            'reply' => $reply
        ];
    } else {
        $result = [
            'status' => 'error',
            'reply' => "Error ($httpCode): $response"
        ];
    }
    return $result;
}

