<?php
/**
 * a set of functions for handle some user and board functions.
 *
 * @author: Guntmar Höche
 * @license: TBD
 */

include_once("password.func.php");
include_once("dbConfig.func.php");
require_once(dirname(__FILE__).'/../../configuration.php');
require_once("writeToLogFunction.func.php");
include 'RandomColor.php';
use \Colors\RandomColor;
//writeToLogFunction::write_to_log("test", $_SERVER["SCRIPT_FILENAME"]);

class sensorTyp
{
  public $id;
  public $name;
}

class myFunctions {

	/**
	 * Check if user is checked in.
   * @return bool true when user is checked in, false when not.
	 */
	public static function is_checked_in() {
		return isset($_SESSION['userId']);
	}

	/**
	 * Returns a random string.
	 *
	 * @return string
	 */
	public static function random_string() {
		if(function_exists('openssl_random_pseudo_bytes')) {
			$bytes = openssl_random_pseudo_bytes(16);
			$str = bin2hex($bytes);
		} else if(function_exists('mcrypt_create_iv')) {
			$bytes = mcrypt_create_iv(16, MCRYPT_DEV_URANDOM);
			$str = bin2hex($bytes);
		} else {
			$str = md5(uniqid(configuration::$md5secretString, true));
		}
		return $str;
	}

	/**
	 * Returns the URL to the site without the script name.
	 */
	public static function getSiteURL() {
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
		return $protocol.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/';
	}

  /**
  * Get all of my Board by user id.
  * @return array|null Boards from UserId.
  * @throws Exception — Return Exception message on error.
  */
  public static function getMyBoards($userId) {
    if (!$userId == null) {
      $pdo = dbConfig::getInstance();
      try {
        $myBoards = $pdo->prepare("SELECT * FROM boardConfig WHERE ownerUserId = " . $userId . " ORDER BY id");
        $result = $myBoards->execute();
        return $myBoards->fetchAll(PDO::FETCH_ASSOC);
      } catch (PDOException $e) {
        writeToLogFunction::write_to_log("Error: Unable to get myBoards for user id: " . $userId, $_SERVER["SCRIPT_FILENAME"]);
        writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
        throw new Exception('Unable to get myBoards.');
      }
    }
    return null;
  }

  /**
  * Get Board by Board id. Only one dataset will return.
  * @return $Board of given id.
  * @throws Exception — Return Exception message on error.
  */
  public static function getBoardById($boardId) {
    if (!$boardId == null) {
      $pdo = dbConfig::getInstance();
      try {
        $myBoards = $pdo->prepare("SELECT * FROM boardConfig WHERE id = " . $boardId . " ORDER BY id LIMIT 1");
        $result = $myBoards->execute();
        return $myBoards->fetch(PDO::FETCH_ASSOC);
      } catch (PDOException $e) {
        writeToLogFunction::write_to_log("Error: Unable to getBoardById for boardId: " . $boardId, $_SERVER["SCRIPT_FILENAME"]);
        writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
        throw new Exception('Unable to getBoardById.');
      }
    }
    return;
  }

  /**
  * Get Board by Board id. Only one dataset will return.
  * @return array|null Board of given Sensor id.
  * @throws Exception — Return Exception message on error.
  */
  public static function getBoardBySensorId($sensorId) {
    if (!$sensorId == null) {
      $pdo = dbConfig::getInstance();
      try {
        $myBoards = $pdo->prepare("SELECT boardId FROM sensorConfig WHERE id = " . $sensorId . " ORDER BY id LIMIT 1");
        $result = $myBoards->execute();
        return $myBoards->fetch(PDO::FETCH_ASSOC);
      } catch (PDOException $e) {
        writeToLogFunction::write_to_log("Error: Unable to getBoardById for sensor id: " . $sensorId, $_SERVER["SCRIPT_FILENAME"]);
        writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
        throw new Exception('Unable to getBoardById.');
      }
    }
    return null;
  }

/*
  * Get Board by Board TTN appid and dev id. Only one dataset will return.
  */
  public static function getBoardByTTN($ttnAppId, $ttnDevId) {
    if ((!$ttnAppId == null) && (!$ttnDevId == null)) {
      $pdo = dbConfig::getInstance();
      $myBoards = $pdo->prepare("SELECT * FROM boardConfig WHERE ttnAppId = '$ttnAppId' AND ttnDevId = '$ttnDevId' ORDER BY id LIMIT 1");
      $result = $myBoards->execute();
      $myBoards2 = $myBoards->fetch(PDO::FETCH_ASSOC);
      return $myBoards2;
    }
  }

  /*
  * Add Board by Board TTN appid and dev id. Only one dataset will return.
  */
  public static function addBoardByTTN($ttnAppId, $ttnDevId) {
    $pdo = dbConfig::getInstance();
    $statement = $pdo->prepare("INSERT INTO boardConfig (macAddress, name, ttnAppId, ttnDevId, onDashboard, updateDataTimer  ) VALUES (?, ?, ?, ?, ?, ?)");
    $statement->execute(array("fakeMacAddress" . $ttnDevId, "- new imported -", $ttnAppId, $ttnDevId, 1, 15));
    $neue_id = $pdo->lastInsertId();
    return $neue_id;
  }

  /*
  * Get all sensors of a given board id.
  */
  public static function getAllSensorsOfBoard($id) {
    $pdo = dbConfig::getInstance();
    $mySensors2 = $pdo->prepare("SELECT sensorConfig.*, sensorTypes.name as sensorTypesName FROM sensorConfig, sensorTypes WHERE (boardId = ?) and (typId = sensorTypes.id) ORDER BY sensorConfig.id; ");
    $mySensors2->execute(array($id));
    $sensorsOfBoard = $mySensors2->fetchAll(PDO::FETCH_ASSOC);
    return $sensorsOfBoard;
  }

  /*
  * Get all sensors of a given board id Old.
  */
  public static function getAllSensorsOfBoardOld($id) {
    $pdo = dbConfig::getInstance();
    $mySensors2 = $pdo->prepare("SELECT * FROM sensorConfig WHERE boardId = ? ORDER BY id");
    $mySensors2->execute(array($id));
    $sensorsOfBoard = $mySensors2->fetchAll(PDO::FETCH_ASSOC);
    return $sensorsOfBoard;
  }

  /*
  * Get all sensors of a given board id with dashboard.
  */
  public static function getAllSensorsOfBoardWithDashboardWithTypeName($id) {
    $pdo = dbConfig::getInstance();
    $mySensors2 = $pdo->prepare("SELECT sensorConfig.*, sensorTypes.name as typename FROM sensorConfig, sensorTypes WHERE boardId = ? AND typId = sensorTypes.id AND onDashboard = 1 ORDER BY id");
    $mySensors2->execute(array($id));
    $sensorsOfBoard = $mySensors2->fetchAll(PDO::FETCH_ASSOC);
    return $sensorsOfBoard;
  }

  /*
  * Get the lastet sensor data of a given sensor id.
  */
  public static function getLatestSensorData($sensorId, $maxNrOfValue = 1) {
    if ($maxNrOfValue >= 1) {
      $pdo = dbConfig::getInstance();
      $mySensors = $pdo->prepare("SELECT * FROM sensorData WHERE sensorId IN ($sensorId) ORDER BY id DESC LIMIT $maxNrOfValue");
      $mySensors->execute();
      $SensorData = $mySensors->fetchAll(PDO::FETCH_ASSOC);
      return $SensorData;
    }
  }

  /*
  * Get all GPS data of a given (sensor id).
  */
  public static function getAllGpsData($boardId, $maxNrOfValue = 1) {
    if ($maxNrOfValue >= 1) {
      $pdo = dbConfig::getInstance();
      $mySensors = $pdo->prepare("SELECT sensorConfig.*, sensorTypes.name as typename FROM `sensorConfig`, sensorTypes WHERE boardId = ? AND typId = sensorTypes.id AND sensorTypes.name = 'GPS'");
      $mySensors->execute(array($boardId));
      $SensorData = $mySensors->fetch(PDO::FETCH_ASSOC);
      if ($SensorData != false) {
        $myGps = $pdo->prepare("SELECT * FROM `sensorData` WHERE sensorId = ?");
        $myGps->execute(array($SensorData["id"]));
        $myGpsData = $myGps->fetchAll(PDO::FETCH_ASSOC);
        return $myGpsData;
      } else {
        return 0;
      }
    }
  }

  /*
  * Get Config Object if a given sensorConfig id
  */
  public static function getSensorConfig($id) {
    $pdo = dbConfig::getInstance();
    $mySensors2 = $pdo->prepare("SELECT * FROM sensorConfig WHERE id = ? ORDER BY id LIMIT 1");
    $mySensors2->execute(array($id));
    $sensorsOfBoard = $mySensors2->fetch(PDO::FETCH_ASSOC);
    return $sensorsOfBoard;
  }

  /*
  * Get Config Object if a given sensorConfig id
  */
  public static function getSensorChannelsConfig($id) {
    $pdo = dbConfig::getInstance();
    $mySensorsChannels = $pdo->prepare("SELECT * FROM sensorChannelConfig WHERE sensorConfigId = ? ORDER BY id ");
    $mySensorsChannels->execute(array($id));
    $mySensorsChannelsOfBoard = $mySensorsChannels->fetchAll(PDO::FETCH_ASSOC);
    return $mySensorsChannelsOfBoard;
  }

  /*
  * Get Config Object if a given sensorConfig id
  */
  public static function getSensorChannelConfig($id, $channelNr) {
    $pdo = dbConfig::getInstance();
    $mySensorsChannels = $pdo->prepare("SELECT * FROM sensorChannelConfig WHERE sensorConfigId = ? AND channelNr = ? ORDER BY id ");
    $mySensorsChannels->execute(array($id, $channelNr));
    //$mySensorsChannelsOfBoard = $mySensorsChannels->fetchAll(PDO::FETCH_ASSOC);
    $mySensorsChannelsOfBoard = $mySensorsChannels->fetch(PDO::FETCH_ASSOC);
    return $mySensorsChannelsOfBoard;
  }

  /*
  * Add SensorConfig Object if a given board id
  */
  public function addSensorConfig($boardId, $typIdName, $sensorName) {
    $pdo = dbConfig::getInstance();
    $valuesDefined = false;
    $mySensorTypId = $pdo->query('SELECT id, name FROM sensorTypes WHERE name = "' . $typIdName . '" LIMIT 1')->fetchObject('sensorTyp');

    $defaultValues['Value1onDashboard'] = $defaultValues['Value2onDashboard'] = $defaultValues['Value3onDashboard'] = $defaultValues['Value4onDashboard'] = 1;

    $defaultValuesPerChannelArray = array();
    $red = "#ff0000";
    $green = "#00ff00";

    // Define Default values (for lora boot monitor):
    if ($sensorName == "GPS") {
      $defaultValuesPerChannel['name'] = "Lat";
      $defaultValuesPerChannel['description'] = "Latitude";
      $defaultValuesPerChannel['channelNr'] = 1;
      $defaultValuesPerChannel['locationOfMeasurement'] = "";
      $defaultValuesPerChannel['GaugeMinValue'] = -90;
      $defaultValuesPerChannel['GaugeMaxValue'] = 90;
      $defaultValuesPerChannel['GaugeRedAreaLowValue'] = -85;
      $defaultValuesPerChannel['GaugeRedAreaLowColor'] = $red;
      $defaultValuesPerChannel['GaugeRedAreaHighValue'] = 85;
      $defaultValuesPerChannel['GaugeRedAreaHighColor'] = $red;
      $defaultValuesPerChannel['GaugeNormalAreaColor'] = $green;
      $defaultValuesPerChannel['onDashboard'] = 0;
      $defaultValuesPerChannel['ChartColor'] = RandomColor::one();
      array_push($defaultValuesPerChannelArray, $defaultValuesPerChannel);

      $defaultValuesPerChannel['name'] = "Lon";
      $defaultValuesPerChannel['description'] = "Longitude";
      $defaultValuesPerChannel['channelNr'] = 2;
      $defaultValuesPerChannel['locationOfMeasurement'] = "";
      $defaultValuesPerChannel['GaugeMinValue'] = -180;
      $defaultValuesPerChannel['GaugeMaxValue'] = 180;
      $defaultValuesPerChannel['GaugeRedAreaLowValue'] = -175;
      $defaultValuesPerChannel['GaugeRedAreaLowColor'] = $red;
      $defaultValuesPerChannel['GaugeRedAreaHighValue'] = 175;
      $defaultValuesPerChannel['GaugeRedAreaHighColor'] = $red;
      $defaultValuesPerChannel['GaugeNormalAreaColor'] = $green;
      $defaultValuesPerChannel['onDashboard'] = 0;
      $defaultValuesPerChannel['ChartColor'] = RandomColor::one();
      array_push($defaultValuesPerChannelArray, $defaultValuesPerChannel);

      $defaultValuesPerChannel['name'] = "Alt";
      $defaultValuesPerChannel['description'] = "Altitude";
      $defaultValuesPerChannel['channelNr'] = 3;
      $defaultValuesPerChannel['locationOfMeasurement'] = "";
      $defaultValuesPerChannel['GaugeMinValue'] = -50;
      $defaultValuesPerChannel['GaugeMaxValue'] = 20;
      $defaultValuesPerChannel['GaugeRedAreaLowValue'] = 0;
      $defaultValuesPerChannel['GaugeRedAreaLowColor'] = $red;
      $defaultValuesPerChannel['GaugeRedAreaHighValue'] = 15;
      $defaultValuesPerChannel['GaugeRedAreaHighColor'] = $red;
      $defaultValuesPerChannel['GaugeNormalAreaColor'] = $green;
      $defaultValuesPerChannel['onDashboard'] = 0;
      $defaultValuesPerChannel['ChartColor'] = RandomColor::one();
      array_push($defaultValuesPerChannelArray, $defaultValuesPerChannel);

      $defaultValuesPerChannel['name'] = "Spd";
      $defaultValuesPerChannel['description'] = "Speed";
      $defaultValuesPerChannel['channelNr'] = 4;
      $defaultValuesPerChannel['locationOfMeasurement'] = "";
      $defaultValuesPerChannel['GaugeMinValue'] = 0;
      $defaultValuesPerChannel['GaugeMaxValue'] = 100;
      $defaultValuesPerChannel['GaugeRedAreaLowValue'] = 0;
      $defaultValuesPerChannel['GaugeRedAreaLowColor'] = $red;
      $defaultValuesPerChannel['GaugeRedAreaHighValue'] = 60;
      $defaultValuesPerChannel['GaugeRedAreaHighColor'] = $red;
      $defaultValuesPerChannel['GaugeNormalAreaColor'] = $green;
      $defaultValuesPerChannel['onDashboard'] = 1;
      $defaultValuesPerChannel['ChartColor'] = RandomColor::one();
      array_push($defaultValuesPerChannelArray, $defaultValuesPerChannel);

      $defaultValues['ttnPayloadId'] = null;
      $defaultValues['NrOfUsedSensors'] = 4;
      $valuesDefined = true;

    } elseif ($sensorName == "Lora") {
      $defaultValuesPerChannel['name'] = "Gateway";
      $defaultValuesPerChannel['description'] = "Gateway";
      $defaultValuesPerChannel['channelNr'] = 1;
      $defaultValuesPerChannel['locationOfMeasurement'] = "";
      $defaultValuesPerChannel['GaugeMinValue'] = 0;
      $defaultValuesPerChannel['GaugeMaxValue'] = 20;
      $defaultValuesPerChannel['GaugeRedAreaLowValue'] = 0;
      $defaultValuesPerChannel['GaugeRedAreaLowColor'] = $red;
      $defaultValuesPerChannel['GaugeRedAreaHighValue'] = 0;
      $defaultValuesPerChannel['GaugeRedAreaHighColor'] = $red;
      $defaultValuesPerChannel['GaugeNormalAreaColor'] = $green;
      $defaultValuesPerChannel['onDashboard'] = 0;
      $defaultValuesPerChannel['ChartColor'] = RandomColor::one();
      array_push($defaultValuesPerChannelArray, $defaultValuesPerChannel);

      $defaultValuesPerChannel['name'] = "RSSI";
      $defaultValuesPerChannel['description'] = "RSSI";
      $defaultValuesPerChannel['channelNr'] = 2;
      $defaultValuesPerChannel['locationOfMeasurement'] = "Received Signal Strength Indicator";
      $defaultValuesPerChannel['GaugeMinValue'] = -130;
      $defaultValuesPerChannel['GaugeMaxValue'] = 10;
      $defaultValuesPerChannel['GaugeRedAreaLowValue'] = -120;
      $defaultValuesPerChannel['GaugeRedAreaLowColor'] = $red;
      $defaultValuesPerChannel['GaugeRedAreaHighValue'] = 0;
      $defaultValuesPerChannel['GaugeRedAreaHighColor'] = $red;
      $defaultValuesPerChannel['GaugeNormalAreaColor'] = $green;
      $defaultValuesPerChannel['onDashboard'] = 1;
      $defaultValuesPerChannel['ChartColor'] = RandomColor::one();
      array_push($defaultValuesPerChannelArray, $defaultValuesPerChannel);

      $defaultValuesPerChannel['name'] = "SNR";
      $defaultValuesPerChannel['description'] = "Signal-to-Noise Ratio";
      $defaultValuesPerChannel['channelNr'] = 3;
      $defaultValuesPerChannel['locationOfMeasurement'] = "";
      $defaultValuesPerChannel['GaugeMinValue'] = -130;
      $defaultValuesPerChannel['GaugeMaxValue'] = 10;
      $defaultValuesPerChannel['GaugeRedAreaLowValue'] = -120;
      $defaultValuesPerChannel['GaugeRedAreaLowColor'] = $red;
      $defaultValuesPerChannel['GaugeRedAreaHighValue'] = 0;
      $defaultValuesPerChannel['GaugeRedAreaHighColor'] = $red;
      $defaultValuesPerChannel['GaugeNormalAreaColor'] = $green;
      $defaultValuesPerChannel['onDashboard'] = 1;
      $defaultValuesPerChannel['ChartColor'] = RandomColor::one();
      array_push($defaultValuesPerChannelArray, $defaultValuesPerChannel);

      $defaultValuesPerChannel['name'] = "Counter";
      $defaultValuesPerChannel['description'] = "Paket counter";
      $defaultValuesPerChannel['channelNr'] = 4;
      $defaultValuesPerChannel['locationOfMeasurement'] = "";
      $defaultValuesPerChannel['GaugeMinValue'] = 0;
      $defaultValuesPerChannel['GaugeMaxValue'] = 2000;
      $defaultValuesPerChannel['GaugeRedAreaLowValue'] = 0;
      $defaultValuesPerChannel['GaugeRedAreaLowColor'] = $red;
      $defaultValuesPerChannel['GaugeRedAreaHighValue'] = 1900;
      $defaultValuesPerChannel['GaugeRedAreaHighColor'] = $red;
      $defaultValuesPerChannel['GaugeNormalAreaColor'] = $green;
      $defaultValuesPerChannel['onDashboard'] = 1;
      $defaultValuesPerChannel['ChartColor'] = RandomColor::one();
      array_push($defaultValuesPerChannelArray, $defaultValuesPerChannel);

      $defaultValues['ttnPayloadId'] = null;
      $defaultValues['NrOfUsedSensors'] = 4;
      $valuesDefined = true;

    } elseif ($sensorName == "ADC") {
      $defaultValuesPerChannel['name'] = "ADC1";
      $defaultValuesPerChannel['description'] = "ADC1";
      $defaultValuesPerChannel['channelNr'] = 1;
      $defaultValuesPerChannel['locationOfMeasurement'] = "";
      $defaultValuesPerChannel['GaugeMinValue'] = 0;
      $defaultValuesPerChannel['GaugeMaxValue'] = 20;
      $defaultValuesPerChannel['GaugeRedAreaLowValue'] = 8;
      $defaultValuesPerChannel['GaugeRedAreaLowColor'] = $red;
      $defaultValuesPerChannel['GaugeRedAreaHighValue'] = 16;
      $defaultValuesPerChannel['GaugeRedAreaHighColor'] = $red;
      $defaultValuesPerChannel['GaugeNormalAreaColor'] = $green;
      $defaultValuesPerChannel['onDashboard'] = 1;
      $defaultValuesPerChannel['ChartColor'] = RandomColor::one();
      array_push($defaultValuesPerChannelArray, $defaultValuesPerChannel);

      $defaultValuesPerChannel['name'] = "ADC2";
      $defaultValuesPerChannel['description'] = "ADC2";
      $defaultValuesPerChannel['channelNr'] = 2;
      $defaultValuesPerChannel['locationOfMeasurement'] = "";
      $defaultValuesPerChannel['GaugeMinValue'] = 0;
      $defaultValuesPerChannel['GaugeMaxValue'] = 20;
      $defaultValuesPerChannel['GaugeRedAreaLowValue'] = 8;
      $defaultValuesPerChannel['GaugeRedAreaLowColor'] = $red;
      $defaultValuesPerChannel['GaugeRedAreaHighValue'] = 16;
      $defaultValuesPerChannel['GaugeRedAreaHighColor'] = $red;
      $defaultValuesPerChannel['GaugeNormalAreaColor'] = $green;
      $defaultValuesPerChannel['onDashboard'] = 1;
      $defaultValuesPerChannel['ChartColor'] = RandomColor::one();
      array_push($defaultValuesPerChannelArray, $defaultValuesPerChannel);

      $defaultValuesPerChannel['name'] = "level1";
      $defaultValuesPerChannel['description'] = "level1";
      $defaultValuesPerChannel['channelNr'] = 3;
      $defaultValuesPerChannel['locationOfMeasurement'] = "";
      $defaultValuesPerChannel['GaugeMinValue'] = 0;
      $defaultValuesPerChannel['GaugeMaxValue'] = 20;
      $defaultValuesPerChannel['GaugeRedAreaLowValue'] = 8;
      $defaultValuesPerChannel['GaugeRedAreaLowColor'] = $red;
      $defaultValuesPerChannel['GaugeRedAreaHighValue'] = 16;
      $defaultValuesPerChannel['GaugeRedAreaHighColor'] = $red;
      $defaultValuesPerChannel['GaugeNormalAreaColor'] = $green;
      $defaultValuesPerChannel['onDashboard'] = 1;
      $defaultValuesPerChannel['ChartColor'] = RandomColor::one();
      array_push($defaultValuesPerChannelArray, $defaultValuesPerChannel);

      $defaultValuesPerChannel['name'] = "level2";
      $defaultValuesPerChannel['description'] = "level2";
      $defaultValuesPerChannel['channelNr'] = 4;
      $defaultValuesPerChannel['locationOfMeasurement'] = "";
      $defaultValuesPerChannel['GaugeMinValue'] = 0;
      $defaultValuesPerChannel['GaugeMaxValue'] = 20;
      $defaultValuesPerChannel['GaugeRedAreaLowValue'] = 8;
      $defaultValuesPerChannel['GaugeRedAreaLowColor'] = $red;
      $defaultValuesPerChannel['GaugeRedAreaHighValue'] = 16;
      $defaultValuesPerChannel['GaugeRedAreaHighColor'] = $red;
      $defaultValuesPerChannel['GaugeNormalAreaColor'] = $green;
      $defaultValuesPerChannel['onDashboard'] = 1;
      $defaultValuesPerChannel['ChartColor'] = RandomColor::one();
      array_push($defaultValuesPerChannelArray, $defaultValuesPerChannel);

      $defaultValues['ttnPayloadId'] = null;
      $defaultValues['NrOfUsedSensors'] = 4;
      $valuesDefined = true;

    } elseif ($sensorName == "DS18B20") {
      $defaultValuesPerChannel['name'] = "Ch1";
      $defaultValuesPerChannel['description'] = "Ch1";
      $defaultValuesPerChannel['channelNr'] = 1;
      $defaultValuesPerChannel['locationOfMeasurement'] = "";
      $defaultValuesPerChannel['GaugeMinValue'] = 0;
      $defaultValuesPerChannel['GaugeMaxValue'] = 80;
      $defaultValuesPerChannel['GaugeRedAreaLowValue'] = 10;
      $defaultValuesPerChannel['GaugeRedAreaLowColor'] = $red;
      $defaultValuesPerChannel['GaugeRedAreaHighValue'] = 70;
      $defaultValuesPerChannel['GaugeRedAreaHighColor'] = $red;
      $defaultValuesPerChannel['GaugeNormalAreaColor'] = $green;
      $defaultValuesPerChannel['onDashboard'] = 1;
      $defaultValuesPerChannel['ChartColor'] = RandomColor::one();
      array_push($defaultValuesPerChannelArray, $defaultValuesPerChannel);

      $defaultValuesPerChannel['name'] = "Ch2";
      $defaultValuesPerChannel['description'] = "Ch2";
      $defaultValuesPerChannel['channelNr'] = 2;
      $defaultValuesPerChannel['locationOfMeasurement'] = "";
      $defaultValuesPerChannel['GaugeMinValue'] = 0;
      $defaultValuesPerChannel['GaugeMaxValue'] = 80;
      $defaultValuesPerChannel['GaugeRedAreaLowValue'] = 10;
      $defaultValuesPerChannel['GaugeRedAreaLowColor'] = $red;
      $defaultValuesPerChannel['GaugeRedAreaHighValue'] = 70;
      $defaultValuesPerChannel['GaugeRedAreaHighColor'] = $red;
      $defaultValuesPerChannel['GaugeNormalAreaColor'] = $green;
      $defaultValuesPerChannel['onDashboard'] = 1;
      $defaultValuesPerChannel['ChartColor'] = RandomColor::one();
      array_push($defaultValuesPerChannelArray, $defaultValuesPerChannel);

      $defaultValuesPerChannel['name'] = "Ch3";
      $defaultValuesPerChannel['description'] = "Ch3";
      $defaultValuesPerChannel['channelNr'] = 3;
      $defaultValuesPerChannel['locationOfMeasurement'] = "";
      $defaultValuesPerChannel['GaugeMinValue'] = 0;
      $defaultValuesPerChannel['GaugeMaxValue'] = 80;
      $defaultValuesPerChannel['GaugeRedAreaLowValue'] = 10;
      $defaultValuesPerChannel['GaugeRedAreaLowColor'] = $red;
      $defaultValuesPerChannel['GaugeRedAreaHighValue'] = 70;
      $defaultValuesPerChannel['GaugeRedAreaHighColor'] = $red;
      $defaultValuesPerChannel['GaugeNormalAreaColor'] = $green;
      $defaultValuesPerChannel['onDashboard'] = 1;
      $defaultValuesPerChannel['ChartColor'] = RandomColor::one();
      array_push($defaultValuesPerChannelArray, $defaultValuesPerChannel);

      $defaultValuesPerChannel['name'] = "Ch4";
      $defaultValuesPerChannel['description'] = "Ch4";
      $defaultValuesPerChannel['channelNr'] = 4;
      $defaultValuesPerChannel['locationOfMeasurement'] = "";
      $defaultValuesPerChannel['GaugeMinValue'] = 0;
      $defaultValuesPerChannel['GaugeMaxValue'] = 80;
      $defaultValuesPerChannel['GaugeRedAreaLowValue'] = 10;
      $defaultValuesPerChannel['GaugeRedAreaLowColor'] = $red;
      $defaultValuesPerChannel['GaugeRedAreaHighValue'] = 70;
      $defaultValuesPerChannel['GaugeRedAreaHighColor'] = $red;
      $defaultValuesPerChannel['GaugeNormalAreaColor'] = $green;
      $defaultValuesPerChannel['onDashboard'] = 1;
      $defaultValuesPerChannel['ChartColor'] = RandomColor::one();
      array_push($defaultValuesPerChannelArray, $defaultValuesPerChannel);

      $defaultValues['ttnPayloadId'] = null;
      $defaultValues['NrOfUsedSensors'] = 4;
      $valuesDefined = true;

    } elseif ($sensorName == "BME280") {
      $defaultValuesPerChannel['name'] = "Temp";
      $defaultValuesPerChannel['description'] = "Temp";
      $defaultValuesPerChannel['channelNr'] = 1;
      $defaultValuesPerChannel['locationOfMeasurement'] = "";
      $defaultValuesPerChannel['GaugeMinValue'] = 0;
      $defaultValuesPerChannel['GaugeMaxValue'] = 40;
      $defaultValuesPerChannel['GaugeRedAreaLowValue'] = 10;
      $defaultValuesPerChannel['GaugeRedAreaLowColor'] = $red;
      $defaultValuesPerChannel['GaugeRedAreaHighValue'] = 26;
      $defaultValuesPerChannel['GaugeRedAreaHighColor'] = $red;
      $defaultValuesPerChannel['GaugeNormalAreaColor'] = $green;
      $defaultValuesPerChannel['onDashboard'] = 1;
      $defaultValuesPerChannel['ChartColor'] = RandomColor::one();
      array_push($defaultValuesPerChannelArray, $defaultValuesPerChannel);

      $defaultValuesPerChannel['name'] = "Hum";
      $defaultValuesPerChannel['description'] = "Hum";
      $defaultValuesPerChannel['channelNr'] = 2;
      $defaultValuesPerChannel['locationOfMeasurement'] = "";
      $defaultValuesPerChannel['GaugeMinValue'] = 0;
      $defaultValuesPerChannel['GaugeMaxValue'] = 100;
      $defaultValuesPerChannel['GaugeRedAreaLowValue'] = 40;
      $defaultValuesPerChannel['GaugeRedAreaLowColor'] = $red;
      $defaultValuesPerChannel['GaugeRedAreaHighValue'] = 60;
      $defaultValuesPerChannel['GaugeRedAreaHighColor'] = $red;
      $defaultValuesPerChannel['GaugeNormalAreaColor'] = $green;
      $defaultValuesPerChannel['onDashboard'] = 1;
      $defaultValuesPerChannel['ChartColor'] = RandomColor::one();
      array_push($defaultValuesPerChannelArray, $defaultValuesPerChannel);

      $defaultValuesPerChannel['name'] = "Pres";
      $defaultValuesPerChannel['description'] = "Pres";
      $defaultValuesPerChannel['channelNr'] = 3;
      $defaultValuesPerChannel['locationOfMeasurement'] = "";
      $defaultValuesPerChannel['GaugeMinValue'] = 0;
      $defaultValuesPerChannel['GaugeMaxValue'] = 3000;
      $defaultValuesPerChannel['GaugeRedAreaLowValue'] = 1000;
      $defaultValuesPerChannel['GaugeRedAreaLowColor'] = $red;
      $defaultValuesPerChannel['GaugeRedAreaHighValue'] = 1026;
      $defaultValuesPerChannel['GaugeRedAreaHighColor'] = $red;
      $defaultValuesPerChannel['GaugeNormalAreaColor'] = $green;
      $defaultValuesPerChannel['onDashboard'] = 1;
      $defaultValuesPerChannel['ChartColor'] = RandomColor::one();
      array_push($defaultValuesPerChannelArray, $defaultValuesPerChannel);

      $defaultValuesPerChannel['name'] = "Dew.";
      $defaultValuesPerChannel['description'] = "Dew.";
      $defaultValuesPerChannel['channelNr'] = 4;
      $defaultValuesPerChannel['locationOfMeasurement'] = "";
      $defaultValuesPerChannel['GaugeMinValue'] = 0;
      $defaultValuesPerChannel['GaugeMaxValue'] = 30;
      $defaultValuesPerChannel['GaugeRedAreaLowValue'] = 12;
      $defaultValuesPerChannel['GaugeRedAreaLowColor'] = $red;
      $defaultValuesPerChannel['GaugeRedAreaHighValue'] = 20;
      $defaultValuesPerChannel['GaugeRedAreaHighColor'] = $red;
      $defaultValuesPerChannel['GaugeNormalAreaColor'] = $green;
      $defaultValuesPerChannel['onDashboard'] = 1;
      $defaultValuesPerChannel['ChartColor'] = RandomColor::one();
      array_push($defaultValuesPerChannelArray, $defaultValuesPerChannel);

      $defaultValues['ttnPayloadId'] = null;
      $defaultValues['NrOfUsedSensors'] = 4;
      $valuesDefined = true;

    } elseif ($sensorName == "DS2438") {
      $defaultValuesPerChannel['name'] = "V";
      $defaultValuesPerChannel['description'] = "Voltage";
      $defaultValuesPerChannel['channelNr'] = 1;
      $defaultValuesPerChannel['locationOfMeasurement'] = "";
      $defaultValuesPerChannel['GaugeMinValue'] = 0;
      $defaultValuesPerChannel['GaugeMaxValue'] = 20;
      $defaultValuesPerChannel['GaugeRedAreaLowValue'] = 8;
      $defaultValuesPerChannel['GaugeRedAreaLowColor'] = $red;
      $defaultValuesPerChannel['GaugeRedAreaHighValue'] = 16;
      $defaultValuesPerChannel['GaugeRedAreaHighColor'] = $red;
      $defaultValuesPerChannel['GaugeNormalAreaColor'] = $green;
      $defaultValuesPerChannel['onDashboard'] = 1;
      $defaultValuesPerChannel['ChartColor'] = RandomColor::one();
      array_push($defaultValuesPerChannelArray, $defaultValuesPerChannel);

      $defaultValuesPerChannel['name'] = "A";
      $defaultValuesPerChannel['description'] = "Ampere";
      $defaultValuesPerChannel['channelNr'] = 2;
      $defaultValuesPerChannel['locationOfMeasurement'] = "";
      $defaultValuesPerChannel['GaugeMinValue'] = 0;
      $defaultValuesPerChannel['GaugeMaxValue'] = 20;
      $defaultValuesPerChannel['GaugeRedAreaLowValue'] = 0;
      $defaultValuesPerChannel['GaugeRedAreaLowColor'] = $red;
      $defaultValuesPerChannel['GaugeRedAreaHighValue'] = 16;
      $defaultValuesPerChannel['GaugeRedAreaHighColor'] = $red;
      $defaultValuesPerChannel['GaugeNormalAreaColor'] = $green;
      $defaultValuesPerChannel['onDashboard'] = 1;
      $defaultValuesPerChannel['ChartColor'] = RandomColor::one();
      array_push($defaultValuesPerChannelArray, $defaultValuesPerChannel);

      $defaultValuesPerChannel['name'] = null;
      $defaultValuesPerChannel['description'] = "";
      $defaultValuesPerChannel['channelNr'] = 3;
      $defaultValuesPerChannel['locationOfMeasurement'] = "";
      $defaultValuesPerChannel['GaugeMinValue'] = 0;
      $defaultValuesPerChannel['GaugeMaxValue'] = 20;
      $defaultValuesPerChannel['GaugeRedAreaLowValue'] = 0;
      $defaultValuesPerChannel['GaugeRedAreaLowColor'] = $red;
      $defaultValuesPerChannel['GaugeRedAreaHighValue'] = 0;
      $defaultValuesPerChannel['GaugeRedAreaHighColor'] = $red;
      $defaultValuesPerChannel['GaugeNormalAreaColor'] = $green;
      $defaultValuesPerChannel['onDashboard'] = 0;
      $defaultValuesPerChannel['ChartColor'] = RandomColor::one();
      array_push($defaultValuesPerChannelArray, $defaultValuesPerChannel);

      $defaultValuesPerChannel['name'] = null;
      $defaultValuesPerChannel['description'] = "";
      $defaultValuesPerChannel['channelNr'] = 4;
      $defaultValuesPerChannel['locationOfMeasurement'] = "";
      $defaultValuesPerChannel['GaugeMinValue'] = 0;
      $defaultValuesPerChannel['GaugeMaxValue'] = 20;
      $defaultValuesPerChannel['GaugeRedAreaLowValue'] = 0;
      $defaultValuesPerChannel['GaugeRedAreaLowColor'] = $red;
      $defaultValuesPerChannel['GaugeRedAreaHighValue'] = 0;
      $defaultValuesPerChannel['GaugeRedAreaHighColor'] = $red;
      $defaultValuesPerChannel['GaugeNormalAreaColor'] = $green;
      $defaultValuesPerChannel['onDashboard'] = 0;
      $defaultValuesPerChannel['ChartColor'] = RandomColor::one();
      array_push($defaultValuesPerChannelArray, $defaultValuesPerChannel);

      $defaultValues['ttnPayloadId'] = null;
      $defaultValues['NrOfUsedSensors'] = 2;
      $valuesDefined = true;
      
    } elseif ($sensorName == "Digital") {
      $defaultValuesPerChannel['name'] = "Ch1";
      $defaultValuesPerChannel['description'] = "Ch1";
      $defaultValuesPerChannel['channelNr'] = 1;
      $defaultValuesPerChannel['locationOfMeasurement'] = "";
      $defaultValuesPerChannel['GaugeMinValue'] = 0;
      $defaultValuesPerChannel['GaugeMaxValue'] = 1;
      $defaultValuesPerChannel['GaugeRedAreaLowValue'] = 0;
      $defaultValuesPerChannel['GaugeRedAreaLowColor'] = $red;
      $defaultValuesPerChannel['GaugeRedAreaHighValue'] = 2;
      $defaultValuesPerChannel['GaugeRedAreaHighColor'] = $red;
      $defaultValuesPerChannel['GaugeNormalAreaColor'] = $green;
      $defaultValuesPerChannel['onDashboard'] = 1;
      $defaultValuesPerChannel['ChartColor'] = RandomColor::one();
      array_push($defaultValuesPerChannelArray, $defaultValuesPerChannel);

      $defaultValuesPerChannel['name'] = "Ch2";
      $defaultValuesPerChannel['description'] = "Ch2";
      $defaultValuesPerChannel['channelNr'] = 2;
      $defaultValuesPerChannel['locationOfMeasurement'] = "";
      $defaultValuesPerChannel['GaugeMinValue'] = 0;
      $defaultValuesPerChannel['GaugeMaxValue'] = 1;
      $defaultValuesPerChannel['GaugeRedAreaLowValue'] = 0;
      $defaultValuesPerChannel['GaugeRedAreaLowColor'] = $red;
      $defaultValuesPerChannel['GaugeRedAreaHighValue'] = 2;
      $defaultValuesPerChannel['GaugeRedAreaHighColor'] = $red;
      $defaultValuesPerChannel['GaugeNormalAreaColor'] = $green;
      $defaultValuesPerChannel['onDashboard'] = 1;
      $defaultValuesPerChannel['ChartColor'] = RandomColor::one();
      array_push($defaultValuesPerChannelArray, $defaultValuesPerChannel);

      $defaultValuesPerChannel['name'] = null;
      $defaultValuesPerChannel['description'] = "";
      $defaultValuesPerChannel['channelNr'] = 3;
      $defaultValuesPerChannel['locationOfMeasurement'] = "";
      $defaultValuesPerChannel['GaugeMinValue'] = 0;
      $defaultValuesPerChannel['GaugeMaxValue'] = 1;
      $defaultValuesPerChannel['GaugeRedAreaLowValue'] = 0;
      $defaultValuesPerChannel['GaugeRedAreaLowColor'] = $red;
      $defaultValuesPerChannel['GaugeRedAreaHighValue'] = 2;
      $defaultValuesPerChannel['GaugeRedAreaHighColor'] = $red;
      $defaultValuesPerChannel['GaugeNormalAreaColor'] = $green;
      $defaultValuesPerChannel['onDashboard'] = 1;
      $defaultValuesPerChannel['ChartColor'] = RandomColor::one();
      array_push($defaultValuesPerChannelArray, $defaultValuesPerChannel);

      $defaultValuesPerChannel['name'] = null;
      $defaultValuesPerChannel['description'] = "";
      $defaultValuesPerChannel['channelNr'] = 4;
      $defaultValuesPerChannel['locationOfMeasurement'] = "";
      $defaultValuesPerChannel['GaugeMinValue'] = 0;
      $defaultValuesPerChannel['GaugeMaxValue'] = 1;
      $defaultValuesPerChannel['GaugeRedAreaLowValue'] = 0;
      $defaultValuesPerChannel['GaugeRedAreaLowColor'] = $red;
      $defaultValuesPerChannel['GaugeRedAreaHighValue'] = 2;
      $defaultValuesPerChannel['GaugeRedAreaHighColor'] = $red;
      $defaultValuesPerChannel['GaugeNormalAreaColor'] = $green;
      $defaultValuesPerChannel['onDashboard'] = 1;
      $defaultValuesPerChannel['ChartColor'] = RandomColor::one();
      array_push($defaultValuesPerChannelArray, $defaultValuesPerChannel);

      $defaultValues['ttnPayloadId'] = null;
      $defaultValues['NrOfUsedSensors'] = 2;
      $valuesDefined = true;
    }

    if ($valuesDefined == true) {
      try {
        $statement = $pdo->prepare("INSERT INTO sensorConfig (boardId, typId, name," .
        "NrOfUsedSensors, onDashboard ) VALUES (?, ?, ?, ?, ?)");
        $statement->execute(array($boardId, $mySensorTypId->id, $sensorName, $defaultValues['NrOfUsedSensors'], 1));
        $neue_id = $pdo->lastInsertId();
      } catch (PDOException $e) {
        writeToLogFunction::write_to_log("Error: Sensor config not updated successfully.", $_SERVER["SCRIPT_FILENAME"]);
        writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
        throw new Exception('Sensor not updated successfully.');
      }

      foreach($defaultValuesPerChannelArray as $defaultValuesPerChannelSingle) {
        try {
          $statement2 = $pdo->prepare("INSERT INTO sensorChannelConfig  SET sensorConfigId=?, name=?, description=?, channelNr=?, locationOfMeasurement=?, GaugeMinValue=?, GaugeMaxValue=?,  GaugeRedAreaLowValue =?, GaugeRedAreaLowColor=?, GaugeRedAreaHighValue=?, GaugeRedAreaHighColor=?, GaugeNormalAreaColor=?, onDashboard=?, ChartColor=?");
          $statement2->execute(array($neue_id, $defaultValuesPerChannelSingle['name'], $defaultValuesPerChannelSingle['description'], $defaultValuesPerChannelSingle['channelNr'], $defaultValuesPerChannelSingle['locationOfMeasurement'],  $defaultValuesPerChannelSingle['GaugeMinValue'], $defaultValuesPerChannelSingle['GaugeMaxValue'],  $defaultValuesPerChannelSingle['GaugeRedAreaLowValue'], $defaultValuesPerChannelSingle['GaugeRedAreaLowColor'], $defaultValuesPerChannelSingle['GaugeRedAreaHighValue'], $defaultValuesPerChannelSingle['GaugeRedAreaHighColor'], $defaultValuesPerChannelSingle['GaugeNormalAreaColor'], $defaultValuesPerChannelSingle['onDashboard'], $defaultValuesPerChannelSingle['ChartColor']));
        } catch (PDOException $e) {
          writeToLogFunction::write_to_log("Error: sensorChannelConfig not updated successfully.", $_SERVER["SCRIPT_FILENAME"]);
          writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
          throw new Exception('sensorChannelConfig not updated successfully.');
        }
      }
      return $neue_id;
    }
  }

  /*
  * Get Sensor type object of a given sensor sensorTypId id.
  */
  public static function getSensorType($sensorTypId) {
    $pdo = dbConfig::getInstance();
    $sensortyps = $pdo->prepare("SELECT * FROM sensorTypes WHERE id = ? ORDER BY id LIMIT 1");
    $sensortyps->execute(array($sensorTypId));
    $SensorData2 = $sensortyps->fetch(PDO::FETCH_ASSOC);
    return $SensorData2;
  }

  /*
  * Get all Sensor type object of a given sensor sensorTypId id.
  */
  public static function getAllSensorType() {
    $pdo = dbConfig::getInstance();
    $sensortyps = $pdo->prepare("SELECT * FROM sensorTypes ORDER BY id");
    $sensortyps->execute();
    $SensorData2 = $sensortyps->fetchAll(PDO::FETCH_ASSOC);
    return $SensorData2;
  }

  /*
  * Get all Users from db.
  */
  public static function getAllUsers() {
    $pdo = dbConfig::getInstance();
    $statement = $pdo->prepare("SELECT * FROM users ORDER BY id");
    $result = $statement->execute();
    return $statement->fetchAll();
  }

  /*
  * Get all Users from db.
  */
  public static function getAllUsersWithReceiveNotifications() {
    $pdo = dbConfig::getInstance();
    $statement = $pdo->prepare("SELECT * FROM users WHERE receive_notifications = 1 ORDER BY id");
    $result = $statement->execute();
    return $statement->fetchAll();
  }

  /**
   * get if User is Already notified
   * 
   */
  public static function getAlreadyNotified($boardId) {
    $pdo = dbConfig::getInstance();
    $result = null;
    $statement = $pdo->prepare("SELECT alreadyNotified FROM boardConfig WHERE id =?");
    $pdoResult = $statement->execute(array($boardId));
    return $pdoResult;
  }

  /**
   * set if User is Already notified
   * 
   */
  public static function setAlreadyNotified($boardId) {
    $pdo = dbConfig::getInstance();
    $result = null;
    try {
      $statement = $pdo->prepare("UPDATE boardConfig SET alreadyNotified = 1 WHERE id =?");
      $pdoResult = $statement->execute(array($boardId));
      $changedRows = $statement->rowCount();
      if($changedRows == 1 ) {
        return true;
      } else {
        return false;
      }
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: Board notified Status in DB not successfully updated for boardId: " . $boardId, $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      throw new Exception('Board notified Status in DB not successfully updated.');
    }
  }

  /**
   * unset if User is Already notified
   * 
   */
  public static function unsetAlreadyNotified($boardId) {
    $pdo = dbConfig::getInstance();
    $result = null;
    try {
      $statement = $pdo->prepare("UPDATE boardConfig SET alreadyNotified = 0 WHERE id =?");
      $pdoResult = $statement->execute(array($boardId));
      $changedRows = $statement->rowCount();
      if($changedRows == 1 ) {
        return true;
      } else {
        return false;
      }
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: Board notified Status in DB not successfully updated for boardId: " . $boardId, $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      throw new Exception('Board notified Status in DB not successfully updated.');
    }
  }

  /*
  * Get all Users from db.
  */
  public static function isUserRegistered($email) {
    $pdo = dbConfig::getInstance();
    $statement = $pdo->prepare("SELECT * FROM users WHERE email = ? ORDER BY id LIMIT 1");
    $statement->execute(array($email));
    $statement2 = $statement->fetch(PDO::FETCH_ASSOC);
    if ($statement2 != NULL) {
      $result = true;
    } else {
      $result = false;
    }
    return $result;
  }

  /*
   * Get all offline Boards, that needs to notify the owner.
   */
  public static function getAllOfflineBoardsToNotify() {
    $pdo = dbConfig::getInstance();
    $sensortyps = $pdo->prepare("SELECT boardConfig.*, users.email FROM boardConfig, users WHERE offlineDataTimer != 0 && alreadyNotified = 0 && ownerUserId = users.id");
    $sensortyps->execute();
    $SensorData2 = $sensortyps->fetchAll(PDO::FETCH_ASSOC);
    return $SensorData2;
  }

	/**
	 * Outputs an error message and stops the further execution of the script.
	 */
	public function error($error_msg) {
		include("common/header.inc.php");
		include("common/error.inc.php");
		include("common/footer.inc.php");
    writeToLogFunction::write_to_log("Error: function error was triggered.", $_SERVER["SCRIPT_FILENAME"]);
		exit();
	}
}
