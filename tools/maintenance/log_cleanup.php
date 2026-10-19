<?php

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';
require_once dirname(__DIR__, 2) . '/app/Infrastructure/Logging/LogFileManager.php';

$config = new configuration();
$logDirectory = dirname(__DIR__, 2) . '/var/log';
$retentionDays = max(1, (int)($config::$logRetentionDays ?: LogFileManager::DEFAULT_RETENTION_DAYS));
$result = LogFileManager::cleanup($logDirectory, $retentionDays);

writeToLogFunction::info('Scheduled log cleanup finished.', __FILE__, array(
    'retentionDays' => $retentionDays,
    'deletedFiles' => (int)$result['deletedFiles'],
    'deletedBytes' => (int)$result['deletedBytes'],
    'failedFiles' => (int)$result['failedFiles'],
));

echo json_encode(
    array_merge(array('status' => empty($result['failedFiles']) ? 'ok' : 'warning'), $result),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
) . PHP_EOL;
