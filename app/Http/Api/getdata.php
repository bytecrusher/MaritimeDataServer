<?php
// Get data from DB for display in JS.
header('Content-Type: application/json');

require_once(__DIR__ . "/../../Application/myFunctions.func.php");
require_once(__DIR__ . "/../../Domain/Board/get_data.php");

$config  = new configuration();
$varDemoMode = $config::$demoMode;

$varIdent = $_POST['identifier'] ?? '';
$varToken = $_POST['securityToken'] ?? '';
$varData = $_POST['data'] ?? '';
$varSensorId = isset($_POST['sensorId']) ? (int)$_POST['sensorId'] : 0;
$varNrOfValues = isset($_POST['NrOfValues']) ? (int)$_POST['NrOfValues'] : 1;

if (empty($varIdent) || empty($varToken) || empty($varData)) {
    http_response_code(400);
    echo json_encode(array('error' => 'Missing parameters.'));
    exit;
}

$authenticatedUserId = myFunctions::validateSecurityToken($varIdent, $varToken);
if ($authenticatedUserId === false) {
    http_response_code(401);
    echo json_encode(array('error' => 'Invalid token.'));
    exit;
}

if (($varData !== "sensor") || ($varSensorId <= 0)) {
    http_response_code(400);
    echo json_encode(array('error' => 'Invalid request.'));
    exit;
}

if (!myFunctions::canUserAccessSensor($authenticatedUserId, $varSensorId)) {
    http_response_code(403);
    echo json_encode(array('error' => 'Access denied.'));
    exit;
}

$SensorType = myFunctions::getSensorConfig($varSensorId);
$mySensors = myFunctions::getLatestSensorData($varSensorId, max(1, min($varNrOfValues, 1000)));
if ($SensorType === false) {
    http_response_code(404);
    echo json_encode(array('error' => 'Sensor not found.'));
    exit;
}

$data = array();
foreach ($mySensors as &$mySensorSingle) {
    if ($varDemoMode == true) {
        $deviceOnline = true;
    } else {
        $boardId = myFunctions::getBoardBySensorId($varSensorId);
        $deviceOnline = ($boardId !== false) && checkDeviceIsOnline($boardId["boardId"]);
    }

    if ($deviceOnline) {
        $data[] = $mySensorSingle['sensorId'];
        array_push($data, $mySensorSingle['value1']);

        if ($SensorType['NrOfUsedSensors'] >= 2) {
            array_push($data, $mySensorSingle['value2']);
        }
        if ($SensorType['NrOfUsedSensors'] >= 3) {
            array_push($data, $mySensorSingle['value3']);
        }
        if ($SensorType['NrOfUsedSensors'] >= 4) {
            array_push($data, $mySensorSingle['value4']);
        }
    } else {
        $data[] = '.';
    }
}

echo json_encode($data);
