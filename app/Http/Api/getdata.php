<?php
// Get data from DB for display in JS.
header('Content-Type: application/json');
require_once dirname(__DIR__, 3) . "/bootstrap/app.php";
mds_start_session();
header('Cache-Control: no-store, private');

require_once(__DIR__ . "/../../Application/myFunctions.func.php");
require_once(__DIR__ . "/../../Domain/Board/get_data.php");

$config  = new configuration();
$varDemoMode = $config::$demoMode;

$varIdent = $_POST['identifier'] ?? '';
$varToken = $_POST['securityToken'] ?? '';
$varData = $_POST['data'] ?? '';
$varSensorId = isset($_POST['sensorId']) ? (int)$_POST['sensorId'] : 0;
$varNrOfValues = isset($_POST['NrOfValues']) ? (int)$_POST['NrOfValues'] : 1;

if (empty($varData)) {
    http_response_code(400);
    echo json_encode(array('error' => 'Missing parameters.'));
    exit;
}

$authenticatedUserId = false;
if (!empty($varIdent) && !empty($varToken)) {
    $authenticatedUserId = myFunctions::validateSecurityToken($varIdent, $varToken);
}

if ($authenticatedUserId === false && isset($_SESSION['userId']) && (int)$_SESSION['userId'] > 0) {
    $authenticatedUserId = (int)$_SESSION['userId'];
}

if ($authenticatedUserId === false) {
    http_response_code(401);
    echo json_encode(array('error' => 'Authentication required.'));
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
session_write_close();
$mySensors = myFunctions::getLatestSensorData($varSensorId, max(1, min($varNrOfValues, 1000)));
if ($SensorType === false) {
    http_response_code(404);
    echo json_encode(array('error' => 'Sensor not found.'));
    exit;
}

if (($_POST['includeStatus'] ?? '') === '1') {
    $board = new board((int)$SensorType['boardId']);
    $maxAge = max(1, (int)$board->getOfflineDataTimer());
    $boardOnline = $varDemoMode || checkDeviceIsOnline((int)$SensorType['boardId']);
    $lastReceived = $mySensors[0]['receivedAt'] ?? null;
    $current = $lastReceived !== null && $lastReceived !== false && (int)$lastReceived >= time() - $maxAge * 60;
    $visible = myFunctions::getAllSensorsOfBoardWithDashboardWithTypeName((int)$SensorType['boardId']) ?: array();
    $ids = array();
    foreach ($visible as $sensor) {
        if (myFunctions::canUserAccessSensor($authenticatedUserId, (int)$sensor['id'])) {
            $ids[] = (int)$sensor['id'];
        }
    }
    $activity = myFunctions::getSensorActivitySummary($ids, $maxAge);
    $values = array($varSensorId);
    $row = $mySensors[0] ?? array();
    $type = myFunctions::getSensorType($SensorType['typId']);
    for ($channel = 1; $channel <= min(4, (int)$SensorType['NrOfUsedSensors']); $channel++) {
        $value = $row['value' . $channel] ?? null;
        $values[] = ($type['name'] ?? '') === 'DS18B20' && !TemperatureReading::valid($value) ? null : $value;
    }
    echo json_encode(array(
        'values' => $values,
        'current' => $current,
        'freshnessLabel' => mds_current_language() === 'de' ? ($current ? 'Aktuell' : 'Veraltet / keine neuen Daten') : ($current ? 'Current' : 'Stale / no new data'),
        'lastReceivedAt' => $lastReceived ? gmdate('c', (int)$lastReceived) : null,
        'boardOnline' => (bool)$boardOnline,
        'boardLabel' => mds_t($boardOnline ? 'common.online' : 'common.offline'),
        'activityLabel' => mds_t('internal.sensor_activity_badge', array($activity['current'], $activity['configured'])),
        'activityHint' => mds_t('internal.sensor_activity_hint', array($activity['configured'], $activity['withData'], $activity['current'])),
    ));
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
