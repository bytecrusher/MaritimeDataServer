<?php
declare(strict_types=1);

require_once __DIR__ . "/common.php";

if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "GET") {
    mds_api_error("method_not_allowed", "Use GET for boards.", 405);
}

$userObj = mds_api_require_user();
$scope = (string)($_GET["scope"] ?? "dashboard");
$includeAllOwnedBoards = $scope === "owned" || $scope === "all";
$pdo = dbConfig::getInstance();
$boards = [];

$sensorWhere = $includeAllOwnedBoards
    ? "sensorConfig.boardId = ?"
    : "sensorConfig.boardId = ? AND sensorConfig.onDashboard = 1";
$channelWhere = $includeAllOwnedBoards
    ? "sensorConfigId = ?"
    : "sensorConfigId = ? AND onDashboard = 1";

$boardRows = myFunctions::getMyBoards((int)$userObj->getId());
if (!$includeAllOwnedBoards) {
    $boardRows = array_values(array_filter($boardRows, static function ($boardRow) {
        return (string)($boardRow["onDashboard"] ?? "0") === "1";
    }));
}

$boardTypeStatement = $pdo->prepare("SELECT name FROM boardType WHERE id = ? LIMIT 1");

$sensorStatement = $pdo->prepare(
    "SELECT sensorConfig.*, sensorTypes.name AS typeName, sensorTypes.description AS typeDescription,
            sensorTypes.siUnitVal1, sensorTypes.siUnitVal2, sensorTypes.siUnitVal3, sensorTypes.siUnitVal4
       FROM sensorConfig
       LEFT JOIN sensorTypes ON sensorTypes.id = sensorConfig.typId
      WHERE " . $sensorWhere . "
      ORDER BY sensorConfig.id"
);

$channelStatement = $pdo->prepare(
    "SELECT *
       FROM sensorChannelConfig
      WHERE " . $channelWhere . "
      ORDER BY channelNr"
);

$latestStatement = $pdo->prepare(
    "SELECT id, sensorId, value1, value2, value3, value4, val_date, val_time, reading_time, transmissionPath
       FROM sensorData
      WHERE sensorId = ?
      ORDER BY id DESC
      LIMIT 1"
);

foreach ($boardRows as $boardRow) {
    $boardTypeName = null;
    if (!empty($boardRow["boardTypeId"])) {
        $boardTypeStatement->execute([(int)$boardRow["boardTypeId"]]);
        $boardTypeName = $boardTypeStatement->fetchColumn() ?: null;
    }

    $sensorStatement->execute([(int)$boardRow["id"]]);
    $sensorRows = array_values(array_filter($sensorStatement->fetchAll(PDO::FETCH_ASSOC), static function ($sensorRow) use ($userObj) {
        return myFunctions::canUserAccessSensor((int)$userObj->getId(), (int)$sensorRow["id"]);
    }));

    $sensors = [];
    $latestBoardReadingTime = null;
    $latestTransmissionPath = null;

    foreach ($sensorRows as $sensorRow) {
        $latestStatement->execute([(int)$sensorRow["id"]]);
        $latestRow = $latestStatement->fetch(PDO::FETCH_ASSOC) ?: null;

        if ($latestRow !== null) {
            if ($latestBoardReadingTime === null || strtotime($latestRow["reading_time"]) > strtotime($latestBoardReadingTime)) {
                $latestBoardReadingTime = $latestRow["reading_time"];
                $latestTransmissionPath = $latestRow["transmissionPath"];
            }
        }

        $channelStatement->execute([(int)$sensorRow["id"]]);
        $channelRows = $channelStatement->fetchAll(PDO::FETCH_ASSOC);

        $channels = [];
        foreach ($channelRows as $channelRow) {
            $channelNr = (int)$channelRow["channelNr"];
            $valueKey = "value" . $channelNr;
            $unitKey = "siUnitVal" . $channelNr;

            $channels[] = [
                "id" => (int)$channelRow["id"],
                "channelNr" => $channelNr,
                "name" => $channelRow["name"],
                "description" => $channelRow["description"],
                "locationOfMeasurement" => $channelRow["locationOfMeasurement"],
                "unit" => $sensorRow[$unitKey] ?? null,
                "onDashboard" => mds_api_bool($channelRow["onDashboard"]),
                "dashboardOrderNr" => $channelRow["DashboardOrderNr"] !== null ? (int)$channelRow["DashboardOrderNr"] : null,
                "chartColor" => $channelRow["ChartColor"],
                "gauge" => [
                    "min" => mds_api_float_or_null($channelRow["GaugeMinValue"]),
                    "max" => mds_api_float_or_null($channelRow["GaugeMaxValue"]),
                    "redLowValue" => mds_api_float_or_null($channelRow["GaugeRedAreaLowValue"]),
                    "redLowColor" => $channelRow["GaugeRedAreaLowColor"],
                    "redHighValue" => mds_api_float_or_null($channelRow["GaugeRedAreaHighValue"]),
                    "redHighColor" => $channelRow["GaugeRedAreaHighColor"],
                    "normalColor" => $channelRow["GaugeNormalAreaColor"]
                ],
                "latestValue" => $latestRow !== null ? mds_api_float_or_null($latestRow[$valueKey] ?? null) : null,
                "latestReadingTime" => $latestRow["reading_time"] ?? null
            ];
        }

        $sensors[] = [
            "id" => (int)$sensorRow["id"],
            "name" => $sensorRow["name"],
            "description" => $sensorRow["description"],
            "sensorAddress" => $sensorRow["sensorAddress"],
            "typeId" => $sensorRow["typId"] !== null ? (int)$sensorRow["typId"] : null,
            "typeName" => $sensorRow["typeName"],
            "typeDescription" => $sensorRow["typeDescription"],
            "locationOfMeasurement" => $sensorRow["locationOfMeasurement"],
            "onDashboard" => mds_api_bool($sensorRow["onDashboard"]),
            "nrOfUsedSensors" => (int)$sensorRow["NrOfUsedSensors"],
            "latestReading" => $latestRow,
            "channels" => $channels
        ];
    }

    $offlineMinutes = (int)($boardRow["offlineDataTimer"] ?? 15);
    $isOnline = checkDeviceIsOnline((int)$boardRow["id"]);

    $boards[] = [
        "id" => (int)$boardRow["id"],
        "macAddress" => $boardRow["macAddress"],
        "name" => $boardRow["name"],
        "location" => $boardRow["location"],
        "description" => $boardRow["description"],
        "firmwareVersion" => $boardRow["firmwareVersion"],
        "boardTypeId" => $boardRow["boardTypeId"] !== null ? (int)$boardRow["boardTypeId"] : null,
        "boardTypeName" => $boardTypeName,
        "onDashboard" => mds_api_bool($boardRow["onDashboard"]),
        "alarmOnUnavailable" => mds_api_bool($boardRow["alarmOnUnavailable"]),
        "updateDataTimer" => $boardRow["updateDataTimer"] !== null ? (int)$boardRow["updateDataTimer"] : null,
        "offlineDataTimer" => $offlineMinutes,
        "isOnline" => $isOnline,
        "latestReadingTime" => $latestBoardReadingTime,
        "latestTransmissionPath" => $latestTransmissionPath !== null ? (int)$latestTransmissionPath : null,
        "sensors" => $sensors
    ];
}

mds_api_json([
    "ok" => true,
    "fetchedAt" => gmdate("c"),
    "scope" => $includeAllOwnedBoards ? "owned" : "dashboard",
    "user" => [
        "id" => (int)$userObj->getId(),
        "email" => $userObj->getEmail()
    ],
    "boards" => $boards
]);
