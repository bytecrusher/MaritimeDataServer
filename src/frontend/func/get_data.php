<?php
/**
 * provide a function or checkin if a given board is online
 * 
 * @author: Guntmar Hoeche
 * @license: TBD
 */

require_once(dirname(__FILE__)."/dbConfig.func.php");
require_once(dirname(__FILE__)."/board.class.php");

/**
 * Checks that the Board is Online WHERE sensor = 'DS18B20'.
 * @return bool the row of the Board is Online
 */
function checkDeviceIsOnline($boardId) {
    $pdo = dbConfig::getInstance();
    $boardObj = new board($boardId);
    $maxTimeout = strtotime("-" . $boardObj->getOfflineDataTimer() . " Minutes"); // For show Online / Offline
    $config = new configuration();

    if ($config::$demoMode) {
        return true;
    }

    // get all sensors
    $query2 = sprintf("SELECT * FROM sensorConfig WHERE boardId = " . $boardId . " ORDER BY id");
    $result2 = $pdo->query($query2);
    foreach ($result2 as $row2) {
        $query = sprintf("SELECT * FROM sensorData WHERE sensorId = " . $row2['id'] . " ORDER BY id DESC LIMIT 1");
        $result = $pdo->query($query);
        $data = array();
        foreach ($result as $row) {
            $data[] = $row;
            $dbTimestamp=strtotime($data[0]['reading_time']);
            if ($dbTimestamp >= $maxTimeout) {
                return true;
            }
        }
    }
    return false;
}