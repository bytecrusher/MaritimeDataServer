<?php
declare(strict_types=1);

require_once __DIR__ . "/common.php";

if (($_SERVER["REQUEST_METHOD"] ?? "GET") !== "GET") {
    mds_api_error("method_not_allowed", "Use GET for sensor data.", 405);
}

$userObj = mds_api_require_user();
$data = mds_api_request_data();
$sensorId = isset($data["sensorId"]) ? (int)$data["sensorId"] : 0;
$limit = isset($data["limit"]) ? (int)$data["limit"] : 100;
$limit = max(1, min($limit, 1000));

if ($sensorId <= 0) {
    mds_api_error("missing_sensor_id", "Parameter sensorId is required.", 400);
}

$pdo = dbConfig::getInstance();
if (!myFunctions::canUserAccessSensor((int)$userObj->getId(), $sensorId)) {
    mds_api_error("not_found", "Sensor not found for this user.", 404);
}

$statement = $pdo->prepare(
    "SELECT *
       FROM (
            SELECT id, sensorId, value1, value2, value3, value4, val_date, val_time, reading_time, transmissionPath
              FROM sensorData
             WHERE sensorId = ?
             ORDER BY id DESC
             LIMIT " . $limit . "
       ) AS recentRows
      ORDER BY id ASC"
);
$statement->execute([$sensorId]);
$rows = $statement->fetchAll(PDO::FETCH_ASSOC);

mds_api_json([
    "ok" => true,
    "sensorId" => $sensorId,
    "limit" => $limit,
    "values" => array_map(function ($row) {
        return [
            "id" => (int)$row["id"],
            "sensorId" => (int)$row["sensorId"],
            "value1" => mds_api_float_or_null($row["value1"]),
            "value2" => mds_api_float_or_null($row["value2"]),
            "value3" => mds_api_float_or_null($row["value3"]),
            "value4" => mds_api_float_or_null($row["value4"]),
            "valDate" => $row["val_date"],
            "valTime" => $row["val_time"],
            "readingTime" => $row["reading_time"],
            "transmissionPath" => $row["transmissionPath"] !== null ? (int)$row["transmissionPath"] : null
        ];
    }, $rows)
]);
