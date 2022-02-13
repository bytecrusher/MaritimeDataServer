<?php
/**
 * @author      Guntmar Höche
 * @license     TBD
 * @datetime    13 Februar 2022
 * @perpose     Get the last x (maxValues) values of a given Sensor.
 * @input       "identivier" and "securitytoken" for user validation, "maxValues" and "sensorId" for getting the data.
 * @output      Return data as JSON format.
 */

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

$varIdent = $_GET['identifier'];
$varToken = $_GET['securitytoken'];
$maxValues = $_GET['maxValues'];
$sensorId = $_GET['sensorId'];

$query = sprintf("SELECT id, sensorid, value1, value2, val_date, val_time, reading_time FROM sensordata WHERE sensorid = " . $sensorId . " ORDER BY id ASC LIMIT " . $maxValues);
$result = $pdo->query($query);

$data = array();
foreach ($result as $row) {
  $data[] = $row;
}
echo json_encode($data);
?>
