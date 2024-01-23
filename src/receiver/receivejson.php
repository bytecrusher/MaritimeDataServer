<?php 
/**
 * receives data from Collectors/Devices (MDCs) in JSON format.
 * 
 * @author: Guntmar Hoeche
 * @license: TBD
 */

require_once(dirname(__FILE__, 2) . "/frontend/func/dbConfig.func.php");
require_once(dirname(__FILE__, 2) . '/configuration.php');
require_once(dirname(__FILE__, 2) . "/frontend/func/writeToLogFunction.func.php");
require_once(dirname(__FILE__, 2) . "/frontend/func/myFunctions.func.php");
require_once(dirname(__FILE__, 2) . "/frontend/func/board.class.php");

$config  = new configuration();

$apiKey_value = $config::$apiKey;
//$apiKey = $protocolVersion = $macAddress = $sensor = $sensorId = $location = $value1 = $value2 = $value3 = $value4 = $date = $time = $transmissionPath = "";
$apiKey = $macAddress = $sensor = $sensorId = $location = $value1 = $value2 = $value3 = $value4 = $date = $time = $transmissionPath = "";

$pdo2 = dbConfig::getInstance();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ttn_post = file_get_contents('php://input');
    $data = json_decode($ttn_post, true);

    $boardData = $data['board'];    // Array of board information from "POST"
    $sensors = $data['sensors'];    // Array of Sensors from "POST"
    if (isset($boardData['apiKey'])) {
        $apiKey = ($boardData['apiKey']);
    } else {
        writeToLogFunction::write_to_log("Wrong Api key!!", $_SERVER["SCRIPT_FILENAME"]);
    }

    $sql = null;
    if ($apiKey == $apiKey_value) {
        if ((isset($boardData['protocolVersion'])) && ($boardData['protocolVersion'] != null)) {
            if ($boardData['protocolVersion'] == "1") {
                $macAddress = test_input($boardData['macAddress']);
                $macAddressId = check_macAddress($macAddress, $pdo2);
                //$boardObj = new board($macAddress);
                foreach ($sensors as $key => &$sensor) {
                    $sensorId = null;
                    if ($sensor != null) {
                        $mySensorId = $owSensorAddress = null;
                        if (isset($sensor["sensorId"])) {
                            $mySensorId = test_input($sensor["sensorId"]);
                            $sensorId = $mySensorId;
                            if (isset($sensor["value1"])) {
                                $value1 = $sensor["value1"];
                            }
                            if (isset($sensor["value2"])) {
                                $value2 = $sensor["value2"];
                            }
                            if (isset($sensor["value3"])) {
                                $value3 = $sensor["value3"];
                            }
                            if (isset($sensor["value4"])) {
                                $value4 = $sensor["value4"];
                            }
                        } else {
                            if(isset($sensor["sensorAddress"])) {
                                $owSensorAddress = test_input($sensor["sensorAddress"]);
                                writeToLogFunction::write_to_log("owSensorAddress: " . $owSensorAddress, $_SERVER["SCRIPT_FILENAME"]);
                                $sensorId = checkOwSensorAddress($owSensorAddress, $macAddressId, $pdo2);
                                if (substr($owSensorAddress, 0, 2) === "28") {
                                    $value1 = test_input($sensor["value1"]);
                                    $value2 = null;
                                    $value3 = null;
                                } elseif (substr($owSensorAddress, 0, 2) === "26") {
                                    $value1 = test_input($sensor["value1"]);
                                    $value2 = test_input($sensor["value2"]);
                                }
                            } else {
                               $owSensorAddress = null; 
                            }
                            
                            writeToLogFunction::write_to_log("sensorId: " . $sensorId, $_SERVER["SCRIPT_FILENAME"]);
                            if (isset($sensor["value1"])) {
                                $value1 = test_input($sensor["value1"]);
                            }
                            if (isset($sensor["value2"])) {
                                $value2 = test_input($sensor["value2"]);
                            }
                            if (isset($sensor["value3"])) {
                                $value3 = test_input($sensor["value3"]);
                            }
                            if (isset($sensor["value4"])) {
                                $value4 = test_input($sensor["value4"]);
                            }
                        }
                        $date = test_input($sensor["date"]);
                        $time = test_input($sensor["time"]);

                        if(isset($sensor["transmissionPath"])) {
                            // 1 = WiFi, 2 = lora
                            $transmissionPath = test_input($sensor["transmissionPath"]);
                        } else {
                            $transmissionPath = 1;
                        }

                        $sql = "INSERT INTO sensorData (sensorId, value1, value2, value3, value4, val_date, val_time, transmissionPath)
                        VALUES ('" . $sensorId . "', '" . $value1 . "', '" . $value2 . "', '" . $value3 . "', '"  . $value4 . "', '" . $date . "', '" . $time . "', '" . $transmissionPath . "')";
                        try {
                            $pdo2->query($sql); //Invalid query
                        } catch (PDOException $ex) {
                            echo "An Error has occurred while run query.";
                            writeToLogFunction::write_to_log("An Error has occurred while run query.", $_SERVER["SCRIPT_FILENAME"]);
                            writeToLogFunction::write_to_log($ex, $_SERVER["SCRIPT_FILENAME"]);
                        }
                        if (myFunctions::getAlreadyNotified($macAddressId) == 1) {
                            myFunctions::unsetAlreadyNotified($macAddressId);
                            //todo: send mail: device is online.
                        }
                    }
                }
            }
        } else {
            echo "Wrong protocol version.";
            writeToLogFunction::write_to_log("Wrong protocol version.", $_SERVER["SCRIPT_FILENAME"]);
            die();
        }
    } else {
        echo "Wrong API Key provided.";
        writeToLogFunction::write_to_log("Wrong API Key provided.", $_SERVER["SCRIPT_FILENAME"]);
    }
} else {
    echo "No data posted with HTTP POST.";
    writeToLogFunction::write_to_log("No data posted with HTTP POST.", $_SERVER["SCRIPT_FILENAME"]);
}

function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function check_macAddress($macAddress, $pdo2)
{
    $sql = "SELECT id FROM boardConfig WHERE macAddress = '" . $macAddress . "' LIMIT 1";
    try {
        $idMacAddress_temp = $pdo2->query($sql); //Invalid query
        $idMacAddress = $idMacAddress_temp->fetch();
    } catch (PDOException $ex) {
        echo "An Error has occurred while check macAddress";
        writeToLogFunction::write_to_log("An Error has occurred while check macAddress", $_SERVER["SCRIPT_FILENAME"]);
    }

    if ( (!isset($idMacAddress['id']) ) || ($idMacAddress['id'] == null) ) {
        $statement = $pdo2->prepare("INSERT INTO boardConfig (macAddress, ownerUserId, name) VALUES (?, ?, ?)");
        $statement->execute(array($macAddress, 1, "- new imported -"));     // Default Owner User
        $neue_id = $pdo2->lastInsertId();
        writeToLogFunction::write_to_log("New Board with id $neue_id created", $_SERVER["SCRIPT_FILENAME"]);

        return $neue_id;
    } else {
        return $idMacAddress['id'];
    }
}

function checkOwSensorAddress($sensorAddress, $macAddressId, $pdo2)
{
    $sql = "SELECT id FROM sensorConfig WHERE sensorAddress = '" . $sensorAddress . "' LIMIT 1";
    try {
        $idSensorAddress_temp = $pdo2->query($sql); //Invalid query
        $sensorAddressId = $idSensorAddress_temp->fetch();
        if ($sensorAddress != "00000000") {
            if (!$sensorAddressId) { // if no sensor found in DB, it should be created.
                $sensorAddressFamilyCode = substr($sensorAddress, 0, 2);
                $sql2 = "SELECT id FROM sensorTypes WHERE oneWireFamilyCode = '" . $sensorAddressFamilyCode . "' LIMIT 1";
                $idSensorTypes_temp = $pdo2->query($sql2); //Invalid query
                $idSensorTypes = $idSensorTypes_temp->fetch();
                writeToLogFunction::write_to_log("sensor: " . $idSensorTypes, $_SERVER["SCRIPT_FILENAME"]);
                $statement2 = "INSERT INTO sensorConfig (boardId, sensorAddress, typId) VALUES ('$macAddressId', '$sensorAddress', '" . $idSensorTypes['id'] . "')";
                $insertSuccess = $pdo2->exec($statement2);
                writeToLogFunction::write_to_log("Insert sensorConfig " . $statement2 . ", " . $insertSuccess, $_SERVER["SCRIPT_FILENAME"]);
                if ($insertSuccess) {
                    $neue_id = $pdo2->lastInsertId();
                    writeToLogFunction::write_to_log("New Sensor with id $neue_id created", $_SERVER["SCRIPT_FILENAME"]);
                    return $neue_id;
                } else {
                    return false;
                }
            } else {
                return $sensorAddressId['id'];
            }
        }
    } catch (PDOException $ex) {
        writeToLogFunction::write_to_log("An Error has occurred while add / check sensor. ", $_SERVER["SCRIPT_FILENAME"]);
    }
}