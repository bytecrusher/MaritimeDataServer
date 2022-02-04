<?php 
/**
 * receives data from Collectors (MDCs) in JSON format.
 * 
 * @author: Guntmar Hoeche
 * @license: TBD
 */

require_once("./../frontend/func/dbConfig.func.php");
//require_once(dirname(__DIR__).'/../configuration.php');
require_once('./../configuration.php');
$config  = new configuration();

$api_key_value = $config::$api_key;
$api_key = $protocollversion = $macaddress = $sensor = $sensorid = $location = $value1 = $value2 = $value3 = $date = $time = "";

$pdo2 = dbConfig::getInstance();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents('php://input'), true);
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
                var_dump($macaddress);
                $macaddressid = check_macadresse($macaddress, $pdo2);
                foreach ($sensors as $key => &$sensor) {
                    $owsensorid = test_input($sensors[$key]["sensorAddress"]);
                    $sensorid = check_sensorid($owsensorid, $macaddressid, $pdo2);
                    $date = test_input($sensors[$key]["date"]);
                    $time = test_input($sensors[$key]["time"]);
                    if (substr($owsensorid, 0, 2) === "28") {
                        $value1 = test_input($sensors[$key]["value1"]);
                        $value2 = null;
                        $value3 = null;
                        $sensor = "DS18b20 new";
                    } elseif (substr($owsensorid, 0, 2) === "26") {
                        $value1 = test_input($sensors[$key]["value1"]);
                        $value2 = test_input($sensors[$key]["value2"]);
                        $sensor = "DS2438";
                    }

                    // ToDo Change to pdo
                    $sql = "INSERT INTO sensordata (sensorid, value1, value2, value3, val_date, val_time)
                    VALUES ('" . $sensorid . "', '" . $value1 . "', '" . $value2 . "', '" . $value3 . "', '" . $date . "', '" . $time . "')";
                    try {
                        $pdo2->query($sql); //Invalid query
                    } catch (PDOException $ex) {
                        //$ex->getMessage();
                        echo "An Error has occurred while run query.";
                        write_to_log("An Error has occurred while run query.");
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
        write_to_log("macadress $macaddress ");
       // write_to_log("idmacaddress_temp $idmacaddress_temp ");
        write_to_log("idmacaddress $idmacaddress[0] ");
    } catch (PDOException $ex) {
        //$ex->getMessage();
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
        // Update Board is online !!
        // $statement3 = $pdo2->prepare("UPDATE boardconfig SET isOnline = ? WHERE macaddress = '" . $macaddress . "'");
        // $statement3->execute(array('1', 1));
        return $idmacaddress['id'];
    }
}

function check_sensorid($sensorAddress, $macaddressid, $pdo2)
{
    $sql = "SELECT id FROM sensorconfig WHERE sensorAddress = '" . $sensorAddress . "' LIMIT 1";
    try {
        $idsensoraddress_temp = $pdo2->query($sql); //Invalid query
        $sensorAddressId = $idsensoraddress_temp->fetch();
        //if ($sensorAddressId['id'] === null) {
        if ($sensorAddress != "00000000") {
            if (!$sensorAddressId) { // if no sensor found in DB, it should be created.
                $sensorAddressFamilyCode = substr($sensorAddress, 0, 2);
                $sql2 = "SELECT id FROM sensortypes WHERE oneWireFamilyCode = '" . $sensorAddressFamilyCode . "' LIMIT 1";
                $idsensortypes_temp = $pdo2->query($sql2); //Invalid query
                $idsensortypes = $idsensortypes_temp->fetch();
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
        //$ex->getMessage();
        write_to_log("An Error has occurred while add / check sensor. ");
    }
}

function write_to_log($text)
{
    $format = "csv"; // csv or txt
    $datum_zeit = date("d.m.Y H:i:s");
    $site = $_SERVER['REQUEST_URI'];
    //$dateiname = dirname(__DIR__)."logs/log.$format";
    $dateiname = "./logs/log.$format";
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