<?php
// TODO Find a way to secure the function. Only loggedin user shall be able to receive Data.
session_start();

require_once(__DIR__."/../func/dbUpdateData.php");
$userobj = unserialize($_SESSION['userobj']);
header('Content-Type: application/json');

if ($_POST['update'] == "sensorOrdnerNumber") {
    dbUpdateData::updateSensorOrderNumber($_POST);
    echo json_encode("done");
}
?>
