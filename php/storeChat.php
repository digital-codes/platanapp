<?php
function storeChatData(PDO $pdo, array $data): string {
    // Get data
    $session = $data['session'] ?? null;
    $seq = isset($data['seq']) ? (int)$data['seq'] : null;
    $system = $data['system'] ?? null;
    $osinfo = $data['osinfo'] ?? null;
    $model = $data['model'] ?? null;
    $lat = $data['lat'] ?? null;
    $lon = $data['lon'] ?? null;
    $lang = $data['lang'] ?? null;
    $user = $data['user'] ?? null;
    $response = $data['response'] ?? null;

    if ($session === null) {
        return "Missing 'session'";
    }

    if ($seq > 1) {
        $system = null;
    }

    $stmt = $pdo->prepare("
        INSERT INTO chats (session, seq, user, system, response, os, model,lat,lon,lang)
        VALUES (:session, :seq, :user, :system, :response, :osinfo, :model, :lat, :lon, :lang)
    ");
    $stmt->bindValue(':session', $session, PDO::PARAM_STR);
    $stmt->bindValue(':user', $user, PDO::PARAM_STR);
    $stmt->bindValue(':response', $response, PDO::PARAM_STR);
    $stmt->bindValue(':seq', $seq, PDO::PARAM_INT);
    $stmt->bindValue(':system', $system, $system === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':osinfo', $osinfo, $osinfo === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':model', $model, $model === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':lat', $lat, $lat === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':lon', $lon, $lon === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindValue(':lang', $lang, $lang === null ? PDO::PARAM_NULL : PDO::PARAM_STR);    

    try {
        $stmt->execute();
        return "Insert successful.";
    } catch (PDOException $e) {
        return "Insert failed: " . $e->getMessage();
    }
}


function getSessionEntries(PDO $pdo, string $sessionId): array {
    $sql = "
        SELECT user, system, response, model, lang
        FROM chats
        WHERE session = :session
        ORDER BY seq ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':session', $sessionId, PDO::PARAM_STR);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// $entries = getSessionEntries($pdo, $sessionId);


function createConnection(bool $isLocal = null): array {
    // Choose config file
    $iniPath = $isLocal ? './config.ini' : '/var/www/files/platane/config.ini';
    if (!file_exists($iniPath)) {
        return ["status"=>"error", "connection"=>null, "message"=>"Configuration file not found: $iniPath"];
    }

    $config = parse_ini_file($iniPath, true)['db'] ?? null;
    if (!$config) {
        return ["status"=>"error", "connection"=>null, "message"=>"Invalid config format in $iniPath"];
    }

    // Connect to DB
    try {
        $dsn = "mysql:host=localhost;dbname={$config['dbname']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['dbuser'], $config['dbpwd'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        return ["status"=>"ok","connection"=>$pdo,"message"=>"Connection successful."];
    } catch (PDOException $e) {
        return ["status"=>"error", "connection"=>null, "message"=>"DB connection failed: " . $e->getMessage()];
    }
}
