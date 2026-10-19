<?php

require_once dirname(__DIR__, 2) . '/Infrastructure/Database/dbConfig.func.php';
require_once dirname(__DIR__, 2) . '/Infrastructure/Config/configuration.php';
require_once dirname(__DIR__, 2) . '/Infrastructure/Logging/writeToLogFunction.func.php';
require_once dirname(__DIR__, 2) . '/Application/myFunctions.func.php';
require_once dirname(__DIR__, 2) . '/Application/SensorMetadataService.php';

header('Content-Type: application/json; charset=utf-8');

function sensorMetadataResponse($statusCode, array $payload)
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sensorMetadataResponse(405, array('status' => 'error', 'error' => 'POST required.'));
}

$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    sensorMetadataResponse(400, array('status' => 'error', 'error' => 'Invalid JSON payload.'));
}

$config = new configuration();
$board = $payload['board'] ?? array();
if (!hash_equals((string)$config::$apiKey, (string)($board['apiKey'] ?? $board['api_key'] ?? ''))) {
    sensorMetadataResponse(403, array('status' => 'error', 'error' => 'Invalid API key.'));
}
if ((string)($board['protocolVersion'] ?? '') !== '1') {
    sensorMetadataResponse(400, array('status' => 'error', 'error' => 'Unsupported protocol version.'));
}

$macAddress = myFunctions::normalizeMacAddress((string)($board['macAddress'] ?? ''));
if (!preg_match('/^[A-F0-9]{2}(:[A-F0-9]{2}){5}$/', $macAddress)) {
    sensorMetadataResponse(400, array('status' => 'error', 'error' => 'Invalid MAC address.'));
}

try {
    $pdo = dbConfig::getInstance();
    $macHex = strtoupper(preg_replace('/[^A-F0-9]/', '', $macAddress));
    $boardQuery = $pdo->prepare(
        "SELECT id FROM boardConfig WHERE REPLACE(REPLACE(UPPER(macAddress), ':', ''), '-', '') = ? LIMIT 1"
    );
    $boardQuery->execute(array($macHex));
    $boardId = $boardQuery->fetchColumn();
    if ($boardId === false) {
        $createBoard = $pdo->prepare("INSERT INTO boardConfig (macAddress, ownerUserId, name) VALUES (?, 1, '- new imported -')");
        $createBoard->execute(array($macAddress));
        $boardId = $pdo->lastInsertId();
    }

    $definitions = is_array($payload['sensors'] ?? null) ? $payload['sensors'] : array();
    $pdo->beginTransaction();
    $sensors = SensorMetadataService::synchronize(
        $pdo,
        (int)$boardId,
        $macAddress,
        $definitions,
        !empty($payload['pushNames'])
    );
    $pdo->commit();
    $metadataHash = SensorMetadataService::metadataHash($sensors);
    $clientHash = strtolower(trim((string)($payload['metadataHash'] ?? '')));
    $response = array(
        'status' => 'ok',
        'boardId' => (int)$boardId,
        'metadataHash' => $metadataHash,
        'changed' => $clientHash !== $metadataHash,
    );
    if ($clientHash !== $metadataHash || !empty($payload['pushNames'])) {
        $response['sensors'] = $sensors;
    }
    sensorMetadataResponse(200, $response);
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    writeToLogFunction::exception($exception, $_SERVER['SCRIPT_FILENAME'], array('macAddress' => $macAddress));
    sensorMetadataResponse(500, array('status' => 'error', 'error' => 'Sensor metadata synchronization failed.'));
}
