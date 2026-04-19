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
require_once(dirname(__DIR__, 3) . '/Infrastructure/Config/configuration.php');
require_once(dirname(__DIR__, 3) . "/Application/myFunctions.func.php");
require_once(dirname(__DIR__, 3) . "/Infrastructure/Database/dbConfig.func.php");
require_once(dirname(__DIR__, 3) . "/Infrastructure/Logging/writeToLogFunction.func.php");

//date_default_timezone_set('UTC');
date_default_timezone_set('Europe/Berlin');
//TODO: load timezone from settings.

$pdo2 = dbConfig::getInstance();
$config = new configuration();

$ttn_post = file_get_contents('php://input');
$data = null;
if(strlen($ttn_post) > 0) {
    $data = json_decode($ttn_post);
    $sensor_raw_payload = null;
    $ttn_device_id = null;
    $ttn_dev_eui = null;
    $ttn_board_identifier = null;

    if(($data != null) && isset($data->uplink_message) && isset($data->uplink_message->decoded_payload) && ($data->uplink_message->decoded_payload != null)) {
        $sensor_temperature = $sensor_humidity = $sensor_battery = 0;       // define Variables

        // Sensor Data
        $decodedPayload = $data->uplink_message->decoded_payload;
        $sensor_alarm1 = $decodedPayload->alarm1 ?? 0;
        $sensor_altitude = $decodedPayload->altitude ?? 0;
        if (isset($data->uplink_message->decoded_payload->counter)) {
          $frame_counter = $data->uplink_message->decoded_payload->counter;
        } else {
          $frame_counter = 0;
        }
        
        $sensor_dewpoint = $decodedPayload->dewpoint ?? 0;
        $sensor_humidity = $decodedPayload->humidity ?? 0;
        if(isset($data->uplink_message->decoded_payload->Hum_SHT)) {
          $sensor_humidity = $data->uplink_message->decoded_payload->Hum_SHT;
        }

        $sensor_latitude = $decodedPayload->latitude ?? 0;
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
        $sensor_longitude = $decodedPayload->longitude ?? 0;
        $position_lat = $decodedPayload->position->context->lat ?? 0;
        $position_lng = $decodedPayload->position->context->lng ?? 0;
        $sensor_pressure = $decodedPayload->pressure ?? 0;
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
        $gtw_id = $data->uplink_message->rx_metadata[0]->gateway_ids->gateway_id ?? '';
        $gtw_rssi = $data->uplink_message->rx_metadata[0]->rssi ?? 0;
        $gtw_snr = $data->uplink_message->rx_metadata[0]->snr ?? 0;

        $ttn_app_id = $data->end_device_ids->application_ids->application_id;
        $ttn_device_id = $data->end_device_ids->device_id ?? null;
        $ttn_dev_eui = $data->end_device_ids->dev_eui ?? null;
        $ttn_dev_id = $ttn_dev_eui;
        $ttn_board_identifier = $ttn_device_id ?: $ttn_dev_eui;
        $ttn_time = $data->received_at;
        writeToLogFunction::info(
            'TTN uplink received.',
            $_SERVER["SCRIPT_FILENAME"],
            array(
                'ttnAppId' => $ttn_app_id,
                'ttnDeviceId' => $ttn_device_id,
                'ttnDevEui' => $ttn_dev_eui,
                'frameCounter' => $frame_counter
            )
        );
    } else {
        writeToLogFunction::warning(
            'TTN payload missing decoded_payload.',
            $_SERVER["SCRIPT_FILENAME"],
            array('rawPayload' => $ttn_post)
        );
    }

    $DATABASE_HOST = $config::$dbHost;
    $DATABASE_USERNAME = $config::$dbUser;
    $DATABASE_PASSWORD = $config::$dbPassword;
    $DATABASE_NAME = $config::$dbName;

    $db_connect = mysqli_connect($DATABASE_HOST, $DATABASE_USERNAME, $DATABASE_PASSWORD, $DATABASE_NAME);

    $server_datetime = date("Y-m-d H:i:s", time());

    if ($sensor_raw_payload != null) {
    mysqli_query($db_connect, "INSERT INTO `ttnDataLoraBoatMonitor` (`id`, `datetime`, `app_id`, `dev_id`, `ttn_timestamp`, `gtw_id`, `gtw_rssi`,"
            . " `gtw_snr`, `gtw_channel_index`, `gtw_bandwidth`, `gtw_sf`, `dev_counter`, `dev_raw_payload`, `dev_value_1`, `dev_value_2`, `dev_value_3`, `dev_value_4`) "
            . "VALUES (NULL, '$server_datetime', '$ttn_app_id', '$ttn_dev_id', '$ttn_time', '$gtw_id', '$gtw_rssi', '$gtw_snr', '2', '55', '4', $frame_counter, "
            . " '$sensor_raw_payload', '$sensor_temperature', '$sensor_temperature_2', '$sensor_humidity', '$sensor_battery');
    ");
    }

    // TODO: insert data into 'sensordata' (first get Board-ID by TTN Appid and Devid)
    $singleRowBoardIdbyTTN = myFunctions::getBoardByTTN($ttn_app_id, $ttn_dev_eui, $ttn_device_id);
    $myFunctions = new myFunctions();
    
    // if board not exist, create it.
    if (!$singleRowBoardIdbyTTN) {
        $newId = myFunctions::addBoardByTTN($ttn_app_id, $ttn_board_identifier);
        writeToLogFunction::info(
            'New board created from TTN uplink.',
            $_SERVER["SCRIPT_FILENAME"],
            array(
                'boardId' => $newId,
                'ttnAppId' => $ttn_app_id,
                'ttnDeviceIdentifier' => $ttn_board_identifier
            )
        );
        $singleRowBoardIdbyTTN = myFunctions::getBoardByTTN($ttn_app_id, $ttn_dev_eui, $ttn_device_id);
    }
    
    $allSensorsOfBoard = myFunctions::getAllSensorsOfBoard($singleRowBoardIdbyTTN['id']);
    $createdSensors = false;
    if(array_search('GPS', array_column($allSensorsOfBoard, 'sensorTypesName')) === false) {
      writeToLogFunction::info('Sensor GPS does not exist. Will now create it.', $_SERVER["SCRIPT_FILENAME"], array('boardId' => $singleRowBoardIdbyTTN['id']));
      $myFunctions->addSensorConfig($singleRowBoardIdbyTTN['id'], "GPS", "GPS");
      $createdSensors = true;
    }

    if(array_search('Lora', array_column($allSensorsOfBoard, 'sensorTypesName')) === false) {
      writeToLogFunction::info('Sensor Lora does not exist. Will now create it.', $_SERVER["SCRIPT_FILENAME"], array('boardId' => $singleRowBoardIdbyTTN['id']));
      $myFunctions->addSensorConfig($singleRowBoardIdbyTTN['id'], "Lora", "Lora");
      $createdSensors = true;
    }

    if(array_search('ADC', array_column($allSensorsOfBoard, 'sensorTypesName')) === false) {
      writeToLogFunction::info('Sensor ADC does not exist. Will now create it.', $_SERVER["SCRIPT_FILENAME"], array('boardId' => $singleRowBoardIdbyTTN['id']));
      $myFunctions->addSensorConfig($singleRowBoardIdbyTTN['id'], "ADC", "ADC");
      $createdSensors = true;
    }

    if(array_search('DS18B20', array_column($allSensorsOfBoard, 'sensorTypesName')) === false) {
      writeToLogFunction::info('Sensor DS18B20 does not exist. Will now create it.', $_SERVER["SCRIPT_FILENAME"], array('boardId' => $singleRowBoardIdbyTTN['id']));
      $myFunctions->addSensorConfig($singleRowBoardIdbyTTN['id'], "DS18B20", "DS18B20");
      $createdSensors = true;
    }

    if(array_search('BME280', array_column($allSensorsOfBoard, 'sensorTypesName')) === false) {
      writeToLogFunction::info('Sensor BME280 does not exist. Will now create it.', $_SERVER["SCRIPT_FILENAME"], array('boardId' => $singleRowBoardIdbyTTN['id']));
      $myFunctions->addSensorConfig($singleRowBoardIdbyTTN['id'], "BME280", "BME280");
      $createdSensors = true;
    }

    if(array_search('DS2438', array_column($allSensorsOfBoard, 'sensorTypesName')) === false) {
      writeToLogFunction::info('Sensor DS2438 does not exist. Will now create it.', $_SERVER["SCRIPT_FILENAME"], array('boardId' => $singleRowBoardIdbyTTN['id']));
      $myFunctions->addSensorConfig($singleRowBoardIdbyTTN['id'], "DS2438", "DS2438");
      $createdSensors = true;
    }

    if(array_search('Digital', array_column($allSensorsOfBoard, 'sensorTypesName')) === false) {
      writeToLogFunction::info('Sensor Digital does not exist. Will now create it.', $_SERVER["SCRIPT_FILENAME"], array('boardId' => $singleRowBoardIdbyTTN['id']));
      $myFunctions->addSensorConfig($singleRowBoardIdbyTTN['id'], "Digital", "Digital");
      $createdSensors = true;
    }

    if ($createdSensors) {
      $allSensorsOfBoard = myFunctions::getAllSensorsOfBoard($singleRowBoardIdbyTTN['id']);
    }

    $url = $config::$baseurl . '/receiver/receivejson.php';
    $ch = curl_init($url);

    writeToLogFunction::info(
      'Forwarding TTN payload to receivejson.php.',
      $_SERVER["SCRIPT_FILENAME"],
      array('targetUrl' => $url, 'boardId' => $singleRowBoardIdbyTTN['id'])
    );

    $boardInfos = array(
        "apiKey" => $config::$apiKey,
        // TODO: Anhand der Dev_IDE die Mac ermitteln
        "macAddress" => $singleRowBoardIdbyTTN['macAddress'],   // fake mac address for debug.
        "protocolVersion" => "1"   // Version of the used protocoll.
    );

    $dateNow = date("d.m.Y");
    $timeNow = date("H:i:s");   

    $sensor1 = $sensor2 = $sensor3 = null;
    $sensors = array();

    foreach($allSensorsOfBoard AS $eachsensor) {
      $sensor1 = null;
      //writeToLogFunction::write_to_log($eachsensor['boardid'], $_SERVER["SCRIPT_FILENAME"]);
      //if ($eachsensor['ttn_payload_id'] != null) {
        // TODO check, if boardid is the right var. I think it should be typid.
        if ($eachsensor['sensorTypesName'] == "DS18B20") {
          $sensor1 = array(
            "id" => $eachsensor['id'],
            "sensorId" => $eachsensor['id'],
            "value1" => $sensor_temperature_2,
            "date" => $dateNow,
            "time" => $timeNow,
            "transmissionPath" => "2"
          );
        } elseif ($eachsensor['sensorTypesName'] == "ADC") {
          $sensor1 = array(
            "id" => $eachsensor['id'],
            "sensorId" => $eachsensor['id'],
            "value1" => $sensor_battery,
            "value2" => $sensor_battery2,
            "value3" => $sensor_level1,
            "value4" => $sensor_level2,
            "date" => $dateNow,
            "time" => $timeNow,
            "transmissionPath" => "2"
          );
        } elseif ($eachsensor['sensorTypesName'] == "BME280") {
          $sensor1 = array(
            "id" => $eachsensor['id'],
            "sensorId" => $eachsensor['id'],
            "value1" => $sensor_temperature,
            "value2" => $sensor_humidity,
            "value3" => $sensor_pressure,
            "value4" => $sensor_dewpoint,
            "date" => $dateNow,
            "time" => $timeNow,
            "transmissionPath" => "2"
          );
        } elseif ($eachsensor['sensorTypesName'] == "GPS") {
          $sensor1 = array(
            "id" => $eachsensor['id'],
            "sensorId" => $eachsensor['id'],
            "value1" => $sensor_latitude,
            "value2" => $sensor_longitude,
            "value3" => $position_lat,
            "value4" => $position_lng,
            "date" => $dateNow,
            "time" => $timeNow,
            "transmissionPath" => "2"
          );
        } elseif ($eachsensor['sensorTypesName'] == "Lora") {
          $sensor1 = array(
            "id" => $eachsensor['id'],
            "sensorId" => $eachsensor['id'],
            "value1" => $gtw_id,
            "value2" => $gtw_rssi,
            "value3" => $gtw_snr,
            "date" => $dateNow,
            "time" => $timeNow,
            "transmissionPath" => "2"
          );
        }
        if ($sensor1 !== null) {
          array_push($sensors, $sensor1);
        }
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

    #writeToLogFunction::write_to_log($ch, $_SERVER["SCRIPT_FILENAME"]);

    // Execute the POST request
    $result = curl_exec($ch);
    if ($result === false) {
      writeToLogFunction::error(
        'cURL forwarding to receivejson failed.',
        $_SERVER["SCRIPT_FILENAME"],
        array(
          'targetUrl' => $url,
          'curlError' => curl_error($ch),
          'curlErrno' => curl_errno($ch)
        )
      );
    } else {
      writeToLogFunction::info(
        'cURL forwarding to receivejson succeeded.',
        $_SERVER["SCRIPT_FILENAME"],
        array(
          'targetUrl' => $url,
          'response' => $result,
          'sensorCount' => count($sensors)
        )
      );
    }

    // Close cURL resource
    curl_close($ch);
} else {
    writeToLogFunction::warning('TTN endpoint called without body.', $_SERVER["SCRIPT_FILENAME"]);
}
?>
