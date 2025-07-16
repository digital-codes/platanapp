<?php
// audioRx.php
date_default_timezone_set('UTC');

// don't set     
// header('Access-Control-Allow-Origin: *');
// here. is already done in website config

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(204);
    exit;
}

$audioDir = __DIR__ . '/audio';
if (!is_dir($audioDir)) {
    mkdir($audioDir, 0755, true);
}

$logFile = __DIR__ . '/audio_rx.log';

// Helper to log errors
function logError($message, $logFile) {
    $entry = sprintf("[%s] ERROR: %s\n", date('Y-m-d H:i:s'), $message);
    file_put_contents($logFile, $entry, FILE_APPEND);
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['audio'])) {
    logError('Missing audio data', $logFile);
    http_response_code(400);
    echo json_encode(['error' => 'Missing audio data']);
    exit;
}

$audioData = base64_decode($data['audio']);
if ($audioData === false) {
    logError('Invalid base64 audio', $logFile);
    http_response_code(400);
    echo json_encode(['error' => 'Invalid base64 audio']);
    exit;
}

$filename = date('Ymd_His') . '.ogg';
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