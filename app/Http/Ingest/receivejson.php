<?php
/**
 * receives data from Collectors/Devices (MDCs) in JSON format.
 * 
 * @author: Guntmar Hoeche
 * @license: TBD
 */

require_once(dirname(__FILE__, 2) . "/../Infrastructure/Database/dbConfig.func.php");
require_once(dirname(__FILE__, 2) . '/../Infrastructure/Config/configuration.php');
require_once(dirname(__FILE__, 2) . "/../Infrastructure/Logging/writeToLogFunction.func.php");
require_once(dirname(__FILE__, 2) . "/../Application/myFunctions.func.php");
require_once(dirname(__FILE__, 2) . "/../Domain/Board/board.class.php");

#require_once("/src/frontend/func/writeToLogFunction.func.php");

$config  = new configuration();

$apiKey_value = $config::$apiKey;
//$apiKey = $protocolVersion = $macAddress = $sensor = $sensorId = $location = $value1 = $value2 = $value3 = $value4 = $date = $time = $transmissionPath = "";
$apiKey = $macAddress = $sensor = $sensorId = $location = $value1 = $value2 = $value3 = $value4 = $date = $time = $transmissionPath = "";

$pdo2 = dbConfig::getInstance();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ttn_post = file_get_contents('php://input');
    $data = json_decode($ttn_post, true);

    if (!is_array($data)) {
        echo "Invalid JSON payload.";
        writeToLogFunction::error(
            "Invalid JSON payload received by receivejson.php",
            $_SERVER["SCRIPT_FILENAME"],
            array('rawPayload' => $ttn_post)
        );
        exit;
    }

    $boardData = $data['board'] ?? array();
    $sensors = $data['sensors'] ?? array();
    writeToLogFunction::info(
        'receivejson request received.',
        $_SERVER["SCRIPT_FILENAME"],
        array(
            'boardKeys' => array_keys($boardData),
            'sensorCount' => is_array($sensors) ? count($sensors) : 0
        )
    );

    if (isset($boardData['apiKey'])) {
        $apiKey = ($boardData['apiKey']);
    } else {
        writeToLogFunction::warning("Missing API key in board payload.", $_SERVER["SCRIPT_FILENAME"]);
    }

    if ($apiKey == $apiKey_value) {
        if ((isset($boardData['protocolVersion'])) && ($boardData['protocolVersion'] != null)) {
            if ($boardData['protocolVersion'] == "1") {
                $macAddress = test_input($boardData['macAddress']);
                $macAddressId = check_macAddress($macAddress, $pdo2);
                $insertStatement = $pdo2->prepare(
                    "INSERT INTO sensorData (sensorId, value1, value2, value3, value4, val_date, val_time, transmissionPath)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );

                foreach ($sensors as $key => &$sensor) {
                    $sensorId = null;
                    $value1 = $value2 = $value3 = $value4 = "";
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
                            $transmissionPath = test_input($sensor["transmissionPath"]);
                        } elseif (isset($sensor["transmissionpath"])) {
                            $transmissionPath = test_input($sensor["transmissionpath"]);
                        } else {
                            $transmissionPath = 1;
                        }

                        try {
                            $insertStatement->execute(array(
                                $sensorId,
                                $value1,
                                $value2,
                                $value3,
                                $value4,
                                $date,
                                $time,
                                $transmissionPath
                            ));
                            writeToLogFunction::debug(
                                'sensorData row inserted.',
                                $_SERVER["SCRIPT_FILENAME"],
                                array(
                                    'boardId' => $macAddressId,
                                    'sensorId' => $sensorId,
                                    'transmissionPath' => $transmissionPath,
                                    'date' => $date,
                                    'time' => $time
                                )
                            );
                        } catch (PDOException $ex) {
                            echo "An Error has occurred while run query.";
                            writeToLogFunction::error("An error has occurred while inserting sensorData.", $_SERVER["SCRIPT_FILENAME"]);
                            writeToLogFunction::exception(
                                $ex,
                                $_SERVER["SCRIPT_FILENAME"],
                                array(
                                    'boardId' => $macAddressId,
                                    'sensorId' => $sensorId,
                                    'payloadSensor' => $sensor
                                )
                            );
                        }
                        if (myFunctions::getAlreadyNotified($macAddressId) == 1) {
                            myFunctions::unsetAlreadyNotified($macAddressId);
                            writeToLogFunction::info(
                                'Board was marked as reachable again. alreadyNotified reset.',
                                $_SERVER["SCRIPT_FILENAME"],
                                array('boardId' => $macAddressId)
                            );
                        }
                    }
                }
                writeToLogFunction::info(
                    'receivejson processing finished successfully.',
                    $_SERVER["SCRIPT_FILENAME"],
                    array('boardId' => $macAddressId)
                );
            }
        } else {
            echo "Wrong protocol version.";
            writeToLogFunction::warning("Wrong protocol version.", $_SERVER["SCRIPT_FILENAME"], array('board' => $boardData));
            die();
        }
    } else {
        echo "Wrong API Key provided.";
        writeToLogFunction::warning(
            "Wrong API Key provided.",
            $_SERVER["SCRIPT_FILENAME"],
            array('providedApiKey' => $apiKey)
        );
    }
} else {
    echo "No data posted with HTTP POST.";
    writeToLogFunction::warning("No data posted with HTTP POST.", $_SERVER["SCRIPT_FILENAME"]);
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
    try {
        $statement = $pdo2->prepare("SELECT id FROM boardConfig WHERE macAddress = ? LIMIT 1");
        $statement->execute(array($macAddress));
        $idMacAddress = $statement->fetch();
    } catch (PDOException $ex) {
        echo "An Error has occurred while check macAddress";
        writeToLogFunction::exception($ex, $_SERVER["SCRIPT_FILENAME"], array('macAddress' => $macAddress));
    }

    if ( (!isset($idMacAddress['id']) ) || ($idMacAddress['id'] == null) ) {
        $statement = $pdo2->prepare("INSERT INTO boardConfig (macAddress, ownerUserId, name) VALUES (?, ?, ?)");
        $statement->execute(array($macAddress, 1, "- new imported -"));     // Default Owner User
        $neue_id = $pdo2->lastInsertId();
        writeToLogFunction::info("New board created during receivejson import.", $_SERVER["SCRIPT_FILENAME"], array('boardId' => $neue_id, 'macAddress' => $macAddress));

        return $neue_id;
    } else {
        return $idMacAddress['id'];
    }
}

function checkOwSensorAddress($sensorAddress, $macAddressId, $pdo2)
{
    try {
        $statement = $pdo2->prepare("SELECT id FROM sensorConfig WHERE sensorAddress = ? LIMIT 1");
        $statement->execute(array($sensorAddress));
        $sensorAddressId = $statement->fetch();
        if ($sensorAddress != "00000000") {
            if (!$sensorAddressId) { // if no sensor found in DB, it should be created.
                $sensorAddressFamilyCode = substr($sensorAddress, 0, 2);
                $statementType = $pdo2->prepare("SELECT id FROM sensorTypes WHERE oneWireFamilyCode = ? LIMIT 1");
                $statementType->execute(array($sensorAddressFamilyCode));
                $idSensorTypes = $statementType->fetch();
                $statement2 = $pdo2->prepare("INSERT INTO sensorConfig (boardId, sensorAddress, typId) VALUES (?, ?, ?)");
                $insertSuccess = $statement2->execute(array($macAddressId, $sensorAddress, $idSensorTypes['id']));
                writeToLogFunction::info(
                    'Sensor auto-created from one-wire address.',
                    $_SERVER["SCRIPT_FILENAME"],
                    array(
                        'boardId' => $macAddressId,
                        'sensorAddress' => $sensorAddress,
                        'sensorTypeId' => $idSensorTypes['id']
                    )
                );
                if ($insertSuccess) {
                    $neue_id = $pdo2->lastInsertId();
                    writeToLogFunction::info("New sensor created.", $_SERVER["SCRIPT_FILENAME"], array('sensorId' => $neue_id));
                    return $neue_id;
                } else {
                    return false;
                }
            } else {
                return $sensorAddressId['id'];
            }
        }
    } catch (PDOException $ex) {
        writeToLogFunction::exception(
            $ex,
            $_SERVER["SCRIPT_FILENAME"],
            array('sensorAddress' => $sensorAddress, 'boardId' => $macAddressId)
        );
    }
}
