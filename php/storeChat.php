<?php
function storeChatData(array $data, bool $isLocal = null): string {

    // Choose config file
    $iniPath = $isLocal ? './chats.ini' : '/var/www/files/platane/chats.ini';
    if (!file_exists($iniPath)) {
        return "Configuration file not found: $iniPath";
    }

    $config = parse_ini_file($iniPath, true)['db'] ?? null;
    if (!$config) {
        return "Invalid config format in $iniPath";
    }

    // Connect to DB
    try {
        $dsn = "mysql:host=localhost;dbname={$config['dbname']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['dbuser'], $config['dbpwd'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
    } catch (PDOException $e) {
        return "DB connection failed: " . $e->getMessage();
    }

    // Get data
    $session = $data['session'] ?? null;
    $seq = isset($data['seq']) ? (int)$data['seq'] : null;
    $system = $data['system'] ?? null;
    $user = $data['user'] ?? null;
    $response = $data['response'] ?? null;

    if ($session === null) {
        return "Missing 'session'";
    }

    if ($seq > 1) {
        $system = null;
    }

    $stmt = $pdo->prepare("
        INSERT INTO chats (session, seq, user, system, response)
        VALUES (:session, :seq, :user, :system, :response)
    ");
    $stmt->bindValue(':session', $session, PDO::PARAM_STR);
    $stmt->bindValue(':user', $user, PDO::PARAM_STR);
    $stmt->bindValue(':response', $response, PDO::PARAM_STR);
    $stmt->bindValue(':seq', $seq, PDO::PARAM_INT);
    $stmt->bindValue(':system', $system, $system === null ? PDO::PARAM_NULL : PDO::PARAM_STR);

    try {
        $stmt->execute();
        return "Insert successful.";
    } catch (PDOException $e) {
        return "Insert failed: " . $e->getMessage();
    }
}
