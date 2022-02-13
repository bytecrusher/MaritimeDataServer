<?php
/**
 * @author      Guntmar Höche
 * @license     TBD
 * @datetime    13 Februar 2022
 * @perpose     Collects all SensorId (with marked as "on Dashboard") of all board for a user. Called from JS
 * @input       "identifier" and "securitytoken" for user validation.
 * @output      return the sensorId as JSON.
 */

require_once("../func/dbConfig.func.php");
require_once("../func/myFunctions.func.php");

$varIdent = $_GET['identifier'];
$varToken = $_GET['securitytoken'];
$maxtimeout = strtotime("-15 Minutes");

$pdo = dbConfig::getInstance();

if (isset($varIdent) && isset($varToken) ) {
    if ( (!empty($varIdent)) && (!empty($varToken) ) ) {
    // ToDo check if identifier and token exist in DB.
        $userId = myFunctions::checkSecuritytoken($varIdent, $varToken);
        if (isset($userId)) {
            $myboards = myFunctions::getMyBoards($userId);
            $data = array();
            $maxValues = 100;

            // get all Sensors of User (via identifier)
            foreach($myboards as $singleRowmyboard) {
                $boardOnlineStatus = false;
                $mysensors = myFunctions::getAllSensorsOfBoardWithDashboard($singleRowmyboard['id']);
                if ((count($mysensors) == 0 )) {
                    echo "Keine Sensoren gefunden oder konfiguriert.";
                }

                foreach ($mysensors as $row) {
                    $data[] = $row;
                }
            }
            echo json_encode($data);
        }
    }
}

?>