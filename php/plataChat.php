<?php
// audioRx.php
date_default_timezone_set('UTC');

// database handler
require_once("storeChat.php");
// remote handler
require_once("remoteLlm.php");


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
    echo json_encode(['error' => 'Database connection failed (1)']);
    exit;
}
$pdo = $connection['connection'];
if (!$pdo) {
    logError("Failed to create PDO connection", $logFile);
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed (2)']);
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
if (isset($data['model']) && $data['model'] === 'remote' ) {
    // set for remote llm
    $useRemote = true;
} else {
    $useRemote = false;
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

    $remotePrompt = $systemPrompt;
    // user input
    $userText = escapeshellarg($data['text']); // Protect input
    $remoteQuery = $userText;
    $remoteContext = [];
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
    $remotePrompt = $systemPrompt;
    $remoteContext = [];
    $modelPrompt = "System: " . $systemPrompt;
    foreach ($sessionEntries as $entry) {
        $context = [];
        if (isset($entry['user'])) {
            $modelPrompt .= "Frage: " . trim($entry['user']);
            $context['user'] = $entry['user'];
        }
        if (isset($entry['response'])) {
            $modelPrompt .= "Antwort: " . trim($entry['response']);
            $context['assistant'] = $entry['response'];
        }
        $remoteContext[] = $context;
    }
    // final user input
    $userText = escapeshellarg($data['text']); // Protect input
    $remoteQuery = $userText;
    $modelPrompt .= "Frage: " . $userText . PHP_EOL;
}

// parse init
$iniPath = $isLocal ? './config.ini' : '/var/www/files/platane/config.ini';
if (!file_exists($iniPath)) {
    logError("Missing ini file: $iniPath", $logFile);
    http_response_code(500);
    echo json_encode(['error' => 'Configuration file not found']);
    exit;
}

if (!$useRemote) {
    $configLlm = parse_ini_file($iniPath, true)['LOCAL'] ?? null;
} else {
    $configLlm = parse_ini_file($iniPath, true)['REMOTE'] ?? null;
}

if (!$configLlm) {
    logError("Invalid config format in $iniPath", $logFile);
    http_response_code(500);
    echo json_encode(['error' => 'Invalid config format']);
    exit;
}


// locking mechanism
require_once __DIR__ . '/locking.php';
$lockname = 'llm';
// blocking!    
acquireLock($lockname);


if (!$useRemote) {

    // check
    logError("Using ollama " . $configLlm['url'], $logFile);

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

} else {
    logError("Using remote LLM API", $logFile);
    // Call the remote LLM API
    $apiKey = $configLlm['api_key'];
    $model = $configLlm['llmodel'];
    $url = $configLlm['llurl'];

    // Call the function
    $response = remoteQuery($apiKey, $model, $url, $remotePrompt, $remoteContext, $remoteQuery);
    if ($response['status'] === 'error') {
        http_response_code(500);
        logError("remote llm failed", $logFile);
        echo json_encode([
            'error' => 'LLM execution failed',
        ]);
        exit;
    } else {
        // Process the response as needed
        $output = $response['reply'];
        $output = preg_replace('/^Assistant:\s*/i', '', $output);
    }
}
// store chat data

releaseLock($lockname);

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


// locking mechanism
$lockname = 'audioSynth';
// blocking!    
acquireLock($lockname);

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

$audioOutput = preg_replace_callback('/\bki\b|\bKI\b/', function ($matches) {
    // Replace isolated 'ki' or 'KI' with 'K I'
    return strtoupper($matches[0][0]) === 'K' ? 'K I' : 'k i';
}, $output);

$audioOutput = preg_replace('/[\x{1F600}-\x{1F64F}|\x{1F300}-\x{1F5FF}|\x{1F680}-\x{1F6FF}|\x{2600}-\x{26FF}|\x{2700}-\x{27BF}|\x{1F900}-\x{1F9FF}|\x{1FA70}-\x{1FAFF}|\x{200D}|\x{23CF}|\x{23E9}-\x{23F3}|\x{25FD}-\x{25FE}|\x{2614}-\x{2615}|\x{2648}-\x{2653}|\x{267F}|\x{2693}|\x{26A1}|\x{26AA}-\x{26AB}|\x{26BD}-\x{26BE}|\x{26C4}-\x{26C5}|\x{26CE}|\x{26D4}|\x{26EA}|\x{26F2}-\x{26F3}|\x{26F5}|\x{26FA}|\x{26FD}|\x{2705}|\x{270A}-\x{270B}|\x{2728}|\x{274C}|\x{274E}|\x{2753}-\x{2755}|\x{2757}|\x{2795}-\x{2797}|\x{27B0}|\x{27BF}|\x{2B1B}-\x{2B1C}|\x{2B50}|\x{2B55}|\x{2934}-\x{2935}|\x{2B06}|\x{2B07}|\x{2B1B}|\x{2B1C}|\x{2B50}|\x{2B55}|\x{3030}|\x{303D}|\x{3297}|\x{3299}]/u', '', $audioOutput);
// Remove other non-printable or non-ASCII characters
$audioOutput = preg_replace('/[^\P{C}\x20-\x7E]/u', '', $audioOutput);

if ($httpCode !== 200) {
    logError("TTS service returned: $httpCode", $logFile);
    // Synthesize the output with espeak-ng
    $audioFile = $audioDir . "/" . $data['session'] . "_" . $data['seq'] . '.wav';
    $synth = "espeak-ng";

    // Escape output for shell
    $escapedOutput = escapeshellarg($audioOutput);

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
    $audioFile = $data['session'] . "_" . $data['seq'] . '.wav'; // plain name here 
    $synth = "coqui";
    $postFields = [
        'text' => $audioOutput,
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

releaseLock($lockname);

$audioData = file_get_contents($audioFile);
$audioBase64 = base64_encode($audioData);

foreach (glob($audioDir . "/*" . $data['session'] . "*") as $oldFile) {
    if (is_file($oldFile)) {
        @unlink($oldFile);
    }
}
echo json_encode(value: ['status' => 'ok', 'text' => $output, "audio" => $audioBase64, "synth" => $synth]);



