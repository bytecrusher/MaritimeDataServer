<?php
// TODO Find a way to secure the function. Only logged in user shall be able to receive Data.
session_start();

require_once(__DIR__."/../func/dbUpdateData.php");
$userObj = unserialize($_SESSION['userObj']);
header('Content-Type: application/json');

if ($_POST['update'] == "sensorOrderNumber") {
    dbUpdateData::updateSensorOrderNumber($_POST);
    echo json_encode("done");
}