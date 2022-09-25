<?php
// TODO Find a way to secure the function. Only loggedin user shall be able to receive Data.
session_start();

require_once("../func/dbConfig.func.php");
require_once("../func/myFunctions.func.php");
require_once("../func/user.class.php");

$userobj = unserialize($_SESSION['userobj']);

  $pdo = dbConfig::getInstance();
  if ( count($_GET) == 0 ) {
    die("Parameter error.");
  }
  header('Content-Type: application/json');

  $maxValues = $_GET['maxValues'];
  $sensorId = $_GET['sensorId'];

  // TODO change to pdo
  $query = sprintf("SELECT * FROM (SELECT id, sensorid, value1, value2, val_date, val_time, reading_time FROM sensordata WHERE sensorid = " . $sensorId . " ORDER BY id DESC LIMIT " . $maxValues . ") sensordata ORDER BY id ASC");
  $result = $pdo->query($query);

  $data = array();
  foreach ($result as $row) {
    $data[] = $row;
  }
  echo json_encode($data);
?>
