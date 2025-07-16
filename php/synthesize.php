$text = "Hallo, ich bin Papperlapp.";
$escapedText = escapeshellarg($text);

// Adjust paths
$pyenvInit = '/home/okl/.pyenv/bin/pyenv';
$venvName = 'myenv';  // your pyenv virtualenv name
$pythonScript = '/home/okl/tts/tts_generate.py';

// Build full bash command
$bashCmd = <<<BASH
export HOME=/home/okl
export PYENV_ROOT="\$HOME/.pyenv"
export PATH="\$PYENV_ROOT/bin:\$PATH"
eval "\$(pyenv init -)"
pyenv activate $venvName
python $pythonScript $escapedText
BASH;

// Wrap in bash -c
$cmd = 'bash -c ' . escapeshellarg($bashCmd);

// Run
$descriptorSpec = [
    0 => ['pipe', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];

$env = ['HOME' => '/home/okl']; // important for pyenv

$process = proc_open($cmd, $descriptorSpec, $pipes, null, $env);

if (!is_resource($process)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to start TTS process']);
    exit;
}

$output = stream_get_contents($pipes[1]);
$error = stream_get_contents($pipes[2]);
fclose($pipes[0]);
fclose($pipes[1]);
fclose($pipes[2]);

$returnCode = proc_close($process);
if ($returnCode !== 0) {
    http_response_code(500);
    echo json_encode([
        'error' => 'TTS execution failed',
        'exitCode' => $returnCode,
        'stderr' => $error
    ]);
    exit;
}

$output = trim($output);
if (!file_exists($output)) {
    http_response_code(500);
    echo json_encode(['error' => 'Output file not found']);
    exit;
}

// Return path or stream
echo json_encode(['audio_url' => '/serve/' . basename($output)]);

