<?php

require_once dirname(__DIR__, 2) . "/bootstrap/app.php";
require_once dirname(__DIR__, 2) . "/app/Infrastructure/Database/dbConfig.func.php";
require_once dirname(__DIR__, 2) . "/app/Infrastructure/Logging/writeToLogFunction.func.php";

$config = new configuration();
$pdo = dbConfig::getInstance();

$logRetentionDays = max(1, (int)($config::$logRetentionDays ?: 90));
$passwordResetRetentionDays = max(1, (int)($config::$passwordResetRetentionDays ?: 30));
$telemetryRetentionDays = max(1, (int)($config::$telemetryRetentionDays ?: 365));
$gpsRetentionDays = max(1, (int)($config::$gpsRetentionDays ?: 90));
$ttnDebugRetentionDays = max(1, (int)($config::$ttnDebugRetentionDays ?: 30));
$securityTokenRetentionDays = max(1, (int)($config::$securityTokenRetentionDays ?: 45));
$projectRoot = dirname(__DIR__, 2);
$logDirectory = $projectRoot . '/var/log';

$deletedLogs = 0;
$resetCodesCleared = 0;
$telemetryRowsDeleted = 0;
$gpsRowsDeleted = 0;
$ttnDebugRowsDeleted = 0;
$securityTokensDeleted = 0;

writeToLogFunction::info(
    'Privacy cleanup job started.',
    __FILE__,
    array(
        'logRetentionDays' => $logRetentionDays,
        'passwordResetRetentionDays' => $passwordResetRetentionDays,
        'telemetryRetentionDays' => $telemetryRetentionDays,
        'gpsRetentionDays' => $gpsRetentionDays,
        'ttnDebugRetentionDays' => $ttnDebugRetentionDays,
        'securityTokenRetentionDays' => $securityTokenRetentionDays,
    )
);

if (is_dir($logDirectory)) {
    $cutoffTimestamp = time() - ($logRetentionDays * 86400);
    $logFiles = glob($logDirectory . '/*.log') ?: array();
    foreach ($logFiles as $logFile) {
        if (!is_file($logFile)) {
            continue;
        }

        $modifiedAt = @filemtime($logFile);
        if ($modifiedAt !== false && $modifiedAt < $cutoffTimestamp) {
            if (@unlink($logFile)) {
                $deletedLogs++;
            }
        }
    }
}

$statement = $pdo->prepare(
    "UPDATE users
     SET passwordCode = NULL,
         passwordCodeTime = NULL
     WHERE passwordCode IS NOT NULL
       AND passwordCodeTime IS NOT NULL
       AND passwordCodeTime < DATE_SUB(CURDATE(), INTERVAL ? DAY)"
);
$statement->execute(array($passwordResetRetentionDays));
$resetCodesCleared = $statement->rowCount();

$statement = $pdo->prepare(
    "DELETE sensorData
     FROM sensorData
     INNER JOIN sensorConfig ON sensorConfig.id = sensorData.sensorId
     INNER JOIN sensorTypes ON sensorTypes.id = sensorConfig.typId
     WHERE sensorTypes.name = 'GPS'
       AND sensorData.reading_time < DATE_SUB(NOW(), INTERVAL ? DAY)"
);
$statement->execute(array($gpsRetentionDays));
$gpsRowsDeleted = $statement->rowCount();

$statement = $pdo->prepare(
    "DELETE sensorData
     FROM sensorData
     INNER JOIN sensorConfig ON sensorConfig.id = sensorData.sensorId
     INNER JOIN sensorTypes ON sensorTypes.id = sensorConfig.typId
     WHERE sensorTypes.name <> 'GPS'
       AND sensorData.reading_time < DATE_SUB(NOW(), INTERVAL ? DAY)"
);
$statement->execute(array($telemetryRetentionDays));
$telemetryRowsDeleted = $statement->rowCount();

$statement = $pdo->prepare(
    "DELETE FROM ttnDataLoraBoatMonitor
     WHERE datetime < DATE_SUB(NOW(), INTERVAL ? DAY)"
);
$statement->execute(array($ttnDebugRetentionDays));
$ttnDebugRowsDeleted = $statement->rowCount();

$statement = $pdo->prepare(
    "DELETE FROM securityTokens
     WHERE createdAt < DATE_SUB(NOW(), INTERVAL ? DAY)"
);
$statement->execute(array($securityTokenRetentionDays));
$securityTokensDeleted = $statement->rowCount();

writeToLogFunction::info(
    'Privacy cleanup job finished.',
    __FILE__,
    array(
        'deletedLogs' => $deletedLogs,
        'resetCodesCleared' => $resetCodesCleared,
        'telemetryRowsDeleted' => $telemetryRowsDeleted,
        'gpsRowsDeleted' => $gpsRowsDeleted,
        'ttnDebugRowsDeleted' => $ttnDebugRowsDeleted,
        'securityTokensDeleted' => $securityTokensDeleted,
    )
);

echo json_encode(
    array(
        'status' => 'ok',
        'deletedLogs' => $deletedLogs,
        'resetCodesCleared' => $resetCodesCleared,
        'telemetryRowsDeleted' => $telemetryRowsDeleted,
        'gpsRowsDeleted' => $gpsRowsDeleted,
        'ttnDebugRowsDeleted' => $ttnDebugRowsDeleted,
        'securityTokensDeleted' => $securityTokensDeleted,
    ),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
