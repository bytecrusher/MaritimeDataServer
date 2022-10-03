<?php
// TODO Find a way to secure the function. Only loggedin user shall be able to receive Data.
session_start();

//require_once("../func/dbConfig.func.php");
//require_once("../func/myFunctions.func.php");
//require_once("../func/user.class.php");
require_once(dirname(__FILE__)."/../func/dbupdateData.php");
$userobj = unserialize($_SESSION['userobj']);
header('Content-Type: application/json');

if ($_POST['update'] == "sensorOrdnerNumber") {
    dbUpdateData::updateSensorOrderNumber($_POST);
    echo json_encode("done");
}


?>
