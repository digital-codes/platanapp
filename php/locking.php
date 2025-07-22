<?php
define('LOCK_DIR', '/var/www/files/locks/');

$globalLockHandles = [];

function getLockFilePath($name) {
    return LOCK_DIR . preg_replace('/[^a-z0-9_\-]/i', '_', $name) . '.lock';
}

function acquireLock($name) {
    global $globalLockHandles;

    $filePath = getLockFilePath($name);
    if (!is_dir(LOCK_DIR)) {
        mkdir(LOCK_DIR, 0777, true);
    }

    $fp = fopen($filePath, 'c');
    if (!$fp) {
        return false;
    }

    // BLOCKING lock: waits until available
    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        return false;
    }

    // Optional: Write PID for debugging
    ftruncate($fp, 0);
    fwrite($fp, getmypid() . "\n");

    $globalLockHandles[$name] = $fp;
    return true;
}

function releaseLock($name) {
    global $globalLockHandles;

    if (!isset($globalLockHandles[$name])) {
        return false;
    }

    $fp = $globalLockHandles[$name];
    flock($fp, LOCK_UN);
    fclose($fp);
    unset($globalLockHandles[$name]);

    return true;
}
