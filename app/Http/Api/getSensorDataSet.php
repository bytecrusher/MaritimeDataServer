<?php
require_once dirname(__DIR__, 3) . "/bootstrap/app.php";
mds_start_session();

require_once(__DIR__ . "/../../Infrastructure/Database/dbConfig.func.php");
require_once(__DIR__ . "/../../Application/myFunctions.func.php");
require_once(__DIR__ . "/../../Application/SensorChartHistory.php");

$pdo = dbConfig::getInstance();
if ( count($_GET) == 0 ) {
  die("Parameter error.");
}
header('Content-Type: application/json');
header('Cache-Control: no-store, private');

$sessionUserId = isset($_SESSION['userId']) ? (int)$_SESSION['userId'] : 0;
$maxValues = isset($_GET['maxValues']) ? (int)$_GET['maxValues'] : 1000;
$days = isset($_GET['days']) ? (int)$_GET['days'] : 7;
$sensorId = isset($_GET['sensorId']) ? (int)$_GET['sensorId'] : 0;

if ($sessionUserId <= 0) {
  http_response_code(401);
  echo json_encode(array('error' => 'Authentication required.'));
  exit;
}

if (($sensorId <= 0) || ($maxValues <= 0) || !in_array($days, array(1, 7, 14, 30), true)) {
  http_response_code(400);
  echo json_encode(array('error' => 'Invalid parameters.'));
  exit;
}

if (!myFunctions::canUserAccessSensor($sessionUserId, $sensorId)) {
  http_response_code(403);
  echo json_encode(array('error' => 'Access denied.'));
  exit;
}

$maxValues = max(14, min($maxValues, 1000));
// Release the session lock before loading history for concurrent chart requests.
session_write_close();
$to = time();
$from = $to - $days * 86400;
$zone = new DateTimeZone('Europe/Berlin');
$local = static fn($time) => (new DateTimeImmutable('@' . $time))->setTimezone($zone)->format('Y-m-d H:i:s');
// Include a DST boundary margin; the UTC filter in summarize applies the exact window.
$pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
$statement = $pdo->prepare(
  "SELECT id, sensorId, value1, value2, value3, value4, val_date, val_time, reading_time,
   UNIX_TIMESTAMP(reading_time) AS receivedTimestamp
   FROM sensorData WHERE sensorId = ?
   AND (STR_TO_DATE(CONCAT(NULLIF(val_date, ''), ' ', NULLIF(val_time, '')), '%d.%m.%Y %H:%i:%s')
   BETWEEN ? AND ? OR UNIX_TIMESTAMP(reading_time) BETWEEN ? AND ?)"
);
$statement->execute(array($sensorId, $local($from - 3600), $local($to + 3600), $from, $to));
$data = SensorChartHistory::summarize($statement, $from, $to, $maxValues);
$statement->closeCursor();
$pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
echo json_encode($data);
