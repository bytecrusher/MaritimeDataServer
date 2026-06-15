<?php
require_once dirname(__DIR__, 3) . "/bootstrap/app.php";
mds_start_session();
header('Content-Type: application/json');

require_once(__DIR__ . "/../../Application/dbUpdateData.php");
require_once(__DIR__ . "/../../Application/myFunctions.func.php");

if (!isset($_SESSION['userId']) || ((int)$_SESSION['userId'] <= 0)) {
    http_response_code(401);
    echo json_encode(array('error' => 'Authentication required.'));
    exit;
}

if (!mds_verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(array('error' => 'Invalid CSRF token.'));
    exit;
}

if (isset($_POST['update']) && $_POST['update'] == "sensorOrderNumber") {
    $sensorId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if (!myFunctions::canUserAccessSensor((int)$_SESSION['userId'], $sensorId)) {
        http_response_code(403);
        echo json_encode(array('error' => 'Access denied.'));
        exit;
    }
    if (!dbUpdateData::updateSensorOrderNumber($_POST)) {
        http_response_code(400);
        echo json_encode(array('error' => 'Sensor order number not saved.'));
        exit;
    }
    echo json_encode("done");
} else {
    http_response_code(400);
    echo json_encode(array('error' => 'Invalid update request.'));
}
