<?php
/**
 * provide a function or checkin if a given board is online
 * 
 * @author: Guntmar Hoeche
 * @license: TBD
 */

require_once(__DIR__ . "/../../Infrastructure/Database/dbConfig.func.php");
require_once(__DIR__ . "/board.class.php");

/**
 * Checks that the Board is Online WHERE sensor = 'DS18B20'.
 * @return bool the row of the Board is Online
 */
function checkDeviceIsOnline($boardId) {
    $pdo = dbConfig::getInstance();
    $boardObj = new board($boardId);
    $offlineDataTimer = (int)$boardObj->getOfflineDataTimer();
    if ($offlineDataTimer <= 0) {
        $offlineDataTimer = 15;
        writeToLogFunction::warning(
            'Board has invalid offlineDataTimer, fallback to 15 minutes.',
            $_SERVER["SCRIPT_FILENAME"],
            array('boardId' => $boardId)
        );
    }
    $maxTimeout = strtotime("-" . $offlineDataTimer . " Minutes");
    $config = new configuration();

    if ($config::$demoMode) {
        return true;
    }

    $statement = $pdo->prepare(
        "SELECT MAX(sensorData.reading_time) AS latestReadingTime
        FROM sensorData
        INNER JOIN sensorConfig ON sensorConfig.id = sensorData.sensorId
        WHERE sensorConfig.boardId = ?"
    );
    $statement->execute(array($boardId));
    $latestRow = $statement->fetch(PDO::FETCH_ASSOC);

    if (($latestRow === false) || empty($latestRow['latestReadingTime'])) {
        writeToLogFunction::debug(
            'Board has no sensorData entries yet.',
            $_SERVER["SCRIPT_FILENAME"],
            array('boardId' => $boardId)
        );
        return false;
    }

    $dbTimestamp = strtotime($latestRow['latestReadingTime']);
    return ($dbTimestamp !== false) && ($dbTimestamp >= $maxTimeout);
}
