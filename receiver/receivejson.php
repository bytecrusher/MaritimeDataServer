<?php 
/**
 * receives data from Collectors (MDCs) in JSON format.
 * 
 * @author: Guntmar Hoeche
 * @license: TBD
 */

require_once(dirname(__FILE__, 2) . "/frontend/func/dbConfig.func.php");
require_once(dirname(__FILE__, 2) . '/configuration.php');
$config  = new configuration();

$api_key_value = $config::$api_key;
$api_key = $protocollversion = $macaddress = $sensor = $sensorid = $location = $value1 = $value2 = $value3 = $value4 = $date = $time = $transmissionpath = "";

$pdo2 = dbConfig::getInstance();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ttn_post = file_get_contents('php://input');
    $data = json_decode($ttn_post, true);

    $boardData = $data['board'];    // Array of Board informations from "POST"
    $sensors = $data['sensors'];    // Array of Sensors from "POST"
    if (isset($boardData['api_key'])) {
        $api_key = ($boardData['api_key']);
    } else {
        write_to_log("Wrong Api key!!");
    }

    $sql = null;
    if ($api_key == $api_key_value) {
        if ((isset($boardData['protocollversion'])) && ($boardData['protocollversion'] != null)) {
            if ($boardData['protocollversion'] == "1") {
                $macaddress = test_input($boardData['macaddress']);
                $macaddressid = check_macadresse($macaddress, $pdo2);
                foreach ($sensors as $key => &$sensor) {
                    //write_to_log($sensor['sensorId']);
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
                                write_to_log("owsensorAddress: " . $owsensorAddress);
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
                            
                            write_to_log("sensorid: " . $sensorid);
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

                        // TODO Change to pdo
                        $sql = "INSERT INTO sensordata (sensorid, value1, value2, value3, value4, val_date, val_time, transmissionpath)
                        VALUES ('" . $sensorid . "', '" . $value1 . "', '" . $value2 . "', '" . $value3 . "', '"  . $value4 . "', '" . $date . "', '" . $time . "', '" . $transmissionpath . "')";
                        try {
                            $pdo2->query($sql); //Invalid query
                        } catch (PDOException $ex) {
                            echo "An Error has occurred while run query.";
                            write_to_log("An Error has occurred while run query.");
                        }
                    }
                }
            }
        } else {
            echo "Wrong protocoll version.";
            write_to_log("Wrong protocoll version.");
            die();
        }
    } else {
        echo "Wrong API Key provided.";
        write_to_log("Wrong API Key provided.");
    }
} else {
    echo "No data posted with HTTP POST.";
    write_to_log("No data posted with HTTP POST.");
}

function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function check_macadresse($macaddress, $pdo2)
{
    $sql = "SELECT id FROM boardconfig WHERE macaddress = '" . $macaddress . "' LIMIT 1";
    try {
        $idmacaddress_temp = $pdo2->query($sql); //Invalid query
        $idmacaddress = $idmacaddress_temp->fetch();
    } catch (PDOException $ex) {
        echo "An Error has occurred while check macadress";
        write_to_log("An Error has occurred while check macadress");
    }

    if ( (!isset($idmacaddress['id']) ) || ($idmacaddress['id'] == null) ) {
        $statement = $pdo2->prepare("INSERT INTO boardconfig (macaddress, owner_userid, name) VALUES (?, ?, ?)");
        $statement->execute(array($macaddress, 1, "- new imported -"));     // Default Owner User
        $neue_id = $pdo2->lastInsertId();
        write_to_log("New Board with id $neue_id created");
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
                write_to_log("sensor: " . $idsensortypes);
                $statement2 = "INSERT INTO sensorconfig (boardid, sensorAddress, typid) VALUES ('$macaddressid', '$sensorAddress', '" . $idsensortypes['id'] . "')";
                $insertsuccess = $pdo2->exec($statement2);
                write_to_log("Insert sensorconfig " . $statement2) . ", " . $insertsuccess;
                if ($insertsuccess) {
                    $neue_id = $pdo2->lastInsertId();
                    write_to_log("New Sensor with id $neue_id created");
                    return $neue_id;
                } else {
                    return false;
                }
            } else {
                return $sensorAddressId['id'];
            }
        }
    } catch (PDOException $ex) {
        write_to_log("An Error has occurred while add / check sensor. ");
    }
}

function write_to_log($text)
{
    $format = "csv"; // csv or txt
    $datum_zeit = date("d.m.Y H:i:s");
    $site = $_SERVER['REQUEST_URI'];
    $dateiname = dirname(__FILE__) . "/logs/log.$format";
    $header = array("Date", "Site", "Log");

    $infos = array($datum_zeit, $site, $text);
    if ($format == "csv") {
        $eintrag2 = '"' . implode('", "', $infos) . '"';
    } else {
        $eintrag2 = implode("\t", $infos);
    }
    $write_header = !file_exists($dateiname);
    $datei = fopen($dateiname, "a");
    if ($write_header) {
        if ($format == "csv") {
            $header_line = '"' . implode('", "', $header) . '"';
        } else {
            $header_line = implode("\t", $header);
        }
        fputs($datei, $header_line . "\n");
    }
    fputs($datei, $eintrag2 . "\n");
    fclose($datei);
}
?>