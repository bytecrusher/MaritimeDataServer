<?php
// Get historical sensor data for a logged-in user's sensor.
session_start();

require_once("../func/dbConfig.func.php");
require_once("../func/myFunctions.func.php");

header('Content-Type: application/json');

if (!myFunctions::is_checked_in()) {
  http_response_code(401);
  echo json_encode(array('error' => 'Authentication required.'));
  exit;
}

if (!isset($_GET['maxValues']) || !isset($_GET['sensorId'])) {
  http_response_code(400);
  echo json_encode(array('error' => 'Parameter error.'));
  exit;
}

$maxValues = filter_input(INPUT_GET, 'maxValues', FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)));
$sensorId = filter_input(INPUT_GET, 'sensorId', FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)));

if ($maxValues === false || $sensorId === false || $maxValues === null || $sensorId === null) {
  http_response_code(400);
  echo json_encode(array('error' => 'Parameter error.'));
  exit;
}

$maxValues = (int) $maxValues;
$sensorId = (int) $sensorId;

$pdo = dbConfig::getInstance();
$statement = $pdo->prepare("SELECT * FROM (
    SELECT sensorData.id, sensorData.sensorId, sensorData.value1, sensorData.value2, sensorData.value3, sensorData.value4, sensorData.val_date, sensorData.val_time, sensorData.reading_time
    FROM sensorData
    INNER JOIN sensorConfig ON sensorConfig.id = sensorData.sensorId
    INNER JOIN boardConfig ON boardConfig.id = sensorConfig.boardId
    WHERE sensorData.sensorId = :sensorId AND boardConfig.ownerUserId = :userId
    ORDER BY sensorData.id DESC
    LIMIT " . $maxValues . "
  ) sensorData ORDER BY id ASC");
$statement->execute(array('sensorId' => $sensorId, 'userId' => $_SESSION['userId']));

echo json_encode($statement->fetchAll(PDO::FETCH_ASSOC));
