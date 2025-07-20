<?php
// audioRx.php
date_default_timezone_set('UTC');

// database handler
require_once("storeChat.php");


// Define paths
$audioDir = __DIR__ . '/audio';
$logFile = __DIR__ . '/platachat.log';

// Helper to log errors
function logError($message, $logFile)
{
    $entry = sprintf("[%s] ERROR: %s\n", date('Y-m-d H:i:s'), $message);
    file_put_contents($logFile, $entry, FILE_APPEND);
}


// don't set     
// header('Access-Control-Allow-Origin: *');
// here. is already done in website config

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(204);
    exit;
}

// Check if the request is from localhost
$clientIp = $_SERVER['REMOTE_ADDR'];
if ($clientIp === '127.0.0.1' || $clientIp === '::1') {
    $isLocal = true;
} else {
    $isLocal = false;
}

$connection = createConnection($isLocal);
if ($connection['status'] !== 'ok') {
    logError("Database connection failed: " . $connection['message'], $logFile);
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}   
$pdo = $connection['connection'];
if (!$pdo) {
    logError("Failed to create PDO connection", $logFile);
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}


$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['text'])) {
    logError('Missing user text', $logFile);
    http_response_code(400);
    echo json_encode(['error' => 'Missing user text']);
    exit;
}

/* we also expect following params:
              lang: language,
              prompt: prompt,
              model: mdl,
              session: session,
              seq: seq,
              lat: lat,
              lon: lon,
              osinfo: osinfo
*/
if (!isset($data['session'])) {
    logError('Missing session ID', $logFile);
    http_response_code(400);
    echo json_encode(['error' => 'Missing session ID']);
    exit;
}
if (!isset($data['seq'])) {
    logError('Missing sequence number', $logFile);
    http_response_code(400);
    echo json_encode(['error' => 'Missing sequence number']);
    exit;
}   
if (!isset($data['lat'])) {
    logError('Missing latitude', $logFile);
    // overwrite geodata
    $data['lat'] = "-1.0"; // default value
}
if (!isset($data['lon'])) {
    logError('Missing longitude', $logFile);
    // overwrite geodata
    $data['lon'] = "-1.0"; // default value
}
if (!isset($data['osinfo'])) {
    logError('Missing OS info', $logFile);
    http_response_code(400);
    echo json_encode(['error' => 'Missing OS info']);
    exit;
}
if (!isset($data['lang'])) {
    logError('Missing language', $logFile);
    http_response_code(400);
    echo json_encode(['error' => 'Missing language']);
    exit;
}
if (!isset($data['prompt'])) {
    logError('Missing prompt', $logFile);
    http_response_code(400);
    echo json_encode(['error' => 'Missing prompt']);
    exit;
}

if ($data["seq"] == 1) {
    // initialize the session
    switch ($data['prompt']) {
        case 'default':
            $promptfile = "plataPrompt.txt";
            break;
        case 'fest25':
            $promptfile = "fest25Prompt.txt";
            break;
        default:
            logError('Unsupported prompt', $logFile);
            http_response_code(400);
            echo json_encode(['error' => 'Unsupported prompt']);
            exit;
    }

    $climatePromptPath = __DIR__ . '/../py/climate_prompt.txt';
    if (file_exists($climatePromptPath)) {
        $climatePrompt = file_get_contents($climatePromptPath);
    } else {
        $climatePrompt = null;
    }

    $systemPrompt = implode(' ', array_map('trim', file($promptfile)));
    if ($climatePrompt !== null) {
        $systemPrompt .= ' ' . trim($climatePrompt);
    }
    // user input
    $userText = escapeshellarg($data['text']); // Protect input
    $modelPrompt = "System: " . $systemPrompt . "Frage:" . $userText . PHP_EOL;
} else {
    // read session history
    $sessionEntries = getSessionEntries($pdo, $data['session']);
    if (empty($sessionEntries)) {
        logError('Session not found or empty', $logFile);
        http_response_code(404);
        echo json_encode(['error' => 'Session not found or empty']);
        exit;
    }
    $systemPrompt = $sessionEntries[0]['system'] ?? null;
    if ($systemPrompt === null) {
        logError('No system prompt found in session', $logFile);
        http_response_code(404);
        echo json_encode(['error' => 'No system prompt found in session']);
        exit;
    }
    $modelPrompt = "System: " . $systemPrompt;
    foreach ($sessionEntries as $entry) {
        if (isset($entry['user'])) {
            $modelPrompt .= "Frage: " . trim($entry['user']);
        }
        if (isset($entry['response'])) {
            $modelPrompt .= "Antwort: " . trim($entry['response']);
        }
    }
    // final user input
    $userText = escapeshellarg($data['text']); // Protect input
    $modelPrompt .= "Frage: " . $userText . PHP_EOL;
}
// check
//logError("Model prompt: $modelPrompt", $logFile);

// Build the `ollama run` command
$model = isset($data['model']) ? escapeshellarg($data['model']) : 'granite3.3:2b';
// qwen2.5:3b   deepseek-r1:1.5b  gemma3:1b  phi3:mini llama3.2:latest 

//set env for ollama. needs to store some cache data there
$home = '/home/okl'; 
$env = array_merge($_ENV, [
    'HOME' => $home,
]);


$descriptorSpec = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];

$process = proc_open("ollama run $model", $descriptorSpec, $pipes, null, $env);

if (!is_resource($process)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to start Ollama']);
    exit;
}

fwrite($pipes[0], $modelPrompt);
fclose($pipes[0]);

$output = stream_get_contents($pipes[1]);
fclose($pipes[1]);

$error = stream_get_contents($pipes[2]);
fclose($pipes[2]);

$returnCode = proc_close($process);

if ($returnCode !== 0) {
    http_response_code(500);
    logError("Ollama failed on: " . $modelPrompt, $logFile);

    echo json_encode([
        'error' => 'Ollama execution failed',
        'exitCode' => $returnCode,
        'stderr' => $error
    ]);
    exit;
}


// store chat data


// trim output 
$output = preg_replace('/<think>.*?<\/think>/is', '', $output);
$output = trim($output);

// store chat data
$chatData = [
    'session' => $data['session'],
    'seq' => $data['seq'],
    'user' => $data['text'],
    'system' => $systemPrompt,
    'response' => $output,
    'osinfo' => $data['osinfo'],
    'model' => $data['model'] ?? null,
    'lat' => $data['lat'] ?? null,
    'lon' => $data['lon'] ?? null,
    'lang' => $data['lang'] ?? null,
];

$storeResult = storeChatData($pdo, $chatData);
if (strpos($storeResult, 'successful') === false) {
    logError("Failed to store chat data: $storeResult", $logFile);
    http_response_code(500);
    echo json_encode(['error' => 'Failed to store chat data']);
    exit;
}   

// check synthesizer 
$port = 9010;
$host = 'localhost';
$ttsUrl = "http://$host:$port/transscribe"; // or any endpoint you defined

// 1. Check if service is responding
$ch = curl_init($ttsUrl);
curl_setopt($ch, CURLOPT_TIMEOUT, 2);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    logError("TTS service returned: $httpCode", $logFile);
    // Synthesize the output with espeak-ng
    $audioFile = $audioDir . "/" . $data['session'] . "_" . $data['seq'] . '.wav';
    $synth = "espeak-ng";

    // Escape output for shell
    $escapedOutput = escapeshellarg($output);

    // Build espeak-ng command
    $espeakCmd = "espeak-ng -v mb-de2 -w " . escapeshellarg($audioFile) . " $escapedOutput";

    // Execute espeak-ng
    exec($espeakCmd, $espeakOutput, $espeakReturn);

    if ($espeakReturn !== 0) {
        logError("espeak-ng failed: " . implode("\n", $espeakOutput), $logFile);
        http_response_code(500);
        echo json_encode(['error' => 'espeak-ng synthesis failed']);
        exit;
    }
} else {
    // If TTS service is running, use it to synthesize the output
    $audioFile =  $data['session'] . "_" . $data['seq'] . '.wav'; // plain name here 
    $synth = "coqui";
    $postFields = [
        'text' => $output,
        'file' => $audioFile
    ];

    // Prepare cURL for POST
    $ch = curl_init($ttsUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postFields));    

    // Execute POST request
    $ttsResponse = curl_exec($ch);
    $ttsHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $ttsError = curl_error($ch);
    curl_close($ch);

    if ($ttsHttpCode !== 200 || !$ttsResponse) {
        logError("TTS service failed: $ttsError", $logFile);
        http_response_code(500);
        echo json_encode(['error' => 'TTS service synthesis failed']);
        exit;
    }
    // Try to extract filename from TTS response if present (assume JSON with 'filename')
    $ttsJson = json_decode($ttsResponse, true);
    if (is_array($ttsJson) && isset($ttsJson['filename'])) {
        $audioFile = $ttsJson['filename'];
    } else {
        logError("TTS did not return filename", $logFile);
        http_response_code(500);
        echo json_encode(['error' => 'TTS service synthesis failed (missing filename)']);
        exit;
    }

}

$audioData = file_get_contents($audioFile);
$audioBase64 = base64_encode($audioData);

foreach (glob($audioDir . "/*" . $data['session'] . "*") as $oldFile) {
    if (is_file($oldFile)) {
        @unlink($oldFile);
    }
}
echo json_encode(value: ['status' => 'ok', 'text' => $output, "audio" => $audioBase64, "synth" => $synth]);



