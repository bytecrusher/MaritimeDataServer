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

$api_key_value = $config::$api_key;
$api_key = $protocollversion = $macAddress = $sensor = $sensorid = $location = $value1 = $value2 = $value3 = $value4 = $date = $time = $transmissionpath = "";

$pdo2 = dbConfig::getInstance();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ttn_post = file_get_contents('php://input');
    $data = json_decode($ttn_post, true);

    $boardData = $data['board'];    // Array of Board informations from "POST"
    $sensors = $data['sensors'];    // Array of Sensors from "POST"
    if (isset($boardData['api_key'])) {
        $api_key = ($boardData['api_key']);
    } else {
        writeToLogFunction::write_to_log("Wrong Api key!!", $_SERVER["SCRIPT_FILENAME"]);
    }

    $sql = null;
    if ($api_key == $api_key_value) {
        if ((isset($boardData['protocollversion'])) && ($boardData['protocollversion'] != null)) {
            if ($boardData['protocollversion'] == "1") {
                $macAddress = test_input($boardData['macAddress']);
                $macaddressid = check_macAddresse($macAddress, $pdo2);
                $boardobj = new board($macAddress);
                foreach ($sensors as $key => &$sensor) {
                    $sensorid = null;
                    if ($sensor != null) {
                        $mysensorid = $owsensorAddress = null;
                        if (isset($sensor["sensorId"])) {
                            $mysensorid = test_input($sensor["sensorId"]);
                            $sensorid = $mysensorid;
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
                                $owsensorAddress = test_input($sensor["sensorAddress"]);
                                writeToLogFunction::write_to_log("owsensorAddress: " . $owsensorAddress, $_SERVER["SCRIPT_FILENAME"]);
                                $sensorid = check_owsensorAddress($owsensorAddress, $macaddressid, $pdo2);
                                if (substr($owsensorAddress, 0, 2) === "28") {
                                    $value1 = test_input($sensor["value1"]);
                                    $value2 = null;
                                    $value3 = null;
                                } elseif (substr($owsensorAddress, 0, 2) === "26") {
                                    $value1 = test_input($sensor["value1"]);
                                    $value2 = test_input($sensor["value2"]);
                                }
                            } else {
                               $owsensorAddress = null; 
                            }
                            
                            writeToLogFunction::write_to_log("sensorid: " . $sensorid, $_SERVER["SCRIPT_FILENAME"]);
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

                        if(isset($sensor["transmissionpath"])) {
                            // 1 = wifi, 2 = lora
                            $transmissionpath = test_input($sensor["transmissionpath"]);
                        } else {
                            $transmissionpath = 1;
                        }

                        $sql = "INSERT INTO sensordata (sensorid, value1, value2, value3, value4, val_date, val_time, transmissionpath)
                        VALUES ('" . $sensorid . "', '" . $value1 . "', '" . $value2 . "', '" . $value3 . "', '"  . $value4 . "', '" . $date . "', '" . $time . "', '" . $transmissionpath . "')";
                        try {
                            $pdo2->query($sql); //Invalid query
                        } catch (PDOException $ex) {
                            echo "An Error has occurred while run query.";
                            writeToLogFunction::write_to_log("An Error has occurred while run query.", $_SERVER["SCRIPT_FILENAME"]);
                            writeToLogFunction::write_to_log($ex, $_SERVER["SCRIPT_FILENAME"]);
                        }
                        if (myFunctions::getAlreadyNotified($macaddressid) == 1) {
                            myFunctions::unsetAlreadyNotified($macaddressid);
                            //todo: send mail: device is online.
                        }
                    }
                }
            }
        } else {
            echo "Wrong protocoll version.";
            writeToLogFunction::write_to_log("Wrong protocoll version.", $_SERVER["SCRIPT_FILENAME"]);
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

function check_macAddresse($macAddress, $pdo2)
{
    $sql = "SELECT id FROM boardConfig WHERE macAddress = '" . $macAddress . "' LIMIT 1";
    try {
        $idmacaddress_temp = $pdo2->query($sql); //Invalid query
        $idmacaddress = $idmacaddress_temp->fetch();
    } catch (PDOException $ex) {
        echo "An Error has occurred while check macAddress";
        writeToLogFunction::write_to_log("An Error has occurred while check macAddress", $_SERVER["SCRIPT_FILENAME"]);
    }

    if ( (!isset($idmacaddress['id']) ) || ($idmacaddress['id'] == null) ) {
        $statement = $pdo2->prepare("INSERT INTO boardConfig (macAddress, ownerUserId, name) VALUES (?, ?, ?)");
        $statement->execute(array($macAddress, 1, "- new imported -"));     // Default Owner User
        $neue_id = $pdo2->lastInsertId();
        writeToLogFunction::write_to_log("New Board with id $neue_id created", $_SERVER["SCRIPT_FILENAME"]);

        return $neue_id;
    } else {
        return $idmacaddress['id'];
    }
}

function check_owsensorAddress($sensorAddress, $macaddressid, $pdo2)
{
    $sql = "SELECT id FROM sensorconfig WHERE sensorAddress = '" . $sensorAddress . "' LIMIT 1";
    try {
        $idsensoraddress_temp = $pdo2->query($sql); //Invalid query
        $sensorAddressId = $idsensoraddress_temp->fetch();
        if ($sensorAddress != "00000000") {
            if (!$sensorAddressId) { // if no sensor found in DB, it should be created.
                $sensorAddressFamilyCode = substr($sensorAddress, 0, 2);
                $sql2 = "SELECT id FROM sensortypes WHERE oneWireFamilyCode = '" . $sensorAddressFamilyCode . "' LIMIT 1";
                $idsensortypes_temp = $pdo2->query($sql2); //Invalid query
                $idsensortypes = $idsensortypes_temp->fetch();
                writeToLogFunction::write_to_log("sensor: " . $idsensortypes, $_SERVER["SCRIPT_FILENAME"]);
                $statement2 = "INSERT INTO sensorconfig (boardid, sensorAddress, typid) VALUES ('$macaddressid', '$sensorAddress', '" . $idsensortypes['id'] . "')";
                $insertsuccess = $pdo2->exec($statement2);
                writeToLogFunction::write_to_log("Insert sensorconfig " . $statement2 . ", " . $insertsuccess, $_SERVER["SCRIPT_FILENAME"]);
                if ($insertsuccess) {
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
?>