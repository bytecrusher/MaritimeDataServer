<?php
/**
 * @author      Guntmar Höche
 * @license     TBD
 * @datetime    13 Februar 2022
 * @perpose     Get the last x (maxValues) values of a given Sensor.
 * @input       "identivier" and "securitytoken" for user validation, "maxValues" and "sensorId" for getting the data.
 * @output      Return data as JSON format.
 */

session_start();
require_once("../func/dbConfig.func.php");
require_once("../func/myFunctions.func.php");
require_once("../func/user.class.php");

$userobj = unserialize($_SESSION['userobj']);

$pdo = dbConfig::getInstance();
if ( count($_GET) == 0 ) {
  die("Parameter error.");
}
header('Content-Type: application/json');

$varIdent = $_GET['identifier'];
$varToken = $_GET['securitytoken'];
$maxValues = $_GET['maxValues'];
$sensorId = $_GET['sensorId'];

//$query = sprintf("SELECT id, sensorid, value1, value2, val_date, val_time, reading_time FROM sensordata WHERE sensorid = " . $sensorId . " ORDER BY id ASC LIMIT " . $maxValues);
//$query = sprintf("SELECT id, sensorid, value1, value2, sensor_timestamp, record_timestamp FROM sensordata WHERE sensorid = " . $sensorId . " ORDER BY id ASC LIMIT " . $maxValues);
$query = sprintf("SELECT id, sensorid, value1, value2, sensor_timestamp, record_timestamp FROM sensordata WHERE sensorid = " . $sensorId . " ORDER BY sensor_timestamp ASC LIMIT " . $maxValues);
$result = $pdo->query($query);

$data = array();
foreach ($result as $row) {
  $data[] = $row;
}

//write_to_log($data[0]);

echo json_encode($data);

function write_to_log($text)
{
    $format = "csv"; // csv or txt
    $datum_zeit = date("d.m.Y H:i:s");
    $site = $_SERVER['REQUEST_URI'];
    //$dateiname = dirname(__DIR__)."logs/log.$format";
    $dateiname = "./logs/log.$format";
    $header = array("Date", "Site", "Log");

    $newvalue = "";
    if (is_array($text)) {
        foreach ($text as $value => $v) {
            $newvalue = $newvalue . $value . ": " . $v . ", ";
        }
    } else {
        $newvalue = $text;
    }
    
    $infos = array($datum_zeit, $site, $newvalue);
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
