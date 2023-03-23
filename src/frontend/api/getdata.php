<?php
// Get data from DB for display in JS.
require_once("../func/myFunctions.func.php");

//require_once(dirname(__FILE__, 2) . '/../../configuration.php');
$config  = new configuration();
$varDemoMode = $config::$demoMode;

$varIdent = $_POST['identifier'];
$varToken = $_POST['securitytoken'];
$vardata = $_POST['data'];
$varsensorId = $_POST['sensorId'];
$varNrOfValues = $_POST['NrOfValues'];

if ($varDemoMode == true) {
    $maxtimeout = strtotime("-15 Years");
} else {
    $maxtimeout = strtotime("-15 Minutes");
}

if (isset($varIdent) && isset($varToken) && isset($vardata)) {
    if ( (!empty($varIdent)) && (!empty($varToken) ) && (!empty($vardata))) {
        // TODO check if identifier and token exist in DB.
        if (($vardata == "sensor") && isset($varsensorId) ) {
            $SensorType = (myFunctions::getSensorConfig($varsensorId));
            $mysensors = myFunctions::getLatestSensorData($varsensorId, $varNrOfValues);
            if (isset($mysensors)) {
                $data = array();
                foreach ($mysensors as &$mysensorSingle) {
                    $dbtimestamp = strtotime($mysensorSingle['reading_time']);
                    if ($dbtimestamp > $maxtimeout) {
                        //$data[] = $mysensorSingle['value1'];
                        $data[] = $mysensorSingle['sensorid'];
                        array_push($data, $mysensorSingle['value1']);

                        if ($SensorType['NrOfUsedSensors'] >= 2) {
                            array_push($data, $mysensorSingle['value2']);
                        }
                        if ($SensorType['NrOfUsedSensors'] >= 3) {
                            array_push($data, $mysensorSingle['value3']);
                        }
                        if ($SensorType['NrOfUsedSensors'] >= 4) {
                            array_push($data, $mysensorSingle['value4']);
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

?>