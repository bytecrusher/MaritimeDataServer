<?php
// TODO Find a way to secure the function. Only logged in user shall be able to receive Data.
session_start();

require_once("../func/dbConfig.func.php");
require_once("../func/myFunctions.func.php");
require_once("../func/user.class.php");

$userObj = unserialize($_SESSION['userObj']);

$pdo = dbConfig::getInstance();
if ( count($_GET) == 0 ) {
  die("Parameter error.");
}
header('Content-Type: application/json');

$maxValues = $_GET['maxValues'];
$sensorId = $_GET['sensorId'];

// TODO change to pdo
if (!$sensorId == null) {
  $query = sprintf("SELECT * FROM (SELECT id, sensorId, value1, value2, value3, value4, val_date, val_time, reading_time FROM sensorData WHERE sensorId = " . $sensorId . " ORDER BY id DESC LIMIT " . $maxValues . ") sensorData ORDER BY id ASC");
  $result = $pdo->query($query);


  //$statement = $pdo->prepare("SELECT * FROM (SELECT id, sensorId, value1, value2, val_date, val_time, reading_time FROM sensorData WHERE sensorId = " . $sensorId . " ORDER BY id DESC LIMIT " . $maxValues . ") sensorData ORDER BY id ASC VALUES (?, ?)");
  //$statement->execute(array($sensorId, $maxValues));
  //$neue_id = $pdo->lastInsertId();
  //$result = $sensortyps->fetchAll(PDO::FETCH_ASSOC);



  $data = array();
  foreach ($result as $row) {
    $data[] = $row;
  }
  echo json_encode($data);
}
