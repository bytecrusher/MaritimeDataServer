<?php
/*
 *  Version 1.1
 *  Created 2020-NOV-27
 *  Update 2021-OCT-11
 *  https://wwww.aeq-web.com
 */

/*
      "decoded_payload":{
            "alarm1":1,
            "altitude":1,
            "counter":0,
            "dewpoint":0,
            "hdop":1.1,
            "humidity":0,
            "latitude":0,
            "level1":0,
            "level2":0,
            "longitude":0,
            "position":{ "context":{ "lat":0, "lng":0 }, "value":0 },
            "pressure":0,
            "relay":0,
            "tempbattery":0,
            "temperature":-10.1,
            "voltage":5.37
        },
  */

// load configuration data
require_once(dirname(__FILE__, 3) . '/configuration.php');
require_once(dirname(__FILE__, 3) . "/frontend/func/myFunctions.func.php");
require_once(dirname(__FILE__, 3) . "/frontend/func/dbConfig.func.php");

$pdo2 = dbConfig::getInstance();
$config = new configuration();

$ttn_post = file('php://input');
$data = null;
if(sizeof($ttn_post) > 0) {
    $data = json_decode($ttn_post[0]);
    $sensor_raw_payload = null;
    if(($data != null) && ($data->uplink_message->decoded_payload != null)) {
        //$payloadversion = $data->uplink_message->decoded_payload->payloadversion;
        $sensor_temperature = $sensor_humidity = $sensor_battery = 0;       // define Variables

        // Sensor Data
        $sensor_alarm1 = $data->uplink_message->decoded_payload->alarm1;
        $sensor_altitude = $data->uplink_message->decoded_payload->altitude;
        $frame_counter = $data->uplink_message->decoded_payload->counter;
        $sensor_dewpoint = $data->uplink_message->decoded_payload->dewpoint;
        $sensor_humidity = $data->uplink_message->decoded_payload->humidity;
        if(isset($data->uplink_message->decoded_payload->Hum_SHT)) {
          $sensor_humidity = $data->uplink_message->decoded_payload->Hum_SHT;
        }

        $sensor_latitude = $data->uplink_message->decoded_payload->latitude;
        $sensor_level1 = $data->uplink_message->decoded_payload->level1;
        $sensor_level2 = $data->uplink_message->decoded_payload->level2;
        $sensor_longitude = $data->uplink_message->decoded_payload->longitude;
        $position_lat = $data->uplink_message->decoded_payload->position->context->lat;
        $position_lng = $data->uplink_message->decoded_payload->position->context->lng;
        $sensor_pressure = $data->uplink_message->decoded_payload->pressure;
        $sensor_relay = $data->uplink_message->decoded_payload->relay;

        if(isset($data->uplink_message->decoded_payload->tempbattery)) {
          $sensor_temperature_2 = $data->uplink_message->decoded_payload->tempbattery;
        }
        if(isset($data->uplink_message->decoded_payload->BatV)) {
            $sensor_battery = $data->uplink_message->decoded_payload->BatV;
        }
        $sensor_temperature = $data->uplink_message->decoded_payload->temperature;
        if(isset($data->uplink_message->decoded_payload->TempC_SHT)) {
          $sensor_temperature = $data->uplink_message->decoded_payload->TempC_SHT;
        }
        if(isset($data->uplink_message->decoded_payload->voltage)) {
          $sensor_battery = $data->uplink_message->decoded_payload->voltage;
        }

        $sensor_raw_payload = $data->uplink_message->frm_payload;

        // TTN Data
        $gtw_id = $data->uplink_message->rx_metadata[0]->gateway_ids->gateway_id;
        $gtw_rssi = $data->uplink_message->rx_metadata[0]->rssi;
        $gtw_snr = $data->uplink_message->rx_metadata[0]->snr;

        $ttn_app_id = $data->end_device_ids->application_ids->application_id;
        $ttn_dev_id = $data->end_device_ids->dev_eui;
        $ttn_time = $data->received_at;
    }

    $DATABASE_HOST = $config::$db_host;
    $DATABASE_USERNAME = $config::$db_user;
    $DATABASE_PASSWORD = $config::$db_password;
    $DATABASE_NAME = $config::$db_name;

    $db_connect = mysqli_connect($DATABASE_HOST, $DATABASE_USERNAME, $DATABASE_PASSWORD, $DATABASE_NAME);

    $server_datetime = date("Y-m-d H:i:s");

    if ($sensor_raw_payload != null) {
    mysqli_query($db_connect, "INSERT INTO `ttnDataLoraBoatMonitor` (`id`, `datetime`, `app_id`, `dev_id`, `ttn_timestamp`, `gtw_id`, `gtw_rssi`,"
            . " `gtw_snr`, `dev_raw_payload`, `dev_value_1`, `dev_value_2`, `dev_value_3`, `dev_value_4`) "
            . "VALUES (NULL, '$server_datetime', '$ttn_app_id', '$ttn_dev_id', '$ttn_time', '$gtw_id', '$gtw_rssi', '$gtw_snr',"
            . " '$sensor_raw_payload', '$sensor_temperature', '$sensor_temperature_2', '$sensor_humidity', '$sensor_battery');
    ");
    }

    // TODO: insert data into 'sensordata' (first get Board-ID by TTN Appid and Devid)
    $singleRowBoardIdbyTTN = myFunctions::getBoardByTTN($ttn_app_id, $ttn_dev_id);
    
    // if board not exist, create it.
    if (!$singleRowBoardIdbyTTN) {
        myFunctions::addBoardByTTN($ttn_app_id, $ttn_dev_id);
        echo("new board created.");
        $singleRowBoardIdbyTTN = myFunctions::getBoardByTTN($ttn_app_id, $ttn_dev_id);
    }
    
    $allSensorsOfBoard = myFunctions::getAllSensorsOfBoard($singleRowBoardIdbyTTN['id']);
    if (!$allSensorsOfBoard) {
      // TODO create sensors
    }

    $url = $config::$baseurl . '/receiver/receivejson.php';
    $ch = curl_init($url);

    $boardInfos = array(
        "api_key" => $config::$api_key,
        // TODO: Anhand der Dev_IDE die Mac ermitteln
        "macaddress" => $singleRowBoardIdbyTTN['macaddress'],   // fake mac address for debug.
        "protocollversion" => "1"   // Version of the used protocoll.
    );

    $dateNow = date("d.m.Y");
    $timeNow = date("H:i:s");   

    $sensor1 = $sensor2 = $sensor3 = null;
    $sensors = array();

    foreach($allSensorsOfBoard AS $eachsensor) {
      $sensor1 = null;
      if ($eachsensor['ttn_payload_id'] != null) {
        // TODO check, if boardid is the right var. I think it should be typid.
        if ($eachsensor['boardid'] == "DS18B20") {
          $sensor1 = array(
            "typid" => $eachsensor['typid'],
            "sensorId" => $eachsensor['id'],
            "value1" => $sensor_temperature_2,
            "date" => $dateNow,
            "time" => $timeNow,
            "transmissionpath" => "2"
          );
        } elseif ($eachsensor['boardid'] == "ADC") {
          $sensor1 = array(
            "typid" => $eachsensor['typid'],
            "sensorId" => $eachsensor['id'],
            "value1" => $sensor_battery,
            "value2" => 0,
            "value3" => $sensor_level1,
            "value4" => $sensor_level2,
            "date" => $dateNow,
            "time" => $timeNow,
            "transmissionpath" => "2"
          );
        } elseif ($eachsensor['boardid'] == "BME280") {
          $sensor1 = array(
            "typid" => $eachsensor['typid'],
            "sensorId" => $eachsensor['id'],
            "value1" => $sensor_temperature,
            "value2" => $sensor_humidity,
            "value3" => $sensor_pressure,
            "value4" => $sensor_dewpoint,
            "date" => $dateNow,
            "time" => $timeNow,
            "transmissionpath" => "2"
          );
        } elseif ($eachsensor['boardid'] == "GPS") {
          $sensor1 = array(
            "typid" => $eachsensor['typid'],
            "sensorId" => $eachsensor['id'],
            "value1" => $sensor_latitude,
            "value2" => $sensor_longitude,
            "value3" => $position_lat,
            "value4" => $position_lng,
            "date" => $dateNow,
            "time" => $timeNow,
            "transmissionpath" => "2"
          );
        } elseif ($eachsensor['boardid'] == "Lora") {
          $sensor1 = array(
            "typid" => $eachsensor['typid'],
            "sensorId" => $eachsensor['id'],
            "value1" => $gtw_id,
            "value2" => $gtw_rssi,
            "value3" => $gtw_snr,
            "date" => $dateNow,
            "time" => $timeNow,
            "transmissionpath" => "2"
          );
        }
        array_push($sensors, $sensor1);
      }   
    }
    $payload = json_encode(array(
      "board" => $boardInfos,
      "sensors" => $sensors
    ));

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  2);

    // Attach encoded JSON string to the POST fields
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    // Set the content type to application/json
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

    // Return response instead of outputting
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute the POST request
    $result = curl_exec($ch);

    // Close cURL resource
    curl_close($ch);
}


function write_to_log($text)
  {
    $format = "csv"; // Possibilities: csv and txt
    $monate = array(1 => "Januar", 2 => "Februar", 3 => "Maerz", 4 => "April", 5 => "Mai", 6 => "Juni", 7 => "Juli", 8 => "August", 9 => "September", 10 => "Oktober", 11 => "November", 12 => "Dezember");
    $monat = date("n");
    $jahr = date("y");
    $dateiname = "./logs/log_" . $monate[$monat] . "_$jahr.$format";
    $header = array("Datum", "IP", "Seite", "Browser");
    $infos = array($text);
    if ($format == "csv") {
      $eintrag2 = '"' . implode('", "' , $infos) . '"';
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
