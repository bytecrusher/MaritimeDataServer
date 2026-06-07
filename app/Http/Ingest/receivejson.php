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

// legacy include removed during public webroot migration
header('Content-Type: application/json; charset=utf-8');

$config  = new configuration();

$apiKey_value = $config::$apiKey;
//$apiKey = $protocolVersion = $macAddress = $sensor = $sensorId = $location = $value1 = $value2 = $value3 = $value4 = $date = $time = $transmissionPath = "";
$apiKey = $macAddress = $sensor = $sensorId = $location = $value1 = $value2 = $value3 = $value4 = $date = $time = $transmissionPath = "";

$pdo2 = dbConfig::getInstance();
$responseBoardId = null;
$insertedSensorRows = 0;
$skippedSensorRows = 0;
$autoResolvedSensorRows = 0;
$autoCreatedSensorConfigs = 0;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ttn_post = file_get_contents('php://input');
    $data = json_decode($ttn_post, true);

    if (!is_array($data)) {
        writeToLogFunction::error(
            "Invalid JSON payload received by receivejson.php",
            $_SERVER["SCRIPT_FILENAME"],
            array('rawPayload' => $ttn_post)
        );
        ingestJsonResponse(400, array('error' => 'Invalid JSON payload.'));
    }

    $boardData = $data['board'] ?? array();
    $sensors = $data['sensors'] ?? array();
    writeToLogFunction::info(
        'receivejson request received.',
        $_SERVER["SCRIPT_FILENAME"],
        array(
            'board' => summarizeBoardPayloadForLog($boardData),
            'boardKeys' => array_keys($boardData),
            'sensorCount' => is_array($sensors) ? count($sensors) : 0,
            'rawBodyBytes' => strlen($ttn_post)
        )
    );

    if (isset($boardData['apiKey'])) {
        $apiKey = ($boardData['apiKey']);
    } elseif (isset($boardData['api_key'])) {
        $apiKey = ($boardData['api_key']);
    } else {
        writeToLogFunction::warning("Missing API key in board payload.", $_SERVER["SCRIPT_FILENAME"]);
    }

    if ($apiKey == $apiKey_value) {
        writeToLogFunction::info(
            'receivejson API key accepted.',
            $_SERVER["SCRIPT_FILENAME"],
            array(
                'board' => summarizeBoardPayloadForLog($boardData),
                'sensorCount' => is_array($sensors) ? count($sensors) : 0
            )
        );
        if ((isset($boardData['protocolVersion'])) && ($boardData['protocolVersion'] != null)) {
            if ($boardData['protocolVersion'] == "1") {
                writeToLogFunction::info(
                    'receivejson protocol version accepted.',
                    $_SERVER["SCRIPT_FILENAME"],
                    array(
                        'protocolVersion' => $boardData['protocolVersion'],
                        'board' => summarizeBoardPayloadForLog($boardData)
                    )
                );
                if (!isset($boardData['macAddress']) || trim((string)$boardData['macAddress']) === '') {
                    writeToLogFunction::warning("Missing macAddress in board payload.", $_SERVER["SCRIPT_FILENAME"], array('board' => $boardData));
                    ingestJsonResponse(400, array('error' => 'Missing board.macAddress.'));
                }
                $macAddress = test_input($boardData['macAddress']);
                $macAddressId = check_macAddress($macAddress, $pdo2);
                $responseBoardId = $macAddressId;
                $firmwareVersion = extractFirmwareVersionFromBoardPayload($boardData);
                if ($firmwareVersion !== null) {
                    updateBoardFirmwareVersion($macAddressId, $firmwareVersion, $pdo2);
                }
                $boardSensors = myFunctions::getAllSensorsOfBoard($macAddressId);
                $boardSensorCount = is_array($boardSensors) ? count($boardSensors) : 0;
                writeToLogFunction::info(
                    'Board resolved for receivejson payload.',
                    $_SERVER["SCRIPT_FILENAME"],
                    array(
                        'boardId' => $macAddressId,
                        'macAddress' => $macAddress,
                        'firmwareVersion' => $firmwareVersion,
                        'sensorConfigCount' => $boardSensorCount
                    )
                );
                $usedBoardSensorIds = array();
                $insertStatement = $pdo2->prepare(
                    "INSERT INTO sensorData (sensorId, value1, value2, value3, value4, val_date, val_time, transmissionPath)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                );

                foreach ($sensors as $key => &$sensor) {
                    $sensorId = null;
                    $sensorWasAutoResolved = false;
                    $sensorWasAutoCreated = false;
                    $sensorMappingSource = 'unresolved';
                    $value1 = $value2 = $value3 = $value4 = "";
                    if ($sensor != null) {
                        writeToLogFunction::info(
                            'Processing incoming sensor payload.',
                            $_SERVER["SCRIPT_FILENAME"],
                            array(
                                'boardId' => $macAddressId,
                                'sensorIndex' => $key,
                                'sensorPayload' => summarizeSensorPayloadForLog($sensor),
                                'knownSensorConfigCount' => $boardSensorCount
                            )
                        );
                        $mySensorId = $owSensorAddress = null;
                        if (isset($sensor["sensorId"])) {
                            $mySensorId = test_input($sensor["sensorId"]);
                            $sensorId = $mySensorId;
                            $sensorMappingSource = 'payload.sensorId';
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
                            if ($sensorId != null) {
                                $usedBoardSensorIds[] = (int)$sensorId;
                                writeToLogFunction::info(
                                    'Using explicit sensorId from payload.',
                                    $_SERVER["SCRIPT_FILENAME"],
                                    array(
                                        'boardId' => $macAddressId,
                                        'sensorIndex' => $key,
                                        'sensorId' => $sensorId,
                                        'sensorPayload' => summarizeSensorPayloadForLog($sensor)
                                    )
                                );
                            }
                        } else {
                            if(isset($sensor["sensorAddress"])) {
                                $owSensorAddress = test_input($sensor["sensorAddress"]);
                                writeToLogFunction::info(
                                    'Attempting sensor resolution via sensorAddress.',
                                    $_SERVER["SCRIPT_FILENAME"],
                                    array(
                                        'boardId' => $macAddressId,
                                        'sensorIndex' => $key,
                                        'sensorAddress' => $owSensorAddress
                                    )
                                );
                                $sensorId = checkOwSensorAddress($owSensorAddress, $macAddressId, $pdo2);
                                if (substr($owSensorAddress, 0, 2) === "28") {
                                    $value1 = test_input($sensor["value1"]);
                                    $value2 = null;
                                    $value3 = null;
                                } elseif (substr($owSensorAddress, 0, 2) === "26") {
                                    $value1 = test_input($sensor["value1"]);
                                    $value2 = test_input($sensor["value2"]);
                                }
                                if ($sensorId != null) {
                                    $usedBoardSensorIds[] = (int)$sensorId;
                                    $sensorMappingSource = 'payload.sensorAddress';
                                    writeToLogFunction::info(
                                        'Sensor mapped via sensorAddress.',
                                        $_SERVER["SCRIPT_FILENAME"],
                                        array(
                                            'boardId' => $macAddressId,
                                            'sensorIndex' => $key,
                                            'sensorId' => $sensorId,
                                            'sensorAddress' => $owSensorAddress
                                        )
                                    );
                                }
                            } else {
                               $owSensorAddress = null; 
                            }
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

                            if ($sensorId == null) {
                                $resolvedSensor = resolveBoardSensorFromConfig(
                                    $sensor,
                                    $boardSensors,
                                    $usedBoardSensorIds
                                );
                                if ($resolvedSensor !== null) {
                                    $sensorId = $resolvedSensor['id'];
                                    $usedBoardSensorIds[] = (int)$resolvedSensor['id'];
                                    $sensorWasAutoResolved = true;
                                    $sensorMappingSource = 'boardConfig.match';
                                    writeToLogFunction::info(
                                        'Sensor resolved automatically from board config.',
                                        $_SERVER["SCRIPT_FILENAME"],
                                        array(
                                            'boardId' => $macAddressId,
                                            'sensorIndex' => $key,
                                            'sensorId' => $sensorId,
                                            'sensorName' => $resolvedSensor['name'] ?? null,
                                            'sensorTypeName' => $resolvedSensor['sensorTypesName'] ?? null,
                                            'mappingHint' => array(
                                                'sensorType' => $sensor['sensorType'] ?? ($sensor['type'] ?? null),
                                                'sensorName' => $sensor['sensorName'] ?? ($sensor['name'] ?? null),
                                                'providedValueCount' => countProvidedSensorValues($sensor)
                                            )
                                        )
                                    );
                                }
                            }

                            if ($sensorId == null) {
                                $createdSensor = ensureBoardSensorConfigExists(
                                    $sensor,
                                    $macAddressId,
                                    $boardSensors,
                                    $usedBoardSensorIds,
                                    $pdo2
                                );
                                if ($createdSensor !== null) {
                                    $sensorId = $createdSensor['id'];
                                    $usedBoardSensorIds[] = (int)$createdSensor['id'];
                                    $sensorWasAutoCreated = true;
                                    $sensorMappingSource = 'sensorConfig.autoCreate';
                                    $boardSensorCount = is_array($boardSensors) ? count($boardSensors) : $boardSensorCount;
                                    writeToLogFunction::info(
                                        'Missing sensorConfig was auto-created from payload metadata.',
                                        $_SERVER["SCRIPT_FILENAME"],
                                        array(
                                            'boardId' => $macAddressId,
                                            'sensorIndex' => $key,
                                            'sensorId' => $sensorId,
                                            'sensorName' => $createdSensor['name'] ?? null,
                                            'sensorTypeName' => $createdSensor['sensorTypesName'] ?? null,
                                            'sensorPayload' => summarizeSensorPayloadForLog($sensor),
                                            'sensorConfigCount' => $boardSensorCount
                                        )
                                    );
                                }
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

                        if ($sensorId == null) {
                            writeToLogFunction::warning(
                                'Sensor payload could not be mapped to a sensorConfig entry. Sensor skipped.',
                                $_SERVER["SCRIPT_FILENAME"],
                                array(
                                    'boardId' => $macAddressId,
                                    'sensorIndex' => $key,
                                    'payloadSensor' => $sensor
                                )
                            );
                            $skippedSensorRows++;
                            continue;
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
                            writeToLogFunction::info(
                                'sensorData row inserted.',
                                $_SERVER["SCRIPT_FILENAME"],
                                array(
                                    'boardId' => $macAddressId,
                                    'sensorIndex' => $key,
                                    'sensorId' => $sensorId,
                                    'mappingSource' => $sensorMappingSource,
                                    'transmissionPath' => $transmissionPath,
                                    'date' => $date,
                                    'time' => $time,
                                    'values' => summarizeSensorValuesForLog($value1, $value2, $value3, $value4)
                                )
                            );
                            $insertedSensorRows++;
                            if ($sensorWasAutoResolved) {
                                $autoResolvedSensorRows++;
                            }
                            if ($sensorWasAutoCreated) {
                                $autoCreatedSensorConfigs++;
                            }
                        } catch (PDOException $ex) {
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
                            ingestJsonResponse(500, array('error' => 'An error has occurred while inserting sensorData.'));
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
                    array(
                        'boardId' => $macAddressId,
                        'sensorConfigCount' => $boardSensorCount,
                        'insertedSensorRows' => $insertedSensorRows,
                        'skippedSensorRows' => $skippedSensorRows,
                        'autoResolvedSensorRows' => $autoResolvedSensorRows,
                        'autoCreatedSensorConfigs' => $autoCreatedSensorConfigs
                    )
                );
                ingestJsonResponse(200, array(
                    'status' => 'ok',
                    'boardId' => $macAddressId,
                    'insertedSensorRows' => $insertedSensorRows,
                    'skippedSensorRows' => $skippedSensorRows,
                    'autoResolvedSensorRows' => $autoResolvedSensorRows,
                    'autoCreatedSensorConfigs' => $autoCreatedSensorConfigs
                ));
            }
            ingestJsonResponse(400, array('error' => 'Unsupported protocol version.'));
        } else {
            writeToLogFunction::warning("Wrong protocol version.", $_SERVER["SCRIPT_FILENAME"], array('board' => $boardData));
            ingestJsonResponse(400, array('error' => 'Wrong protocol version.'));
        }
    } else {
        writeToLogFunction::warning(
            "Wrong API Key provided.",
            $_SERVER["SCRIPT_FILENAME"],
            array(
                'providedApiKey' => maskSecretForLog($apiKey),
                'board' => summarizeBoardPayloadForLog($boardData)
            )
        );
        ingestJsonResponse(401, array('error' => 'Wrong API Key provided.'));
    }
} else {
    writeToLogFunction::warning("No data posted with HTTP POST.", $_SERVER["SCRIPT_FILENAME"]);
    ingestJsonResponse(405, array('error' => 'No data posted with HTTP POST.'));
}

ingestJsonResponse(500, array(
    'error' => 'Unexpected receivejson state.',
    'boardId' => $responseBoardId
));

function test_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function ingestJsonResponse($statusCode, array $payload)
{
    http_response_code($statusCode);
    echo json_encode($payload);
    exit;
}

function summarizeBoardPayloadForLog(array $boardData)
{
    return array(
        'protocolVersion' => $boardData['protocolVersion'] ?? null,
        'macAddress' => $boardData['macAddress'] ?? null,
        'firmwareVersion' => extractFirmwareVersionFromBoardPayload($boardData),
        'apiKeyPresent' => isset($boardData['apiKey']) || isset($boardData['api_key']),
        'apiKeyMasked' => maskSecretForLog($boardData['apiKey'] ?? ($boardData['api_key'] ?? null)),
    );
}

function extractFirmwareVersionFromBoardPayload(array $boardData)
{
    foreach (array('firmwareVersion', 'firmware_version', 'fwVersion', 'firmware') as $fieldName) {
        if (!array_key_exists($fieldName, $boardData)) {
            continue;
        }

        $firmwareVersion = trim((string)$boardData[$fieldName]);
        if ($firmwareVersion === '') {
            return null;
        }

        return substr(test_input($firmwareVersion), 0, 64);
    }

    return null;
}

function updateBoardFirmwareVersion($boardId, $firmwareVersion, PDO $pdo2)
{
    $statement = $pdo2->prepare("UPDATE boardConfig SET firmwareVersion = ? WHERE id = ?");
    $statement->execute(array($firmwareVersion, $boardId));
    writeToLogFunction::info(
        'Board firmware version updated from receivejson payload.',
        $_SERVER["SCRIPT_FILENAME"],
        array('boardId' => $boardId, 'firmwareVersion' => $firmwareVersion)
    );
}

function summarizeSensorPayloadForLog(array $sensor)
{
    return array(
        'sensorId' => $sensor['sensorId'] ?? null,
        'sensorAddress' => $sensor['sensorAddress'] ?? null,
        'sensorType' => $sensor['sensorType'] ?? ($sensor['type'] ?? null),
        'sensorName' => $sensor['sensorName'] ?? ($sensor['name'] ?? null),
        'valueCount' => countProvidedSensorValues($sensor),
        'values' => summarizeSensorValuesForLog(
            $sensor['value1'] ?? null,
            $sensor['value2'] ?? null,
            $sensor['value3'] ?? null,
            $sensor['value4'] ?? null
        ),
        'date' => $sensor['date'] ?? null,
        'time' => $sensor['time'] ?? null,
        'transmissionPath' => $sensor['transmissionPath'] ?? ($sensor['transmissionpath'] ?? null),
    );
}

function summarizeSensorValuesForLog($value1, $value2, $value3, $value4)
{
    return array(
        'value1' => normalizeSensorValueForLog($value1),
        'value2' => normalizeSensorValueForLog($value2),
        'value3' => normalizeSensorValueForLog($value3),
        'value4' => normalizeSensorValueForLog($value4),
    );
}

function normalizeSensorValueForLog($value)
{
    if ($value === null || $value === '') {
        return null;
    }

    if (is_numeric($value)) {
        return (float)$value;
    }

    return (string)$value;
}

function maskSecretForLog($secret)
{
    if (!is_string($secret)) {
        return null;
    }

    $trimmedSecret = trim($secret);
    if ($trimmedSecret === '') {
        return null;
    }

    if (strlen($trimmedSecret) <= 8) {
        return str_repeat('*', strlen($trimmedSecret));
    }

    return substr($trimmedSecret, 0, 4) . str_repeat('*', max(0, strlen($trimmedSecret) - 8)) . substr($trimmedSecret, -4);
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

    return false;
}

function resolveBoardSensorFromConfig(array $sensor, array $boardSensors, array $usedBoardSensorIds)
{
    if (empty($boardSensors)) {
        return null;
    }

    $providedValueCount = countProvidedSensorValues($sensor);
    $sensorTypeHint = normalizeSensorLookupValue($sensor['sensorType'] ?? ($sensor['type'] ?? null));
    $sensorNameHint = normalizeSensorLookupValue($sensor['sensorName'] ?? ($sensor['name'] ?? null));

    $unusedSensors = array_values(array_filter($boardSensors, function ($boardSensor) use ($usedBoardSensorIds) {
        return !in_array((int)$boardSensor['id'], $usedBoardSensorIds, true);
    }));

    if (empty($unusedSensors)) {
        $unusedSensors = $boardSensors;
    }

    if ($sensorTypeHint !== null && $sensorNameHint !== null) {
        $exactTypeAndNameMatches = array_values(array_filter($unusedSensors, function ($boardSensor) use ($sensorTypeHint, $sensorNameHint) {
            $boardSensorName = normalizeSensorLookupValue($boardSensor['name'] ?? null);
            $boardSensorTypeName = normalizeSensorLookupValue($boardSensor['sensorTypesName'] ?? null);

            return $sensorTypeHint === $boardSensorTypeName && $sensorNameHint === $boardSensorName;
        }));

        if (count($exactTypeAndNameMatches) === 1) {
            return $exactTypeAndNameMatches[0];
        }

        if ($providedValueCount > 0) {
            $exactTypeAndNameCountMatches = array_values(array_filter($exactTypeAndNameMatches, function ($boardSensor) use ($providedValueCount) {
                return (int)($boardSensor['NrOfUsedSensors'] ?? 0) === $providedValueCount;
            }));
            if (count($exactTypeAndNameCountMatches) >= 1) {
                return $exactTypeAndNameCountMatches[0];
            }
        }

        return null;
    }

    if ($sensorNameHint !== null) {
        $nameMatches = array_values(array_filter($unusedSensors, function ($boardSensor) use ($sensorNameHint) {
            $boardSensorName = normalizeSensorLookupValue($boardSensor['name'] ?? null);
            return $sensorNameHint === $boardSensorName;
        }));

        if (count($nameMatches) === 1) {
            return $nameMatches[0];
        }
    }

    if ($sensorTypeHint !== null) {
        $typeMatches = array_values(array_filter($unusedSensors, function ($boardSensor) use ($sensorTypeHint) {
            $boardSensorTypeName = normalizeSensorLookupValue($boardSensor['sensorTypesName'] ?? null);
            return $sensorTypeHint === $boardSensorTypeName;
        }));

        if (count($typeMatches) === 1) {
            return $typeMatches[0];
        }

        if ($providedValueCount > 0) {
            $typeCountMatches = array_values(array_filter($typeMatches, function ($boardSensor) use ($providedValueCount) {
                return (int)($boardSensor['NrOfUsedSensors'] ?? 0) === $providedValueCount;
            }));
            if (count($typeCountMatches) === 1) {
                return $typeCountMatches[0];
            }
        }
    }

    if ($providedValueCount > 0) {
        $countMatches = array_values(array_filter($unusedSensors, function ($boardSensor) use ($providedValueCount) {
            return (int)($boardSensor['NrOfUsedSensors'] ?? 0) === $providedValueCount;
        }));

        if (count($countMatches) === 1) {
            return $countMatches[0];
        }
    }

    if (count($unusedSensors) === 1) {
        return $unusedSensors[0];
    }

    return null;
}

function ensureBoardSensorConfigExists(array $sensor, $boardId, array &$boardSensors, array $usedBoardSensorIds, PDO $pdo2)
{
    $canonicalTypeName = detectCanonicalSensorTypeName($sensor, $boardSensors, $pdo2);
    if ($canonicalTypeName === null) {
        return null;
    }

    $existingSensor = resolveBoardSensorFromConfig(
        array(
            'sensorType' => $canonicalTypeName,
            'sensorName' => $sensor['sensorName'] ?? ($sensor['name'] ?? null),
            'value1' => $sensor['value1'] ?? null,
            'value2' => $sensor['value2'] ?? null,
            'value3' => $sensor['value3'] ?? null,
            'value4' => $sensor['value4'] ?? null
        ),
        $boardSensors,
        $usedBoardSensorIds
    );

    if ($existingSensor !== null) {
        return $existingSensor;
    }

    $sensorName = determineSensorConfigName($sensor, $canonicalTypeName);

    try {
        $myFunctions = new myFunctions();
        $createdSensorId = $myFunctions->addSensorConfig($boardId, $canonicalTypeName, $canonicalTypeName);
        if ($createdSensorId && $sensorName !== $canonicalTypeName) {
            $renameStatement = $pdo2->prepare("UPDATE sensorConfig SET name = ? WHERE id = ?");
            $renameStatement->execute(array($sensorName, $createdSensorId));
        }
        $boardSensors = myFunctions::getAllSensorsOfBoard($boardId);
    } catch (Throwable $ex) {
        writeToLogFunction::exception(
            $ex,
            $_SERVER["SCRIPT_FILENAME"],
            array(
                'boardId' => $boardId,
                'sensorTypeName' => $canonicalTypeName,
                'sensorName' => $sensorName,
                'payloadSensor' => $sensor
            )
        );
        return null;
    }

    return resolveBoardSensorFromConfig(
        array(
            'sensorType' => $canonicalTypeName,
            'sensorName' => $sensorName,
            'value1' => $sensor['value1'] ?? null,
            'value2' => $sensor['value2'] ?? null,
            'value3' => $sensor['value3'] ?? null,
            'value4' => $sensor['value4'] ?? null
        ),
        $boardSensors,
        $usedBoardSensorIds
    );
}

function detectCanonicalSensorTypeName(array $sensor, array $boardSensors, PDO $pdo2)
{
    $valueCount = max(1, countProvidedSensorValues($sensor));
    $typeCandidates = array(
        array('value' => $sensor['sensorType'] ?? null, 'allowAutoCreate' => true),
        array('value' => $sensor['type'] ?? null, 'allowAutoCreate' => true),
        array('value' => $sensor['sensorName'] ?? null, 'allowAutoCreate' => false),
        array('value' => $sensor['name'] ?? null, 'allowAutoCreate' => false)
    );

    foreach ($typeCandidates as $candidateConfig) {
        $canonicalName = resolveCanonicalSensorTypeName(
            $candidateConfig['value'],
            $boardSensors,
            $pdo2,
            $candidateConfig['allowAutoCreate'],
            $valueCount
        );
        if ($canonicalName !== null) {
            return $canonicalName;
        }
    }

    return null;
}

function resolveCanonicalSensorTypeName($candidate, array $boardSensors, PDO $pdo2, $allowAutoCreate = false, $valueCount = 4)
{
    if (!is_string($candidate)) {
        return null;
    }

    $trimmedCandidate = trim($candidate);
    if ($trimmedCandidate === '') {
        return null;
    }

    $normalizedCandidate = normalizeSensorLookupValue($trimmedCandidate);

    foreach ($boardSensors as $boardSensor) {
        $boardSensorTypeName = $boardSensor['sensorTypesName'] ?? null;
        if (normalizeSensorLookupValue($boardSensorTypeName) === $normalizedCandidate) {
            return $boardSensorTypeName;
        }
    }

    $statement = $pdo2->prepare("SELECT name FROM sensorTypes WHERE LOWER(name) = LOWER(?) LIMIT 1");
    $statement->execute(array($trimmedCandidate));
    $sensorType = $statement->fetch(PDO::FETCH_ASSOC);

    if (isset($sensorType['name'])) {
        return $sensorType['name'];
    }

    if ($allowAutoCreate) {
        return createSensorTypeFromPayloadCandidate($trimmedCandidate, $valueCount, $pdo2);
    }

    return null;
}

function determineSensorConfigName(array $sensor, $canonicalTypeName)
{
    foreach (array('sensorName', 'name') as $nameKey) {
        if (!isset($sensor[$nameKey]) || !is_string($sensor[$nameKey])) {
            continue;
        }

        $trimmedName = trim($sensor[$nameKey]);
        if ($trimmedName === '') {
            continue;
        }

        if (normalizeSensorLookupValue($trimmedName) === normalizeSensorLookupValue($canonicalTypeName)) {
            return $canonicalTypeName;
        }

        return $trimmedName;
    }

    return $canonicalTypeName;
}

function countProvidedSensorValues(array $sensor)
{
    $valueCount = 0;
    foreach (array('value1', 'value2', 'value3', 'value4') as $valueKey) {
        if (array_key_exists($valueKey, $sensor) && $sensor[$valueKey] !== null && $sensor[$valueKey] !== '') {
            $valueCount++;
        }
    }

    return $valueCount;
}

function normalizeSensorLookupValue($value)
{
    if (!is_string($value)) {
        return null;
    }

    $normalizedValue = trim($value);
    if ($normalizedValue === '') {
        return null;
    }

    return mb_strtolower($normalizedValue);
}

function createSensorTypeFromPayloadCandidate($candidate, $valueCount, PDO $pdo2)
{
    $trimmedCandidate = trim((string)$candidate);
    if ($trimmedCandidate === '') {
        return null;
    }

    $maxNrOfValues = max(1, min(4, (int)$valueCount));

    try {
        $statement = $pdo2->prepare("INSERT INTO sensorTypes (name, siUnitVal1, siUnitVal2, siUnitVal3, siUnitVal4, oneWireFamilyCode, description, MaxNrOfValues, hasAddress) VALUES (?, '', '', '', '', '', ?, ?, 0)");
        $statement->execute(array(
            $trimmedCandidate,
            'Auto-created from receivejson ingest',
            $maxNrOfValues
        ));
        writeToLogFunction::info(
            'New sensor type auto-created from receivejson payload.',
            $_SERVER["SCRIPT_FILENAME"],
            array(
                'sensorTypeName' => $trimmedCandidate,
                'maxNrOfValues' => $maxNrOfValues
            )
        );
        return $trimmedCandidate;
    } catch (PDOException $ex) {
        writeToLogFunction::exception(
            $ex,
            $_SERVER["SCRIPT_FILENAME"],
            array(
                'sensorTypeName' => $trimmedCandidate,
                'maxNrOfValues' => $maxNrOfValues
            )
        );
    }

    return null;
}
