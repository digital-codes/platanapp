<?php
// audioRx.php
// don't set     
// header('Access-Control-Allow-Origin: *');
// here. is already done in website config

date_default_timezone_set('UTC');

$audioDir = __DIR__ . '/audio';
if (!is_dir($audioDir)) {
    mkdir($audioDir, 0755, true);
}

$logFile = __DIR__ . '/audio_rx.log';

// Helper to log errors
function logError($message, $logFile)
{
    $entry = sprintf("[%s] ERROR: %s\n", date('Y-m-d H:i:s'), $message);
    file_put_contents($logFile, $entry, FILE_APPEND);
}

// clean up files first
$now = time();
$deleted = [];
$files = array_merge(
    glob($audioDir . '/*.webm'),
    glob($audioDir . '/*.wav'),
    glob($audioDir . '/*.ogg'),
    glob($audioDir . '/*.txt')
);

foreach ($files as $file) {
    if (is_file($file)) {
        $fileMTime = filemtime($file);

        // Older than 10 minutes?
        if ($now - $fileMTime > 600) {
            if (unlink($file)) {
                $deleted[] = basename($file);
            }
        }
    }
}

logError('Deleted files: ' . implode(', ', $deleted), $logFile);

// check tts service
$port = 9010;
$host = 'localhost';
$url = "http://$host:$port/transscribe"; // or any endpoint you defined

// 1. Check if service is responding
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_TIMEOUT, 2);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    // 2. Service not running, start it in background
    // Adjust paths
    $venvActivate = '/opt/pyenvs/tts/bin/activate'; // or virtualenv/bin/activate
    $ttsScript = '/var/www/html/llama/platane/py/ttsService.py';

    // Start command wrapped in bash -c to allow venv activation
    $bashCmd = <<<BASH
source $venvActivate
python $ttsScript --port $port
BASH;

    // Run in background with output redirected
    $finalCmd = 'bash -c ' . escapeshellarg($bashCmd) . ' > /dev/null 2>&1 &';

    exec($finalCmd, $out, $exitCode);

    if ($exitCode !== 0) {
        logError("Failed to start tts server", $logFile);
        http_response_code(500);
        echo json_encode(['error' => 'Failed to start TTS service']);
        exit;
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(204);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['audio'])) {
    logError('Missing audio data', $logFile);
    http_response_code(400);
    echo json_encode(['error' => 'Missing audio data']);
    exit;
}

if (!isset($data['filename'])) {
    logError('Missing audio file', $logFile);
    http_response_code(400);
    echo json_encode(['error' => 'Missing audio file']);
    exit;
}

$audioData = base64_decode($data['audio']);
if ($audioData === false) {
    logError('Invalid base64 audio', $logFile);
    http_response_code(400);
    echo json_encode(['error' => 'Invalid base64 audio']);
    exit;
}

$filename = $data['filename'] . '.ogg';
$filePath = "{$audioDir}/{$filename}";

if (file_put_contents($filePath, $audioData) === false) {
    logError("Failed to save audio file: $filename", $logFile);
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save audio file']);
    exit;
}

$logEntry = sprintf(
    "[%s] Saved: %s, Size: %d bytes\n",
    date('Y-m-d H:i:s'),
    $filename,
    strlen($audioData)
);
file_put_contents($logFile, $logEntry, FILE_APPEND);

echo json_encode(['status' => 'ok', 'filename' => $filename]);