<?php
// Get data from DB for display in JS.
require_once("../func/myFunctions.func.php");
require_once("../func/get_data.php");

//require_once(dirname(__FILE__, 2) . '/../../configuration.php');
$config  = new configuration();
$varDemoMode = $config::$demoMode;

$varIdent = $_POST['identifier'];
$varToken = $_POST['securityToken'];
$varData = $_POST['data'];
$varSensorId = $_POST['sensorId'];
$varNrOfValues = $_POST['NrOfValues'];

if (isset($varIdent) && isset($varToken) && isset($varData)) {
    if ( (!empty($varIdent)) && (!empty($varToken) ) && (!empty($varData))) {
        // TODO check if identifier and token exist in DB.
        if (($varData == "sensor") && isset($varSensorId) ) {
            $SensorType = (myFunctions::getSensorConfig($varSensorId));
            $mySensors = myFunctions::getLatestSensorData($varSensorId, $varNrOfValues);
            if (isset($mySensors)) {
                $data = array();
                foreach ($mySensors as &$mySensorSingle) {
                    $dbTimestamp = strtotime($mySensorSingle['reading_time']);

                    if ($varDemoMode == true) {
                        $maxTimeout = strtotime("-15 Years");
                        $deviceOnline = true;
                    } else {
                        $boardId = myFunctions::getBoardBySensorId($varSensorId);
                        $deviceOnline = checkDeviceIsOnline($boardId["boardId"]);
                        //$maxTimeout = strtotime("-15 Minutes");
                        //$maxTimeout = strtotime("-" . $boardObj->getOfflineDataTimer() . " Minutes"); // For show Online / Offline
                    }

                    //if ($dbTimestamp > $maxTimeout) {
                    if ($deviceOnline) {
                        //$data[] = $mySensorSingle['value1'];
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
            }
        }
    }
}