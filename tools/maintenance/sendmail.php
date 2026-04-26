<?php
require_once(dirname(__DIR__, 2) . "/bootstrap/app.php");
require_once(dirname(__DIR__, 2) . "/app/Application/NotificationService.php");

$result = NotificationService::sendPendingNotifications();
echo "Offline notifications sent: " . (int)($result['offlineSent'] ?? 0) . PHP_EOL;
echo "Sensor alert notifications sent: " . (int)($result['sensorAlertsSent'] ?? 0) . PHP_EOL;
