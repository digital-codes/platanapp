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
$logFile = __DIR__ . '/whisper.log';

// Helper to log errors
function logError($message, $logFile) {
    $entry = sprintf("[%s] ERROR: %s\n", date('Y-m-d H:i:s'), $message);
    file_put_contents($logFile, $entry, FILE_APPEND);
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['filename'])) {
    logError('Missing filename', $logFile);
    http_response_code(400);
    echo json_encode(['error' => 'Missing audio filename']);
    exit;
}

$inputFile = $audioDir . "/" . $data['filename'];
$outputFile = $inputFile . ".txt";

if (!file_exists($inputFile)) {
    logError("Input file does not exist: $inputFile", $logFile);
    http_response_code(404);
    echo json_encode(['error' => 'Input audio file not found']);
    exit;
}

// Set up paths for Whisper execution

$libPath = '/opt/llama/whisper/lib'; // Where libopenblas.so or others live
$whisperBin = '/opt/llama/whisper/bin/whisper-cli';
$model = '/opt/llama/whisper/models/ggml-medium.bin';

$cmd = "env LD_LIBRARY_PATH={$libPath}:\$LD_LIBRARY_PATH {$whisperBin} -m {$model} -f {$inputFile} --output-txt --language de --no-timestamps ";

// Escape the command
exec($cmd, $output, $returnVar);

if ($returnVar !== 0) {
    error_log("Whisper failed: " . implode("\n", $output));
    http_response_code(500);
    echo json_encode(['error' => 'Whisper execution failed']);
    exit;
}

$result = file_exists($outputFile) ? file_get_contents($outputFile) : '';

// Optionally normalize whitespace and remove special markers
$transcript = trim($result);

// Define patterns considered "non-speech"
$nonSpeechPatterns = [
    '/^\*.*\*$/i',       // Lines like * Klopfen * or * Lachen *
    '/^\[.*\]$/i',       // Lines like [MUSIC], [NOISE]
    '/^\[_EOT_\]$/i',    // Whisper EOT marker
];

// If transcript only contains noise/markers, return blank
foreach ($nonSpeechPatterns as $pattern) {
    if (preg_match($pattern, $transcript)) {
        $transcript = '';
        break;
    }
}


echo json_encode(['status' => 'ok', 'text' => $transcript]);

