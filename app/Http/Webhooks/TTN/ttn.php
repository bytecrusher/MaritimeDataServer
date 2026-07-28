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
require_once(dirname(__DIR__, 3) . "/Application/TtnPayloadDecoder.php");
require_once(dirname(__DIR__, 3) . "/Application/DevicePowerState.php");
require_once(dirname(__DIR__, 3) . "/Infrastructure/Database/dbConfig.func.php");
require_once(dirname(__DIR__, 3) . "/Infrastructure/Logging/writeToLogFunction.func.php");
header('Content-Type: application/json; charset=utf-8');

//date_default_timezone_set('UTC');
date_default_timezone_set('Europe/Berlin');
//TODO: load timezone from settings.

$pdo2 = dbConfig::getInstance();
$config = new configuration();
$requestHeaders = function_exists('getallheaders') ? getallheaders() : array();

$ttn_post = file_get_contents('php://input');
$data = null;
if(strlen($ttn_post) > 0) {
    $data = json_decode($ttn_post);
    if ($data === null || !isset($data->uplink_message) || !isset($data->end_device_ids) || !isset($data->end_device_ids->application_ids->application_id)) {
        http_response_code(400);
        writeToLogFunction::warning(
            'TTN payload missing required uplink fields.',
            $_SERVER["SCRIPT_FILENAME"],
            array('rawPayload' => $ttn_post)
        );
        ttnJsonResponse(400, array('error' => 'TTN payload missing required uplink fields.'));
    }

    if (!ttnWebhookSecretIsValid($requestHeaders, (string)$config::$ttnWebhookSecret)) {
        writeToLogFunction::warning(
            'TTN webhook secret validation failed.',
            $_SERVER["SCRIPT_FILENAME"],
            array('requestHeaders' => ttnFilterHeadersForLogging($requestHeaders))
        );
        ttnJsonResponse(403, array('error' => 'TTN webhook secret validation failed.'));
    }

    $sensor_temperature = 0;
    $sensor_temperature_2 = 0;
    $sensor_humidity = 0;
    $sensor_battery = 0;
    $sensor_battery2 = 0;
    $sensor_alarm1 = 0;
    $sensor_altitude = 0;
    $frame_counter = 0;
    $sensor_dewpoint = 0;
    $sensor_latitude = 0;
    $sensor_level1 = 0;
    $sensor_level2 = 0;
    $sensor_longitude = 0;
    $position_lat = 0;
    $position_lng = 0;
    $sensor_pressure = 0;
    $sensor_relay = 0;

    $uplinkMessage = $data->uplink_message;
    $sensor_raw_payload = $uplinkMessage->frm_payload ?? null;
    $hasDecodedPayload = isset($uplinkMessage->decoded_payload)
        && is_object($uplinkMessage->decoded_payload)
        && count(get_object_vars($uplinkMessage->decoded_payload)) > 0;
    $hasNormalizedPayload = ttnHasNormalizedPayload($uplinkMessage);
    $hasRawPayload = is_string($sensor_raw_payload) && trim($sensor_raw_payload) !== '';

    if (!$hasDecodedPayload && !$hasNormalizedPayload && !$hasRawPayload) {
        writeToLogFunction::info(
            'TTN uplink contained no application payload and was ignored.',
            $_SERVER["SCRIPT_FILENAME"],
            array(
                'fPort' => (int)($uplinkMessage->f_port ?? 0),
                'ttnDeviceId' => $data->end_device_ids->device_id ?? null,
                'ttnDevEui' => $data->end_device_ids->dev_eui ?? null
            )
        );
        ttnJsonResponse(202, array(
            'status' => 'ignored',
            'reason' => 'TTN uplink contained no application payload.'
        ));
    }

    $decodedPayload = ttnExtractMeasurementPayload($uplinkMessage);
    $ttnReportedPayloadType = (string)ttnPayloadValue($decodedPayload, array('payloadType'), 'measurements');
    $ttnReportedPayloadSchema = (int)ttnPayloadValue($decodedPayload, array('payloadSchema'), 1);
    $serverDecodedPayload = TtnPayloadDecoder::decodeKnownPayload(
        (int)($uplinkMessage->f_port ?? 0),
        $sensor_raw_payload
    );
    $payloadDecoderSource = 'ttn';
    if ($serverDecodedPayload !== null) {
        $serverPayloadType = (string)($serverDecodedPayload['payloadType'] ?? 'measurements');
        $serverPayloadSchema = (int)($serverDecodedPayload['payloadSchema'] ?? 1);
        if (($hasDecodedPayload || $hasNormalizedPayload)
            && ($ttnReportedPayloadType !== $serverPayloadType || $ttnReportedPayloadSchema !== $serverPayloadSchema)) {
            writeToLogFunction::warning(
                'TTN decoded payload metadata did not match the raw payload. Server-side decoder used.',
                $_SERVER["SCRIPT_FILENAME"],
                array(
                    'fPort' => (int)($uplinkMessage->f_port ?? 0),
                    'ttnDeviceId' => $data->end_device_ids->device_id ?? null,
                    'ttnDevEui' => $data->end_device_ids->dev_eui ?? null,
                    'ttnPayloadType' => $ttnReportedPayloadType,
                    'ttnPayloadSchema' => $ttnReportedPayloadSchema,
                    'rawPayloadType' => $serverPayloadType,
                    'rawPayloadSchema' => $serverPayloadSchema
                )
            );
        }
        $decodedPayload = json_decode(json_encode($serverDecodedPayload));
        $payloadDecoderSource = 'mdsRaw';
    } elseif (!$hasDecodedPayload && !$hasNormalizedPayload) {
        writeToLogFunction::warning(
            'TTN uplink payload could not be decoded.',
            $_SERVER["SCRIPT_FILENAME"],
            array(
                'fPort' => (int)($uplinkMessage->f_port ?? 0),
                'ttnDeviceId' => $data->end_device_ids->device_id ?? null,
                'ttnDevEui' => $data->end_device_ids->dev_eui ?? null,
                'rawPayloadBytes' => ttnEncodedPayloadByteLength($sensor_raw_payload)
            )
        );
        ttnJsonResponse(422, array(
            'error' => 'TTN uplink payload could not be decoded.',
            'fPort' => (int)($uplinkMessage->f_port ?? 0)
        ));
    }
    $payloadType = (string)ttnPayloadValue($decodedPayload, array('payloadType'), 'measurements');
    $payloadSchema = (int)ttnPayloadValue($decodedPayload, array('payloadSchema'), 1);
    $isDeviceConfigPayload = $payloadType === 'deviceConfig';
    $isNamedMeasurementPayload = $payloadType === 'measurements' && $payloadSchema >= 2;
    $bestRxMetadata = ttnSelectBestRxMetadata($uplinkMessage->rx_metadata ?? array());

    // Sensor Data
    $sensor_alarm1 = ttnPayloadValue($decodedPayload, array('alarm1'), 0);
    $sensor_altitude = ttnPayloadValue($decodedPayload, array('altitude'), 0);
    $frame_counter = ttnPayloadValue($decodedPayload, array('counter'), 0);
    $sensor_dewpoint = ttnPayloadValue($decodedPayload, array('dewpoint'), 0);
    $sensor_humidity = ttnPayloadValue($decodedPayload, array('humidity', 'Hum_SHT'), 0);
    $sensor_latitude = ttnPayloadValue($decodedPayload, array('latitude', 'position.latitude'), 0);
    $sensor_level1 = ttnPayloadValue($decodedPayload, array('level1'), 0);
    $sensor_level2 = ttnPayloadValue($decodedPayload, array('level2'), 0);
    $sensor_longitude = ttnPayloadValue($decodedPayload, array('longitude', 'position.longitude'), 0);
    $position_lat = ttnPayloadValue($decodedPayload, array('position.context.lat', 'position.latitude'), 0);
    $position_lng = ttnPayloadValue($decodedPayload, array('position.context.lng', 'position.longitude'), 0);
    $sensor_pressure = ttnPayloadValue($decodedPayload, array('pressure', 'air.pressure'), 0);
    $sensor_relay = ttnPayloadValue($decodedPayload, array('relay'), 0);
    $sensor_temperature_2 = ttnPayloadValue($decodedPayload, array('tempbattery'), 0);
    $sensor_battery = ttnPayloadValue($decodedPayload, array('BatV', 'voltage', 'battery'), 0);
    $sensor_temperature = ttnPayloadValue($decodedPayload, array('temperature', 'TempC_SHT', 'air.temperature'), 0);
    $sensor_battery2 = ttnPayloadValue($decodedPayload, array('voltage2'), 0);
    $sensor_battery_capacity = ttnPayloadValue($decodedPayload, array('batteryCapacity'), 0);
    $sensor_tank1_adc = ttnPayloadValue($decodedPayload, array('tank1Adc'), 0);
    $sensor_tank2_adc = ttnPayloadValue($decodedPayload, array('tank2Adc'), 0);
    $sensor_speed = ttnPayloadValue($decodedPayload, array('speed'), 0);
    $sensor_course = ttnPayloadValue($decodedPayload, array('course'), 0);
    $sensor_vedirect_voltage = ttnPayloadValue($decodedPayload, array('vedirectVoltage'), 0);
    $sensor_vedirect_current = ttnPayloadValue($decodedPayload, array('vedirectCurrent'), 0);
    $sensor_vedirect_temperature = ttnPayloadValue($decodedPayload, array('vedirectTemperature'), 0);
    $environment_present = ttnNormalizeBooleanValue(ttnPayloadValue($decodedPayload, array('environmentPresent'), false)) === true;
    $vedirect_present = ttnNormalizeBooleanValue(ttnPayloadValue($decodedPayload, array('vedirectPresent'), false)) === true;
    $wakeup_event_present = ttnNormalizeBooleanValue(ttnPayloadValue($decodedPayload, array('wakeupEventPresent'), false)) === true;
    $standby_event_epoch = (int)ttnPayloadValue($decodedPayload, array('standbyEpoch'), 0);
    $wakeup_event_epoch = (int)ttnPayloadValue($decodedPayload, array('wakeupEpoch'), 0);
    $standby_event_cause = trim((string)ttnPayloadValue($decodedPayload, array('standbyCause'), ''));
    $wakeup_event_cause = trim((string)ttnPayloadValue($decodedPayload, array('wakeupCause'), ''));
    $firmwareVersion = ttnFirmwareVersion($decodedPayload);
    $standbyState = ttnStandbyState($decodedPayload);
    $payloadMacAddress = ttnMacAddress($decodedPayload);

    // TTN Data
    $gtw_id = $bestRxMetadata->gateway_ids->gateway_id ?? '';
    $gtw_rssi = $bestRxMetadata->rssi ?? 0;
    $gtw_snr = $bestRxMetadata->snr ?? 0;

    $ttn_app_id = $data->end_device_ids->application_ids->application_id;
    $ttn_device_id = $data->end_device_ids->device_id ?? null;
    $ttn_dev_eui = $data->end_device_ids->dev_eui ?? null;
    $ttn_dev_id = $ttn_dev_eui;
    $ttn_board_identifier = $ttn_device_id ?: $ttn_dev_eui;
    $ttn_time = $data->received_at ?? ($uplinkMessage->received_at ?? null);
    writeToLogFunction::info(
        'TTN uplink received.',
        $_SERVER["SCRIPT_FILENAME"],
        array(
            'ttnAppId' => $ttn_app_id,
            'ttnDeviceId' => $ttn_device_id,
            'ttnDevEui' => $ttn_dev_eui,
            'payloadMacAddress' => $payloadMacAddress,
            'frameCounter' => $frame_counter,
            'payloadType' => $payloadType,
            'payloadSchema' => $payloadSchema,
            'payloadDecoderSource' => $payloadDecoderSource,
            'hasDecodedPayload' => $hasDecodedPayload,
            'hasNormalizedPayload' => $hasNormalizedPayload
        )
    );

    if (!$hasDecodedPayload) {
        writeToLogFunction::info(
            'TTN uplink arrived without decoded_payload. Fallback extraction used.',
            $_SERVER["SCRIPT_FILENAME"],
            array(
                'ttnDeviceId' => $ttn_device_id,
                'ttnDevEui' => $ttn_dev_eui,
                'fallbackSource' => $payloadDecoderSource
            )
        );
    }

    $server_datetime = date("Y-m-d H:i:s", time());

    if ($sensor_raw_payload != null) {
      $ttnDebugStatement = $pdo2->prepare(
        "INSERT INTO `ttnDataLoraBoatMonitor`
          (`datetime`, `app_id`, `dev_id`, `ttn_timestamp`, `gtw_id`, `gtw_rssi`,
           `gtw_snr`, `gtw_channel_index`, `gtw_bandwidth`, `gtw_sf`, `dev_counter`,
           `dev_raw_payload`, `dev_value_1`, `dev_value_2`, `dev_value_3`, `dev_value_4`)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
      );
      $ttnDebugStatement->execute(array(
        $server_datetime,
        $ttn_app_id,
        $ttn_dev_id,
        $ttn_time,
        $gtw_id,
        $gtw_rssi,
        $gtw_snr,
        2,
        55,
        4,
        (int)$frame_counter,
        $sensor_raw_payload,
        $sensor_temperature,
        $sensor_temperature_2,
        $sensor_humidity,
        $sensor_battery
      ));
    }

    $boardResolvedBy = null;
    $singleRowBoardIdbyTTN = null;
    if ($payloadMacAddress !== null && $payloadMacAddress !== '') {
        $singleRowBoardIdbyTTN = myFunctions::getBoardByMacAddress($payloadMacAddress);
        if ($singleRowBoardIdbyTTN) {
            $boardResolvedBy = 'macAddress';
            myFunctions::updateBoardTTNIdentifiersIfEmpty($singleRowBoardIdbyTTN['id'], $ttn_app_id, $ttn_board_identifier);
        }
    }

    if (!$singleRowBoardIdbyTTN) {
        $singleRowBoardIdbyTTN = myFunctions::getBoardByTTN($ttn_app_id, $ttn_dev_eui, $ttn_device_id);
        if ($singleRowBoardIdbyTTN) {
            $boardResolvedBy = 'ttnLegacy';
        }
    }

    $myFunctions = new myFunctions();
    
    // if board not exist, create it.
    if (!$singleRowBoardIdbyTTN) {
        $newId = myFunctions::addBoardByTTN($ttn_app_id, $ttn_board_identifier, $payloadMacAddress);
        writeToLogFunction::info(
            'New board created from TTN uplink.',
            $_SERVER["SCRIPT_FILENAME"],
            array(
                'boardId' => $newId,
                'ttnAppId' => $ttn_app_id,
                'ttnDeviceIdentifier' => $ttn_board_identifier,
                'payloadMacAddress' => $payloadMacAddress
            )
        );
        $singleRowBoardIdbyTTN = $payloadMacAddress !== null && $payloadMacAddress !== ''
            ? myFunctions::getBoardByMacAddress($payloadMacAddress)
            : myFunctions::getBoardByTTN($ttn_app_id, $ttn_dev_eui, $ttn_device_id);
        $boardResolvedBy = $payloadMacAddress !== null && $payloadMacAddress !== '' ? 'macAddress.created' : 'ttnLegacy.created';
    } elseif ($payloadMacAddress !== null && $payloadMacAddress !== '' && $boardResolvedBy === 'ttnLegacy') {
        $macAddressUpdated = myFunctions::updateBoardMacAddressIfPlaceholder($singleRowBoardIdbyTTN['id'], $payloadMacAddress);
        if ($macAddressUpdated) {
            $singleRowBoardIdbyTTN['macAddress'] = $payloadMacAddress;
            writeToLogFunction::info(
                'Existing TTN board migrated from placeholder MAC to payload MAC.',
                $_SERVER["SCRIPT_FILENAME"],
                array(
                    'boardId' => $singleRowBoardIdbyTTN['id'],
                    'payloadMacAddress' => $payloadMacAddress,
                    'ttnAppId' => $ttn_app_id,
                    'ttnDeviceIdentifier' => $ttn_board_identifier
                )
            );
        } elseif (myFunctions::normalizeMacAddress($singleRowBoardIdbyTTN['macAddress'] ?? '') !== $payloadMacAddress) {
            writeToLogFunction::warning(
                'TTN payload MAC differs from existing board MAC. Keeping existing board MAC to avoid accidental re-assignment.',
                $_SERVER["SCRIPT_FILENAME"],
                array(
                    'boardId' => $singleRowBoardIdbyTTN['id'],
                    'existingMacAddress' => $singleRowBoardIdbyTTN['macAddress'] ?? null,
                    'payloadMacAddress' => $payloadMacAddress,
                    'ttnAppId' => $ttn_app_id,
                    'ttnDeviceIdentifier' => $ttn_board_identifier
                )
            );
        }
    }

    if (!$singleRowBoardIdbyTTN) {
      writeToLogFunction::error(
        'TTN board could not be resolved or created.',
        $_SERVER["SCRIPT_FILENAME"],
        array(
          'ttnAppId' => $ttn_app_id,
          'ttnDeviceId' => $ttn_device_id,
          'ttnDevEui' => $ttn_dev_eui,
          'payloadMacAddress' => $payloadMacAddress
        )
      );
      ttnJsonResponse(500, array('error' => 'TTN board could not be resolved or created.'));
    }

    writeToLogFunction::info(
      'TTN board resolved.',
      $_SERVER["SCRIPT_FILENAME"],
      array(
        'boardId' => $singleRowBoardIdbyTTN['id'],
        'resolvedBy' => $boardResolvedBy,
        'boardMacAddress' => $singleRowBoardIdbyTTN['macAddress'] ?? null,
        'payloadMacAddress' => $payloadMacAddress
      )
    );
    
    $allSensorsOfBoard = myFunctions::getAllSensorsOfBoard($singleRowBoardIdbyTTN['id']);
    $createdSensors = false;
    if (!$isDeviceConfigPayload && !$isNamedMeasurementPayload) {
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
    }

    $url = $config::$baseurl . '/ingest/receivejson.php';
    $ch = curl_init($url);

    writeToLogFunction::info(
      'Forwarding TTN payload to receivejson.php.',
      $_SERVER["SCRIPT_FILENAME"],
      array('targetUrl' => $url, 'boardId' => $singleRowBoardIdbyTTN['id'])
    );

    $boardInfos = array(
        "apiKey" => $config::$apiKey,
        "macAddress" => $singleRowBoardIdbyTTN['macAddress'],
        "protocolVersion" => "1"   // Version of the used protocoll.
    );
    if ($firmwareVersion !== null) {
      $boardInfos["firmwareVersion"] = $firmwareVersion;
    }
    if ($standbyState !== null) {
      $boardInfos["standbyState"] = $standbyState;
    }

    $dateNow = date("d.m.Y");
    $timeNow = date("H:i:s");   

    $sensor1 = $sensor2 = $sensor3 = null;
    $sensors = array();

    if ($isNamedMeasurementPayload) {
      $commonSensorFields = array(
        "date" => $dateNow,
        "time" => $timeNow,
        "transmissionPath" => "2"
      );
      $sensors[] = array_merge($commonSensorFields, array(
        "sensorType" => "ADC", "type" => "ADC", "sensorName" => "Battery", "name" => "Battery",
        "value1" => $sensor_battery, "value2" => $sensor_battery_capacity, "value3" => 0, "value4" => 0
      ));
      $sensors[] = array_merge($commonSensorFields, array(
        "sensorType" => "ADC", "type" => "ADC", "sensorName" => "Tanks", "name" => "Tanks",
        "value1" => $sensor_level1, "value2" => $sensor_tank1_adc,
        "value3" => $sensor_level2, "value4" => $sensor_tank2_adc
      ));
      $sensors[] = array_merge($commonSensorFields, array(
        "sensorType" => "Digital", "type" => "Digital", "sensorName" => "Status", "name" => "Status",
        "value1" => $sensor_alarm1, "value2" => $sensor_relay,
        "value3" => $sensor_temperature_2, "value4" => 0
      ));
      $sensors[] = array_merge($commonSensorFields, array(
        "sensorType" => "GPS", "type" => "GPS", "sensorName" => "GPS", "name" => "GPS",
        "value1" => $sensor_latitude, "value2" => $sensor_longitude,
        "value3" => $sensor_speed, "value4" => $sensor_course
      ));
      if ($environment_present) {
        $sensors[] = array_merge($commonSensorFields, array(
          "sensorType" => "BME280", "type" => "BME280", "sensorName" => "Environment", "name" => "Environment",
          "value1" => $sensor_temperature, "value2" => $sensor_humidity,
          "value3" => $sensor_pressure, "value4" => $sensor_altitude
        ));
        $sensors[] = array_merge($commonSensorFields, array(
          "sensorType" => "BME280", "type" => "BME280", "sensorName" => "Dewpoint", "name" => "Dewpoint",
          "value1" => $sensor_dewpoint, "value2" => 0, "value3" => 0, "value4" => 0
        ));
      }
      if ($vedirect_present) {
        $sensors[] = array_merge($commonSensorFields, array(
          "sensorType" => "DS2438", "type" => "DS2438", "sensorName" => "VEdirect", "name" => "VEdirect",
          "value1" => $sensor_vedirect_voltage, "value2" => $sensor_vedirect_current,
          "value3" => $sensor_vedirect_temperature, "value4" => 0
        ));
      }
      $sensors[] = array_merge($commonSensorFields, array(
        "sensorType" => "Lora", "type" => "Lora", "sensorName" => "Lora", "name" => "Lora",
        "value1" => $gtw_id, "value2" => $gtw_rssi, "value3" => $gtw_snr, "value4" => $frame_counter
      ));
      if ($wakeup_event_present && $standby_event_epoch > 0 && $wakeup_event_epoch >= $standby_event_epoch) {
        $sensors[] = array_merge($commonSensorFields, array(
          "sensorType" => "WakeupStan", "type" => "WakeupStan", "sensorName" => "WakeupLog", "name" => "WakeupLog",
          "value1" => $standby_event_cause,
          "value2" => date("d.m.Y H:i:s", $standby_event_epoch),
          "value3" => $wakeup_event_cause,
          "value4" => date("d.m.Y H:i:s", $wakeup_event_epoch)
        ));
      } elseif ($wakeup_event_present) {
        writeToLogFunction::warning(
          'Ignoring invalid explicit LoRa wakeup event.',
          $_SERVER["SCRIPT_FILENAME"],
          array('standbyEpoch' => $standby_event_epoch, 'wakeupEpoch' => $wakeup_event_epoch)
        );
      }
    }

    if (!$isDeviceConfigPayload && !$isNamedMeasurementPayload) foreach($allSensorsOfBoard AS $eachsensor) {
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

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  2);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);

    // Attach encoded JSON string to the POST fields
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    // Set the content type to application/json
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

    // Return response instead of outputting
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    #writeToLogFunction::write_to_log($ch, $_SERVER["SCRIPT_FILENAME"]);

    // Execute the POST request
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
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
      curl_close($ch);
      ttnJsonResponse(502, array(
        'error' => 'cURL forwarding to receivejson failed.',
        'targetUrl' => $url
      ));
    } elseif ($httpCode < 200 || $httpCode >= 300) {
      writeToLogFunction::error(
        'receivejson returned a non-success HTTP status.',
        $_SERVER["SCRIPT_FILENAME"],
        array(
          'targetUrl' => $url,
          'httpCode' => $httpCode,
          'response' => $result,
          'sensorCount' => count($sensors)
        )
      );
      curl_close($ch);
      ttnJsonResponse(502, array(
        'error' => 'receivejson returned a non-success HTTP status.',
        'targetUrl' => $url,
        'httpCode' => $httpCode
      ));
    } else {
      writeToLogFunction::info(
        'cURL forwarding to receivejson succeeded.',
        $_SERVER["SCRIPT_FILENAME"],
        array(
          'targetUrl' => $url,
          'httpCode' => $httpCode,
          'response' => $result,
          'sensorCount' => count($sensors)
        )
      );
      curl_close($ch);
      ttnJsonResponse(200, array(
        'status' => 'ok',
        'boardId' => $singleRowBoardIdbyTTN['id'],
        'forwardedTo' => $url,
        'forwardHttpCode' => $httpCode,
        'sensorCount' => count($sensors)
      ));
    }
} else {
    writeToLogFunction::warning('TTN endpoint called without body.', $_SERVER["SCRIPT_FILENAME"]);
    ttnJsonResponse(400, array('error' => 'TTN endpoint called without body.'));
}

ttnJsonResponse(500, array('error' => 'Unexpected TTN webhook state.'));

function ttnWebhookSecretIsValid(array $headers, string $expectedSecret) {
    if ($expectedSecret === '') {
        return true;
    }

    $providedSecret = ttnHeaderValue($headers, array('X-MDS-Webhook-Secret', 'X-Webhook-Secret', 'X-TTN-Webhook-Secret'));
    if ($providedSecret === null) {
        return false;
    }

    return hash_equals($expectedSecret, $providedSecret);
}

function ttnFilterHeadersForLogging(array $headers) {
    $filteredHeaders = array();
    foreach ($headers as $name => $value) {
        if (stripos((string)$name, 'secret') !== false || stripos((string)$name, 'authorization') !== false) {
            $filteredHeaders[$name] = '***';
        } else {
            $filteredHeaders[$name] = $value;
        }
    }
    return $filteredHeaders;
}

function ttnHeaderValue(array $headers, array $candidates) {
    foreach ($headers as $name => $value) {
        foreach ($candidates as $candidate) {
            if (strcasecmp((string)$name, $candidate) === 0) {
                return is_array($value) ? null : (string)$value;
            }
        }
    }
    return null;
}

function ttnSelectBestRxMetadata($rxMetadataList) {
    if (!is_array($rxMetadataList) || empty($rxMetadataList)) {
        return (object)array();
    }

    usort($rxMetadataList, function ($left, $right) {
        $leftRssi = isset($left->rssi) ? (float)$left->rssi : -INF;
        $rightRssi = isset($right->rssi) ? (float)$right->rssi : -INF;
        return $rightRssi <=> $leftRssi;
    });

    return $rxMetadataList[0];
}

function ttnExtractMeasurementPayload($uplinkMessage) {
    if (isset($uplinkMessage->decoded_payload) && is_object($uplinkMessage->decoded_payload)) {
        return $uplinkMessage->decoded_payload;
    }

    if (isset($uplinkMessage->normalized_payload)) {
        $normalizedPayload = $uplinkMessage->normalized_payload;
        if (is_array($normalizedPayload)) {
            foreach ($normalizedPayload as $payload) {
                if (is_object($payload) && count(get_object_vars($payload)) > 0) {
                    return $payload;
                }
            }
        }
        if (is_object($normalizedPayload) && count(get_object_vars($normalizedPayload)) > 0) {
            return $normalizedPayload;
        }
    }

    return (object)array();
}

function ttnHasNormalizedPayload($uplinkMessage) {
    if (!isset($uplinkMessage->normalized_payload)) {
        return false;
    }

    $normalizedPayload = $uplinkMessage->normalized_payload;
    if (is_object($normalizedPayload)) {
        return count(get_object_vars($normalizedPayload)) > 0;
    }

    if (!is_array($normalizedPayload) || empty($normalizedPayload)) {
        return false;
    }

    foreach ($normalizedPayload as $payload) {
        if (is_object($payload) && count(get_object_vars($payload)) > 0) {
            return true;
        }
    }

    return false;
}

function ttnEncodedPayloadByteLength($encodedPayload) {
    if (!is_string($encodedPayload) || trim($encodedPayload) === '') {
        return 0;
    }

    $decodedPayload = base64_decode($encodedPayload, true);
    return $decodedPayload === false ? null : strlen($decodedPayload);
}

function ttnPayloadValue($payload, array $paths, $default = 0) {
    foreach ($paths as $path) {
        $value = ttnReadPath($payload, $path);
        if ($value !== null) {
            return $value;
        }
    }
    return $default;
}

function ttnFirmwareVersion($payload) {
    $value = ttnPayloadValue($payload, array('firmwareVersion', 'firmware_version', 'fwVersion', 'firmware'), null);
    if ($value === null) {
        return null;
    }

    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }

    return substr($value, 0, 64);
}

function ttnStandbyState($payload) {
    $stateValue = ttnPayloadValue($payload, array('standbyState', 'standby_state', 'powerState', 'deviceState', 'sleepState'), null);
    $normalizedState = ttnNormalizeStandbyStateValue($stateValue);
    if ($normalizedState !== null) {
        return $normalizedState;
    }

    return null;
}

function ttnMacAddress($payload) {
    $value = ttnPayloadValue(
        $payload,
        array('macAddress', 'mac_address', 'mac', 'deviceMac', 'device_mac', 'espMac', 'esp_mac', 'chipMac', 'chip_mac'),
        null
    );
    if ($value === null) {
        return null;
    }

    $normalizedMacAddress = myFunctions::normalizeMacAddress($value);
    return $normalizedMacAddress === '' ? null : $normalizedMacAddress;
}

function ttnNormalizeStandbyStateValue($value) {
    return DevicePowerState::normalize($value);
}

function ttnNormalizeBooleanValue($value) {
    if (is_bool($value)) {
        return $value;
    }

    if (is_int($value) || is_float($value)) {
        return ((int)$value) !== 0;
    }

    if (!is_string($value)) {
        return null;
    }

    $normalizedValue = mb_strtolower(trim($value));
    if ($normalizedValue === '') {
        return null;
    }

    if (in_array($normalizedValue, array('1', 'true', 'yes', 'on', 'enabled'), true)) {
        return true;
    }

    if (in_array($normalizedValue, array('0', 'false', 'no', 'off', 'disabled'), true)) {
        return false;
    }

    return null;
}

function ttnJsonResponse($statusCode, array $payload) {
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function ttnReadPath($payload, $path) {
    if (!is_object($payload) && !is_array($payload)) {
        return null;
    }

    $current = $payload;
    foreach (explode('.', $path) as $segment) {
        if (is_object($current) && isset($current->{$segment})) {
            $current = $current->{$segment};
            continue;
        }

        if (is_array($current) && array_key_exists($segment, $current)) {
            $current = $current[$segment];
            continue;
        }

        return null;
    }

    return $current;
}
?>
