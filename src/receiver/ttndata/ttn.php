<?php
/*
 *  Version 1.1
 *  Created 2020-NOV-27
 *  Update 2021-OCT-11
 *  https://wwww.aeq-web.com
 * 
 * Mofified by Guntmar Hoeche
 */

/*
      "decoded_payload":{
            "alarm1":1,           // DIGITAL
            "altitude":1,         // GPS
            "counter":0,          // LORA
            "dewpoint":0,         // (BME) calculated in ESP
            "hdop":1.1,           // ??
            "humidity":0,         // BME
            "latitude":0,         // GPS
            "level1":0,           // ADC
            "level2":0,           // ADC
            "longitude":0,        // GPS
            "position":{ "context":{ "lat":0, "lng":0 }, "value":0 },
            "pressure":0,         // BME
            "relay":0,            // DIGITAL
            "tempbattery":0,      // Temp
            "temperature":-10.1,  // BME
            "voltage":5.37        // ADC
        },
  */

// load configuration data
require_once(dirname(__FILE__, 3) . '/configuration.php');
require_once(dirname(__FILE__, 3) . "/frontend/func/myFunctions.func.php");
require_once(dirname(__FILE__, 3) . "/frontend/func/dbConfig.func.php");
require_once(dirname(__FILE__, 3) . "/frontend/func/writeToLogFunction.func.php");

$pdo2 = dbConfig::getInstance();
$config = new configuration();

$ttn_post = file('php://input');
$data = null;
//writeToLogFunction::write_to_log($ttn_post, $_SERVER["SCRIPT_FILENAME"]);

if(sizeof($ttn_post) > 0) {
    $data = json_decode($ttn_post[0]);
    //$data = json_decode($ttn_post);
    //writeToLogFunction::write_to_log($data, $_SERVER["SCRIPT_FILENAME"]);

    $sensor_raw_payload = null;
    if(($data != null) && ($data->uplink_message->decoded_payload != null)) {
        //$payloadversion = $data->uplink_message->decoded_payload->payloadversion;
        $sensor_temperature = $sensor_humidity = $sensor_battery = 0;       // define Variables

        // Sensor Data
        $sensor_alarm1 = $data->uplink_message->decoded_payload->alarm1;
        $sensor_altitude = $data->uplink_message->decoded_payload->altitude;
        if (isset($data->uplink_message->decoded_payload->counter)) {
          $frame_counter = $data->uplink_message->decoded_payload->counter;
        } else {
          $frame_counter = 0;
        }
        
        $sensor_dewpoint = $data->uplink_message->decoded_payload->dewpoint;
        $sensor_humidity = $data->uplink_message->decoded_payload->humidity;
        if(isset($data->uplink_message->decoded_payload->Hum_SHT)) {
          $sensor_humidity = $data->uplink_message->decoded_payload->Hum_SHT;
        } else {
          //$sensor_humidity = 0;
        }

        $sensor_latitude = $data->uplink_message->decoded_payload->latitude;
        if(isset($data->uplink_message->decoded_payload->level1)) {
          $sensor_level1 = $data->uplink_message->decoded_payload->level1;
        } else {
          $sensor_level1 = 0;
        }

        if(isset($data->uplink_message->decoded_payload->level2)) {
          $sensor_level2 = $data->uplink_message->decoded_payload->level2;
        } else {
          $sensor_level2 = 0;
        }
        $sensor_longitude = $data->uplink_message->decoded_payload->longitude;
        $position_lat = $data->uplink_message->decoded_payload->position->context->lat;
        $position_lng = $data->uplink_message->decoded_payload->position->context->lng;
        $sensor_pressure = $data->uplink_message->decoded_payload->pressure;
        if(isset($data->uplink_message->decoded_payload->relay)) {
          $sensor_relay = $data->uplink_message->decoded_payload->relay;
        } else {
          $sensor_relay = 0;
        }

        if(isset($data->uplink_message->decoded_payload->tempbattery)) {
          $sensor_temperature_2 = $data->uplink_message->decoded_payload->tempbattery;
        } else {
          $sensor_temperature_2 = 0;
        }

        if(isset($data->uplink_message->decoded_payload->BatV)) {
            $sensor_battery = $data->uplink_message->decoded_payload->BatV;
        } else {
          $sensor_battery = 0;
        }

        if(isset($data->uplink_message->decoded_payload->temperature)) {
          $sensor_temperature = $data->uplink_message->decoded_payload->temperature;
        }
        if(isset($data->uplink_message->decoded_payload->TempC_SHT)) {
          $sensor_temperature = $data->uplink_message->decoded_payload->TempC_SHT;
        }
        if(isset($data->uplink_message->decoded_payload->voltage)) {
          $sensor_battery = $data->uplink_message->decoded_payload->voltage;
        } else {
          $sensor_battery = 0;
        }

        if(isset($data->uplink_message->decoded_payload->voltage2)) {
          $sensor_battery2 = $data->uplink_message->decoded_payload->voltage2;
        } else {
          $sensor_battery2 = 0;
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

    date_default_timezone_set('Europe/Berlin');
    $server_datetime = date("Y-m-d H:i:s");

    if ($sensor_raw_payload != null) {
      try {
        mysqli_query($db_connect, "INSERT INTO `ttnDataLoraBoatMonitor` (`id`, `datetime`, `app_id`, `dev_id`, `ttn_timestamp`, `gtw_id`, `gtw_rssi`,"
        . " `gtw_snr`, `dev_raw_payload`, `dev_value_1`, `dev_value_2`, `dev_value_3`, `dev_value_4`) "
        . "VALUES (NULL, '$server_datetime', '$ttn_app_id', '$ttn_dev_id', '$ttn_time', '$gtw_id', '$gtw_rssi', '$gtw_snr',"
        . " '$sensor_raw_payload', '$sensor_temperature', '$sensor_temperature_2', '$sensor_humidity', '$sensor_battery');
        ");
      } catch (PDOException $e) {
        writeToLogFunction::write_to_log("Error: ttnDataLoraBoatMonitor not saved.", $_SERVER["SCRIPT_FILENAME"]);
        writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
        throw new Exception('ttnDataLoraBoatMonitor not saved.');
      }
    }

    // TODO: insert data into 'sensordata' (first get Board-ID by TTN Appid and Devid)
    $singleRowBoardIdbyTTN = myFunctions::getBoardByTTN($ttn_app_id, $ttn_dev_id);
    $myFunctions = new myFunctions();
    
    // if board not exist, create it.
    if (!$singleRowBoardIdbyTTN) {
        $newId = myFunctions::addBoardByTTN($ttn_app_id, $ttn_dev_id);
        writeToLogFunction::write_to_log('new board created. BoardID: ' . $newId, $_SERVER["SCRIPT_FILENAME"]);
        $singleRowBoardIdbyTTN = myFunctions::getBoardByTTN($ttn_app_id, $ttn_dev_id);
    }
    
    $allSensorsOfBoard = myFunctions::getAllSensorsOfBoard($singleRowBoardIdbyTTN['id']);
    if(array_search('GPS', array_column($allSensorsOfBoard, 'boardid')) === false) {
      writeToLogFunction::write_to_log('Sensor GPS does not exist. Will now create for boardid: ' . $singleRowBoardIdbyTTN['id'], $_SERVER["SCRIPT_FILENAME"]);
      $myFunctions->addSensorConfig($singleRowBoardIdbyTTN['id'], "GPS", "GPS");
    }

    if(array_search('Lora', array_column($allSensorsOfBoard, 'boardid')) === false) {
      writeToLogFunction::write_to_log('Sensor Lora does not exist. Will now create for boardid: ' . $singleRowBoardIdbyTTN['id'], $_SERVER["SCRIPT_FILENAME"]);
      $myFunctions->addSensorConfig($singleRowBoardIdbyTTN['id'], "Lora", "Lora");
    }

    if(array_search('ADC', array_column($allSensorsOfBoard, 'boardid')) === false) {
      writeToLogFunction::write_to_log('Sensor ADC does not exist. Will now create for boardid: ' . $singleRowBoardIdbyTTN['id'], $_SERVER["SCRIPT_FILENAME"]);
      $myFunctions->addSensorConfig($singleRowBoardIdbyTTN['id'], "ADC", "ADC");
    }

    if(array_search('DS18B20', array_column($allSensorsOfBoard, 'boardid')) === false) {
      writeToLogFunction::write_to_log('Sensor DS18B20 does not exist. Will now create for boardid: ' . $singleRowBoardIdbyTTN['id'], $_SERVER["SCRIPT_FILENAME"]);
      $myFunctions->addSensorConfig($singleRowBoardIdbyTTN['id'], "DS18B20", "DS18B20");
    }

    if(array_search('BME280', array_column($allSensorsOfBoard, 'boardid')) === false) {
      writeToLogFunction::write_to_log('Sensor BME280 does not exist. Will now create for boardid: ' . $singleRowBoardIdbyTTN['id'], $_SERVER["SCRIPT_FILENAME"]);
      $myFunctions->addSensorConfig($singleRowBoardIdbyTTN['id'], "BME280", "BME280");
    }

    if(array_search('DS2438', array_column($allSensorsOfBoard, 'boardid')) === false) {
      writeToLogFunction::write_to_log('Sensor DS2438 does not exist. Will now create for boardid: ' . $singleRowBoardIdbyTTN['id'], $_SERVER["SCRIPT_FILENAME"]);
      $myFunctions->addSensorConfig($singleRowBoardIdbyTTN['id'], "DS2438", "DS2438");
    }

    if(array_search('Digital', array_column($allSensorsOfBoard, 'boardid')) === false) {
      writeToLogFunction::write_to_log('Sensor Digital does not exist. Will now create for boardid: ' . $singleRowBoardIdbyTTN['id'], $_SERVER["SCRIPT_FILENAME"]);
      $myFunctions->addSensorConfig($singleRowBoardIdbyTTN['id'], "Digital", "Digital");
    }

    $url = $config::$baseurl . '/receiver/receivejson.php';
    //$url = 'http://172.26.0.5/maritimedataserver/src/receiver/receivejson.php';

    // create a new cURL resource
    $ch = curl_init();

    // set URL and other appropriate options
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);

    //$ch = curl_init($url);
    writeToLogFunction::write_to_log("debug: " . $url, $_SERVER["SCRIPT_FILENAME"]);
    //writeToLogFunction::write_to_log("debug ch: ", $_SERVER["SCRIPT_FILENAME"]);
    //writeToLogFunction::write_to_log($ch, $_SERVER["SCRIPT_FILENAME"]);

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
      //writeToLogFunction::write_to_log("Sensor: " . $eachsensor['boardid'], $_SERVER["SCRIPT_FILENAME"]);
      //if ($eachsensor['ttn_payload_id'] != null) {
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
            "value2" => $sensor_battery2,
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
      //}   
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
    if(curl_exec($ch) === false)
    {
        writeToLogFunction::write_to_log('Curl error: ' . curl_error($ch), $_SERVER["SCRIPT_FILENAME"]);
    }
    else
    {
        writeToLogFunction::write_to_log('Operation completed without any errors', $_SERVER["SCRIPT_FILENAME"]);
    }

    // Close cURL resource
    curl_close($ch);
}
?>
