<?php

require_once dirname(__DIR__, 2) . '/Infrastructure/Database/dbConfig.func.php';
require_once dirname(__DIR__, 2) . '/Infrastructure/Config/configuration.php';
require_once dirname(__DIR__, 2) . '/Infrastructure/Logging/writeToLogFunction.func.php';
require_once dirname(__DIR__, 2) . '/Application/myFunctions.func.php';
require_once dirname(__DIR__, 2) . '/Application/SensorMetadataService.php';
require_once dirname(__DIR__, 2) . '/Application/BoardCredential.php';

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
if (!is_array($board) || BoardCredential::boardId($board['apiKey'] ?? $board['api_key'] ?? null, $config::$boardApiKeyHashes) === null) {
    sensorMetadataResponse(403, array('status' => 'error', 'error' => 'Board-specific API key required.'));
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
    $authenticatedBoard = BoardCredential::resolve($pdo, $board['apiKey'] ?? $board['api_key'] ?? null, $macAddress, $config::$boardApiKeyHashes);
    $boardId = (int)$authenticatedBoard['id'];

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
} catch (DomainException $exception) {
    sensorMetadataResponse(403, array('status' => 'error', 'error' => 'Board credential mismatch.'));
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    writeToLogFunction::exception($exception, $_SERVER['SCRIPT_FILENAME'], array('macAddress' => $macAddress));
    sensorMetadataResponse(500, array('status' => 'error', 'error' => 'Sensor metadata synchronization failed.'));
}
