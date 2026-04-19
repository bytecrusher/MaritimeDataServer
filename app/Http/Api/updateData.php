<?php
session_start();
header('Content-Type: application/json');

require_once(__DIR__ . "/../../Application/dbUpdateData.php");

if (!isset($_SESSION['userId']) || ((int)$_SESSION['userId'] <= 0)) {
    http_response_code(401);
    echo json_encode(array('error' => 'Authentication required.'));
    exit;
}

if (isset($_POST['update']) && $_POST['update'] == "sensorOrderNumber") {
    dbUpdateData::updateSensorOrderNumber($_POST);
    echo json_encode("done");
} else {
    http_response_code(400);
    echo json_encode(array('error' => 'Invalid update request.'));
}
