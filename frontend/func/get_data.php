<?php
/**
 * provide a function or checkin if a given board is online
 * 
 * @author: Guntmar Hoeche
 * @license: TBD
 */

require_once("func/dbConfig.func.php");

/**
 * Checks that the Board is Online WHERE sensor = 'DS18B20'.
 * @return Returns the row of the Board is Online
 */
function checkDeviceIsOnline($boardid) {
    $pdo = dbConfig::getInstance();
    $maxtimeout=strtotime("-15 Minutes"."+1 hour");
    $statement = $pdo->prepare("UPDATE boardconfig SET isOnline = ? WHERE id = ?");

    // get all sensors
    $query2 = sprintf("SELECT * FROM sensorconfig WHERE boardid = " . $boardid . " ORDER BY id");
    $result2 = $pdo->query($query2);
    foreach ($result2 as $row2) {
        $query = sprintf("SELECT * FROM sensordata WHERE sensorid = " . $row2['id'] . " ORDER BY id DESC LIMIT 1");
        $result = $pdo->query($query);
        $data = array();
        foreach ($result as $row) {
            $data[] = $row;
            $dbtimestamp=strtotime($data[0]['reading_time']);
            if ($dbtimestamp >= $maxtimeout) {
                $statement->execute(array('1', $boardid));
                return true;
            }
        }
    }
    return false;
}

/**
 * Checks that the Board is Online WHERE sensor = 'DS18B20'.
 * @return Returns the row of the Board is Online
 */
function checkDeviceIsOnline2() {
    $pdo = dbConfig::getInstance();
    $query = sprintf("SELECT * FROM sensordata WHERE sensorid = '7' ORDER BY id DESC LIMIT 1");
    $result = $pdo->query($query);
    $data = array();
    foreach ($result as $row) {
        $data[] = $row;
    }

    $timestamp = time();
    $nurDatum = date('d.m.Y', $timestamp);
    $nurUhrmitSekunden = date('H:i:s', $timestamp);
	return $data;
}
?>
