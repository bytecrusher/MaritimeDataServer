<?php
// Get data from DB for display in JS.
require_once("../func/myFunctions.func.php");

$varIdent = $_POST['identifier'];
$varToken = $_POST['securitytoken'];
$vardata = $_POST['data'];
$varsensorId = $_POST['sensorId'];
$varNrOfValues = $_POST['NrOfValues'];

$maxtimeout = strtotime("-15 Minutes");

if (isset($varIdent) && isset($varToken) && isset($vardata)) {
    if ( (!empty($varIdent)) && (!empty($varToken) ) && (!empty($vardata))) {
        // ToDo check if identifier and token exist in DB.
        if (($vardata == "sensor") && isset($varsensorId) ) {
            $mysensors = myFunctions::getLatestSensorData($varsensorId, $varNrOfValues);
            if (isset($mysensors)) {
                $data = array();
                foreach ($mysensors as &$mysensorSingle) {
                    $dbtimestamp = strtotime($mysensorSingle['reading_time']);
                    if ($dbtimestamp > $maxtimeout) {
                        $data[] = $mysensorSingle['value1'];
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