<?php
require_once(dirname(__DIR__, 2) . "/bootstrap/app.php");
require_once(dirname(__DIR__, 2) . "/app/Application/NotificationService.php");
require_once(dirname(__DIR__, 2) . "/app/Infrastructure/Logging/writeToLogFunction.func.php");

writeToLogFunction::info('sendmail maintenance job started.', __FILE__);
$result = NotificationService::sendPendingNotifications();
echo "Offline notifications sent: " . (int)($result['offlineSent'] ?? 0) . PHP_EOL;
echo "Sensor alert notifications sent: " . (int)($result['sensorAlertsSent'] ?? 0) . PHP_EOL;
writeToLogFunction::info('sendmail maintenance job finished.', __FILE__, $result);
