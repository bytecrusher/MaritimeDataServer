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
                $boardSensors = myFunctions::getAllSensorsOfBoard($macAddressId);
                $usedBoardSensorIds = array();
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
                            if ($sensorId != null) {
                                $usedBoardSensorIds[] = (int)$sensorId;
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
                                if ($sensorId != null) {
                                    $usedBoardSensorIds[] = (int)$sensorId;
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
                                    writeToLogFunction::info(
                                        'Sensor resolved automatically from board config.',
                                        $_SERVER["SCRIPT_FILENAME"],
                                        array(
                                            'boardId' => $macAddressId,
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
                                    writeToLogFunction::info(
                                        'Missing sensorConfig was auto-created from payload metadata.',
                                        $_SERVER["SCRIPT_FILENAME"],
                                        array(
                                            'boardId' => $macAddressId,
                                            'sensorId' => $sensorId,
                                            'sensorName' => $createdSensor['name'] ?? null,
                                            'sensorTypeName' => $createdSensor['sensorTypesName'] ?? null
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
                                    'payloadSensor' => $sensor
                                )
                            );
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
    $typeCandidates = array(
        $sensor['sensorType'] ?? null,
        $sensor['type'] ?? null,
        $sensor['sensorName'] ?? null,
        $sensor['name'] ?? null
    );

    foreach ($typeCandidates as $candidate) {
        $canonicalName = resolveCanonicalSensorTypeName($candidate, $boardSensors, $pdo2);
        if ($canonicalName !== null) {
            return $canonicalName;
        }
    }

    return null;
}

function resolveCanonicalSensorTypeName($candidate, array $boardSensors, PDO $pdo2)
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

    return $sensorType['name'] ?? null;
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
