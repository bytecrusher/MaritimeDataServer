<?php
// TODO Find a way to secure the function. Only logged in user shall be able to receive Data.
require_once dirname(__DIR__, 3) . "/bootstrap/app.php";
mds_start_session();

require_once(__DIR__ . "/../../Infrastructure/Database/dbConfig.func.php");
require_once(__DIR__ . "/../../Application/myFunctions.func.php");

$pdo = dbConfig::getInstance();
if ( count($_GET) == 0 ) {
  die("Parameter error.");
}
header('Content-Type: application/json');

$sessionUserId = isset($_SESSION['userId']) ? (int)$_SESSION['userId'] : 0;
$maxValues = isset($_GET['maxValues']) ? (int)$_GET['maxValues'] : 0;
$sensorId = isset($_GET['sensorId']) ? (int)$_GET['sensorId'] : 0;

if ($sessionUserId <= 0) {
  http_response_code(401);
  echo json_encode(array('error' => 'Authentication required.'));
  exit;
}

if (($sensorId <= 0) || ($maxValues <= 0)) {
  http_response_code(400);
  echo json_encode(array('error' => 'Invalid parameters.'));
  exit;
}

if (!myFunctions::canUserAccessSensor($sessionUserId, $sensorId)) {
  http_response_code(403);
  echo json_encode(array('error' => 'Access denied.'));
  exit;
}

$maxValues = min($maxValues, 1000);
$statement = $pdo->prepare(
  "SELECT * FROM (
    SELECT id, sensorId, value1, value2, value3, value4, val_date, val_time, reading_time
    FROM sensorData
    WHERE sensorId = ?
    ORDER BY id DESC
    LIMIT $maxValues
  ) sensorData ORDER BY id ASC"
);
$statement->execute(array($sensorId));
$data = $statement->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($data);
