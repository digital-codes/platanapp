<?php
function transcribeAudioFile(string $filePath, string $apiKey, string $model, string $url, string $language = "de"): ?string
{
    // Check file exists
    if (!file_exists($filePath)) {
        return null;
    }

    // Init cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $apiKey"
    ]);

    // Build POST fields
    $postFields = [
        'file' => new CURLFile($filePath, mime_content_type($filePath), basename($filePath)),
        'model' => $model,
        "language" => $language
    ];
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Error handling
    if ($curlError || $httpCode !== 200) {
        return null;
    }

    // Parse JSON
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }

    // Return transcription text or null
    return $data['text'] ?? null;
}

// Example usage:
/*
$config = parse_ini_file(__DIR__ . '/config.ini', true);
if (!isset($config['REMOTE']['apiKey'])) {
    die("API key not found in config.ini under [REMOTE] section\n");
}
$apiKey = $config['REMOTE']['apiKey'];
$audioFile = __DIR__ . "/audio/decoded.pcm.wav"; // local file path
$outputFile = __DIR__ . "/audio/transcription.txt";
$model = "mistralai/Voxtral-Mini-3B-2507";
$url = "https://api.deepinfra.com/v1/openai/audio/transcriptions";
$transcription = transcribeAudioFile($audioFile, $apiKey, $model, $url);
if ($transcription !== null) {
    echo "Transcription:\n$transcription\n";
} else {
    echo "Failed to transcribe audio.\n";
}
*/
