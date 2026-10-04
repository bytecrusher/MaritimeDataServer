<?php

require_once dirname(__DIR__) . '/app/Infrastructure/Logging/LogFileManager.php';

function failLogManagerTest($message)
{
    fwrite(STDERR, 'FAILED: ' . $message . PHP_EOL);
    exit(1);
}

function removeLogManagerTestDirectory($directory)
{
    foreach (glob($directory . '/*') ?: array() as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
    foreach (glob($directory . '/.*') ?: array() as $file) {
        if (basename($file) !== '.' && basename($file) !== '..' && is_file($file)) {
            unlink($file);
        }
    }
    rmdir($directory);
}

$directory = sys_get_temp_dir() . '/mds-log-manager-test-' . bin2hex(random_bytes(6));
$now = strtotime('2026-07-25 12:00:00');
$header = 'Date Time Level Source Message';

$first = LogFileManager::append($directory, 'log_Juli_2026.log', $header, str_repeat('A', 700), 1024, 90, $now);
$second = LogFileManager::append($directory, 'log_Juli_2026.log', $header, str_repeat('B', 700), 1024, 90, $now + 1);
if (empty($first['success']) || empty($second['success']) || empty($second['rotated'])) {
    failLogManagerTest('A log file exceeding its maximum size was not rotated.');
}

$logFiles = glob($directory . '/*.log') ?: array();
if (count($logFiles) !== 2) {
    failLogManagerTest('Rotation must preserve one archive and one current log file.');
}
foreach ($logFiles as $logFile) {
    if (!str_starts_with((string)file_get_contents($logFile), $header . PHP_EOL)) {
        failLogManagerTest('Every rotated or current log file must start with the log header.');
    }
}

$oldLog = $directory . '/log_old.log';
file_put_contents($oldLog, $header . PHP_EOL . 'old entry' . PHP_EOL);
touch($oldLog, $now - (10 * 86400));
$cleanupResult = LogFileManager::cleanup($directory, 5, $now);
if (is_file($oldLog) || (int)$cleanupResult['deletedFiles'] !== 1 || (int)$cleanupResult['failedFiles'] !== 0) {
    failLogManagerTest('Expired log files were not removed according to retention settings.');
}

$scheduledOldLog = $directory . '/log_scheduled_old.log';
file_put_contents($scheduledOldLog, $header . PHP_EOL . 'old scheduled entry' . PHP_EOL);
touch($scheduledOldLog, $now - (10 * 86400));
touch($directory . '/.log-maintenance', $now - LogFileManager::MAINTENANCE_INTERVAL_SECONDS - 1);
$scheduled = LogFileManager::append($directory, 'log_Juli_2026.log', $header, 'new entry', 1024, 5, $now);
if (is_file($scheduledOldLog) || (int)$scheduled['deletedFiles'] !== 1) {
    failLogManagerTest('Daily retention cleanup was not triggered while writing.');
}

$oversized = LogFileManager::append($directory, 'oversized.log', $header, str_repeat('X', 5000), 1024, 5, $now);
$oversizedContent = (string)file_get_contents($directory . '/oversized.log');
if (empty($oversized['success']) || filesize($directory . '/oversized.log') > 1024 || !str_contains($oversizedContent, '[log entry truncated]')) {
    failLogManagerTest('A single oversized log entry was not limited to the configured file size.');
}

removeLogManagerTestDirectory($directory);
echo "LogFileManager tests passed.\n";
