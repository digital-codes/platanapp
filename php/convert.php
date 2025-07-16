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
$logFile = __DIR__ . '/convert.log';

// Helper to log errors
function logError($message, $logFile) {
    $entry = sprintf("[%s] ERROR: %s\n", date('Y-m-d H:i:s'), $message);
    file_put_contents($logFile, $entry, FILE_APPEND);
}

// clean up files first
$now = time();
$deleted = [];
$files = array_merge(
    glob($audioDir . '/*.webm'),
    glob($audioDir . '/*.wav')
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

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['filename'])) {
    logError('Missing filename', $logFile);
    http_response_code(400);
    echo json_encode(['error' => 'Missing audio filename']);
    exit;
}

$inputFile = $audioDir . "/" . $data['filename'];
$outputFile = "converted.wav";

if (!file_exists($inputFile)) {
    logError("Input file does not exist: $inputFile", $logFile);
    http_response_code(404);
    echo json_encode(['error' => 'Input audio file not found']);
    exit;
}

// Convert to WAV using ffmpeg
$cmd = "ffmpeg -y -i " . escapeshellarg($inputFile) . " -ar 16000 -ac 1 -c:a pcm_s16le " . escapeshellarg($audioDir . "/" . $outputFile);
exec($cmd, $output, $returnVar);

if ($returnVar !== 0) {
    error_log("FFmpeg conversion failed: " . implode("\n", $output));
    http_response_code(500);
    echo json_encode(['error' => 'Failed to convert audio for Whisper']);
    exit;
}

echo json_encode(['status' => 'ok', 'filename' => $outputFile]);

