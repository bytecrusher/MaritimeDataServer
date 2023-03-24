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

  class sensorTyp
  {
    public $id;
    public $name;
  }

class myFunctions {

	/**
	 * Returns true when the user is checked in, else false
	 */
	public static function is_checked_in() {
		return isset($_SESSION['userid']);
	}

	/**
	 * Returns a random string
	 */
	public static function random_string() {
		if(function_exists('openssl_random_pseudo_bytes')) {
			$bytes = openssl_random_pseudo_bytes(16);
			$str = bin2hex($bytes);
		} else if(function_exists('mcrypt_create_iv')) {
			$bytes = mcrypt_create_iv(16, MCRYPT_DEV_URANDOM);
			$str = bin2hex($bytes);
		} else {
			$str = md5(uniqid(configuration::$md5secretstring, true));
		}
		return $str;
	}

	/**
	 * Returns the URL to the site without the script name
	 */
	public static function getSiteURL() {
		$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
		return $protocol.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/';
	}

  /*
  * Get all of my Board by user id.
  */
  public static function getMyBoards($userid) {
    if (!$userid == null) {
      $pdo = dbConfig::getInstance();
      $myboards = $pdo->prepare("SELECT * FROM boardconfig WHERE owner_userid = " . $userid . " ORDER BY id");
      $result = $myboards->execute();
      $myboards2 = $myboards->fetchAll(PDO::FETCH_ASSOC);
      return $myboards2;
    }
  }

  /*
  * Get Board by Board id. Only one dataset will return.
  */
  public static function getBoardById($boardId) {
    if (!$boardId == null) {
      $pdo = dbConfig::getInstance();
      $myboards = $pdo->prepare("SELECT * FROM boardconfig WHERE id = " . $boardId . " ORDER BY id LIMIT 1");
      $result = $myboards->execute();
      $myboards2 = $myboards->fetch(PDO::FETCH_ASSOC);
      return $myboards2;
    }
  }

  /*
  * Get Board by Board macaddress. Only one dataset will return.
  */
  public static function getBoardByMac($boardMac) {
    if (!$boardMac == null) {
      $pdo = dbConfig::getInstance();
      $myboards = $pdo->prepare("SELECT * FROM boardconfig WHERE macaddress = '" . $boardMac . "' ORDER BY id LIMIT 1");
      $result = $myboards->execute();
      $myboards2 = $myboards->fetch(PDO::FETCH_ASSOC);
      return $myboards2;
    }
  }

/*
  * Get Board by Board TTN appid and devid. Only one dataset will return.
  */
  public static function getBoardByTTN($ttn_app_id, $ttn_dev_id) {
    if ((!$ttn_app_id == null) && (!$ttn_dev_id == null)) {
      $pdo = dbConfig::getInstance();
      $myboards = $pdo->prepare("SELECT * FROM boardconfig WHERE ttn_app_id = '$ttn_app_id' AND ttn_dev_id = '$ttn_dev_id' ORDER BY id LIMIT 1");
      //var_dump($myboards);
      $result = $myboards->execute();
      $myboards2 = $myboards->fetch(PDO::FETCH_ASSOC);
      return $myboards2;
    }
  }

  /*
  * Add Board by Board TTN appid and devid. Only one dataset will return.
  */
  public static function addBoardByTTN($ttn_app_id, $ttn_dev_id) {
    $pdo = dbConfig::getInstance();
    //$statement = $pdo->prepare("INSERT INTO boardconfig (macaddress, owner_userid, name, ttn_app_id, ttn_dev_id) VALUES (?, ?, ?, ?, ?)");
    $statement = $pdo->prepare("INSERT INTO boardconfig (macaddress, name, ttn_app_id, ttn_dev_id, onDashboard, updateDataTimer  ) VALUES (?, ?, ?, ?, ?, ?)");
    //$statement->execute(array("fakeMacAddress" . $ttn_dev_id, 1, "- new imported -", $ttn_app_id, $ttn_dev_id));     // ToDo: change default Owner User to one of the admins
    $statement->execute(array("fakeMacAddress" . $ttn_dev_id, "- new imported -", $ttn_app_id, $ttn_dev_id, 1, 15));
    $neue_id = $pdo->lastInsertId();
    return $neue_id;
  }

  /*
  * Get all sensors of a given board id.
  */
  public static function getAllSensorsOfBoard($id) {
    $pdo = dbConfig::getInstance();
    $mysensors2 = $pdo->prepare("SELECT sensorconfig.*, sensortypes.name as boardid FROM sensorconfig, sensortypes WHERE (boardid = ?) and (typid = sensortypes.id) ORDER BY sensorconfig.id; ");
    $mysensors2->execute(array($id));
    $sensorsOfBoard = $mysensors2->fetchAll(PDO::FETCH_ASSOC);
    return $sensorsOfBoard;
  }

  /*
  * Get all sensors of a given board id.
  */
  public static function getAllSensorsOfBoardold($id) {
    $pdo = dbConfig::getInstance();
    $mysensors2 = $pdo->prepare("SELECT * FROM sensorconfig WHERE boardid = ? ORDER BY id");
    $mysensors2->execute(array($id));
    $sensorsOfBoard = $mysensors2->fetchAll(PDO::FETCH_ASSOC);
    return $sensorsOfBoard;
  }

  /*
  * Get all sensors of a given board id with dashboard and sensor typ 1 (temp).
  */
  public static function getAllSensorsOfBoardWithDashboardAndTemp($id) {
    $pdo = dbConfig::getInstance();
    $mysensors2 = $pdo->prepare("SELECT * FROM sensorconfig WHERE boardid = ? AND onDashboard = 1	AND typid = 1 ORDER BY id");
    $mysensors2->execute(array($id));
    $sensorsOfBoard = $mysensors2->fetchAll(PDO::FETCH_ASSOC);
    return $sensorsOfBoard;
  }

/*
  * Get all sensors of a given board id with dashboard.
  */
  public static function getAllSensorsOfBoardWithDashboard($id) {
    $pdo = dbConfig::getInstance();
    $mysensors2 = $pdo->prepare("SELECT * FROM sensorconfig WHERE boardid = ? AND onDashboard = 1 ORDER BY id");
    $mysensors2->execute(array($id));
    $sensorsOfBoard = $mysensors2->fetchAll(PDO::FETCH_ASSOC);
    return $sensorsOfBoard;
  }

  /*
  * Get all sensors of a given board id with dashboard.
  */
  public static function getAllSensorsOfBoardWithDashboardWithTypeName($id) {
    $pdo = dbConfig::getInstance();
    $mysensors2 = $pdo->prepare("SELECT sensorconfig.*, sensortypes.name as typename FROM sensorconfig, sensortypes WHERE boardid = ? AND typid = sensortypes.id AND onDashboard = 1 ORDER BY id");
    $mysensors2->execute(array($id));
    $sensorsOfBoard = $mysensors2->fetchAll(PDO::FETCH_ASSOC);
    return $sensorsOfBoard;
  }

  /*
  * Get the lastet sensor data of a given sensor id.
  */
  public static function getLatestSensorData($sensorId, $maxNrOfValue = 1) {
    if ($maxNrOfValue >= 1) {
      $pdo = dbConfig::getInstance();
      $mysensors = $pdo->prepare("SELECT * FROM sensordata WHERE sensorid = ? ORDER BY id DESC LIMIT $maxNrOfValue");
      $mysensors->execute(array($sensorId));
      $SensorData = $mysensors->fetchAll(PDO::FETCH_ASSOC);
      return $SensorData;
    }
  }

  /*
  * Get all GPS data of a given (sensor id).
  */
  public static function getAllGpsData($boardid, $maxNrOfValue = 1) {
    if ($maxNrOfValue >= 1) {
      $pdo = dbConfig::getInstance();
      $mysensors = $pdo->prepare("SELECT sensorconfig.*, sensortypes.name as typename FROM `sensorconfig`, sensortypes WHERE boardid = ? AND typid = sensortypes.id AND sensortypes.name = 'GPS'");
      $mysensors->execute(array($boardid));
      $SensorData = $mysensors->fetch(PDO::FETCH_ASSOC);
      if ($SensorData != false) {
        $myGps = $pdo->prepare("SELECT * FROM `sensordata` WHERE sensorid = ?");
        $myGps->execute(array($SensorData["id"]));
        $myGpsData = $myGps->fetchAll(PDO::FETCH_ASSOC);
        return $myGpsData;
      } else {
        return 0;
      }
    }
  }

  /*
  * Get Config Object if a given sensorconfig id
  */
  public static function getSensorConfig($id) {
    $pdo = dbConfig::getInstance();
    $mysensors2 = $pdo->prepare("SELECT * FROM sensorconfig WHERE id = ? ORDER BY id LIMIT 1");
    $mysensors2->execute(array($id));
    $sensorsOfBoard = $mysensors2->fetch(PDO::FETCH_ASSOC);
    return $sensorsOfBoard;
  }

  /*
  * Add SensorConfig Object if a given board id
  */
  public function addSensorConfig($boardid, $typIdName, $sensorName) {
    $pdo = dbConfig::getInstance();
    $valuesDefined = false;
    $mysensorTypId = $pdo->query('SELECT id, name FROM sensortypes WHERE name = "' . $typIdName . '" LIMIT 1')->fetchObject('sensorTyp');

    $defaultValues['Value1onDashboard'] = $defaultValues['Value2onDashboard'] = $defaultValues['Value3onDashboard'] = $defaultValues['Value4onDashboard'] = 1;

    // Define Default values (for lora boot monitor):
    if ($sensorName == "GPS") {
      $defaultValues['nameValue1'] = "Lat";
      $defaultValues['Value1GaugeMinValue'] = 0;
      $defaultValues['Value1GaugeMaxValue'] = 20;
      $defaultValues['Value1GaugeRedAreaLowValue'] = 0;
      $defaultValues['Value1GaugeRedAreaLowColor'] = "red";
      $defaultValues['Value1GaugeRedAreaHighValue'] = 0;
      $defaultValues['Value1GaugeRedAreaHighColor'] = "red";
      $defaultValues['Value1GaugeNormalAreaColor'] = "green";
      $defaultValues['Value1onDashboard'] = 0;

      $defaultValues['nameValue2'] = "Lon";
      $defaultValues['Value2GaugeMinValue'] = 0;
      $defaultValues['Value2GaugeMaxValue'] = 20;
      $defaultValues['Value2GaugeRedAreaLowValue'] = 0;
      $defaultValues['Value2GaugeRedAreaLowColor'] = "red";
      $defaultValues['Value2GaugeRedAreaHighValue'] = 0;
      $defaultValues['Value2GaugeRedAreaHighColor'] = "red";
      $defaultValues['Value2GaugeNormalAreaColor'] = "green";
      $defaultValues['Value2onDashboard'] = 0;

      $defaultValues['nameValue3'] = "Alt";
      $defaultValues['Value3GaugeMinValue'] = 0;
      $defaultValues['Value3GaugeMaxValue'] = 20;
      $defaultValues['Value3GaugeRedAreaLowValue'] = 0;
      $defaultValues['Value3GaugeRedAreaLowColor'] = "red";
      $defaultValues['Value3GaugeRedAreaHighValue'] = 0;
      $defaultValues['Value3GaugeRedAreaHighColor'] = "red";
      $defaultValues['Value3GaugeNormalAreaColor'] = "green";
      $defaultValues['Value3onDashboard'] = 0;

      $defaultValues['nameValue4'] = "Spd";
      $defaultValues['Value4GaugeMinValue'] = 0;
      $defaultValues['Value4GaugeMaxValue'] = 20;
      $defaultValues['Value4GaugeRedAreaLowValue'] = 0;
      $defaultValues['Value4GaugeRedAreaLowColor'] = "red";
      $defaultValues['Value4GaugeRedAreaHighValue'] = 0;
      $defaultValues['Value4GaugeRedAreaHighColor'] = "red";
      $defaultValues['Value4GaugeNormalAreaColor'] = "green";

      $defaultValues['ttn_payload_id'] = null;
      $defaultValues['NrOfUsedSensors'] = 4;
      $valuesDefined = true;

    } elseif ($sensorName == "Lora") {
      $defaultValues['nameValue1'] = "Gateway";
      $defaultValues['Value1GaugeMinValue'] = 0;
      $defaultValues['Value1GaugeMaxValue'] = 20;
      $defaultValues['Value1GaugeRedAreaLowValue'] = 0;
      $defaultValues['Value1GaugeRedAreaLowColor'] = "red";
      $defaultValues['Value1GaugeRedAreaHighValue'] = 0;
      $defaultValues['Value1GaugeRedAreaHighColor'] = "red";
      $defaultValues['Value1GaugeNormalAreaColor'] = "green";
      $defaultValues['Value1onDashboard'] = 0;

      $defaultValues['nameValue2'] = "RSSI";
      $defaultValues['Value2GaugeMinValue'] = -130;
      $defaultValues['Value2GaugeMaxValue'] = 10;
      $defaultValues['Value2GaugeRedAreaLowValue'] = -120;
      $defaultValues['Value2GaugeRedAreaLowColor'] = "red";
      $defaultValues['Value2GaugeRedAreaHighValue'] = 0;
      $defaultValues['Value2GaugeRedAreaHighColor'] = "red";
      $defaultValues['Value2GaugeNormalAreaColor'] = "green";

      $defaultValues['nameValue3'] = null;
      $defaultValues['Value3GaugeMinValue'] = 0;
      $defaultValues['Value3GaugeMaxValue'] = 20;
      $defaultValues['Value3GaugeRedAreaLowValue'] = 0;
      $defaultValues['Value3GaugeRedAreaLowColor'] = "red";
      $defaultValues['Value3GaugeRedAreaHighValue'] = 0;
      $defaultValues['Value3GaugeRedAreaHighColor'] = "red";
      $defaultValues['Value3GaugeNormalAreaColor'] = "green";

      $defaultValues['nameValue4'] = null;
      $defaultValues['Value4GaugeMinValue'] = 0;
      $defaultValues['Value4GaugeMaxValue'] = 20;
      $defaultValues['Value4GaugeRedAreaLowValue'] = 0;
      $defaultValues['Value4GaugeRedAreaLowColor'] = "red";
      $defaultValues['Value4GaugeRedAreaHighValue'] = 0;
      $defaultValues['Value4GaugeRedAreaHighColor'] = "red";
      $defaultValues['Value4GaugeNormalAreaColor'] = "green";

      $defaultValues['ttn_payload_id'] = null;
      $defaultValues['NrOfUsedSensors'] = 2;
      $valuesDefined = true;

    } elseif ($sensorName == "ADC") {
      $defaultValues['nameValue1'] = "ADC1";
      $defaultValues['Value1GaugeMinValue'] = 0;
      $defaultValues['Value1GaugeMaxValue'] = 20;
      $defaultValues['Value1GaugeRedAreaLowValue'] = 0;
      $defaultValues['Value1GaugeRedAreaLowColor'] = "red";
      $defaultValues['Value1GaugeRedAreaHighValue'] = 16;
      $defaultValues['Value1GaugeRedAreaHighColor'] = "red";
      $defaultValues['Value1GaugeNormalAreaColor'] = "green";

      $defaultValues['nameValue2'] = "level1";
      $defaultValues['Value2GaugeMinValue'] = 0;
      $defaultValues['Value2GaugeMaxValue'] = 20;
      $defaultValues['Value2GaugeRedAreaLowValue'] = 0;
      $defaultValues['Value2GaugeRedAreaLowColor'] = "red";
      $defaultValues['Value2GaugeRedAreaHighValue'] = 16;
      $defaultValues['Value2GaugeRedAreaHighColor'] = "red";
      $defaultValues['Value2GaugeNormalAreaColor'] = "green";

      $defaultValues['nameValue3'] = "level2";
      $defaultValues['Value3GaugeMinValue'] = 0;
      $defaultValues['Value3GaugeMaxValue'] = 20;
      $defaultValues['Value3GaugeRedAreaLowValue'] = 0;
      $defaultValues['Value3GaugeRedAreaLowColor'] = "red";
      $defaultValues['Value3GaugeRedAreaHighValue'] = 0;
      $defaultValues['Value3GaugeRedAreaHighColor'] = "red";
      $defaultValues['Value3GaugeNormalAreaColor'] = "green";

      $defaultValues['nameValue4'] = null;
      $defaultValues['Value4GaugeMinValue'] = 0;
      $defaultValues['Value4GaugeMaxValue'] = 20;
      $defaultValues['Value4GaugeRedAreaLowValue'] = 0;
      $defaultValues['Value4GaugeRedAreaLowColor'] = "red";
      $defaultValues['Value4GaugeRedAreaHighValue'] = 0;
      $defaultValues['Value4GaugeRedAreaHighColor'] = "red";
      $defaultValues['Value4GaugeNormalAreaColor'] = "green";

      $defaultValues['ttn_payload_id'] = null;
      $defaultValues['NrOfUsedSensors'] = 3;
      $valuesDefined = true;

    } elseif ($sensorName == "DS18B20") {
      $defaultValues['nameValue1'] = "Ch1";
      $defaultValues['Value1GaugeMinValue'] = 0;
      $defaultValues['Value1GaugeMaxValue'] = 80;
      $defaultValues['Value1GaugeRedAreaLowValue'] = 10;
      $defaultValues['Value1GaugeRedAreaLowColor'] = "red";
      $defaultValues['Value1GaugeRedAreaHighValue'] = 70;
      $defaultValues['Value1GaugeRedAreaHighColor'] = "red";
      $defaultValues['Value1GaugeNormalAreaColor'] = "green";

      $defaultValues['nameValue2'] = "Ch2";
      $defaultValues['Value2GaugeMinValue'] = 0;
      $defaultValues['Value2GaugeMaxValue'] = 80;
      $defaultValues['Value2GaugeRedAreaLowValue'] = 10;
      $defaultValues['Value2GaugeRedAreaLowColor'] = "red";
      $defaultValues['Value2GaugeRedAreaHighValue'] = 70;
      $defaultValues['Value2GaugeRedAreaHighColor'] = "red";
      $defaultValues['Value2GaugeNormalAreaColor'] = "green";

      $defaultValues['nameValue3'] = "Ch3";
      $defaultValues['Value3GaugeMinValue'] = 0;
      $defaultValues['Value3GaugeMaxValue'] = 80;
      $defaultValues['Value3GaugeRedAreaLowValue'] = 10;
      $defaultValues['Value3GaugeRedAreaLowColor'] = "red";
      $defaultValues['Value3GaugeRedAreaHighValue'] = 70;
      $defaultValues['Value3GaugeRedAreaHighColor'] = "red";
      $defaultValues['Value3GaugeNormalAreaColor'] = "green";

      $defaultValues['nameValue4'] = "Ch4";
      $defaultValues['Value4GaugeMinValue'] = 0;
      $defaultValues['Value4GaugeMaxValue'] = 80;
      $defaultValues['Value4GaugeRedAreaLowValue'] = 10;
      $defaultValues['Value4GaugeRedAreaLowColor'] = "red";
      $defaultValues['Value4GaugeRedAreaHighValue'] = 70;
      $defaultValues['Value4GaugeRedAreaHighColor'] = "red";
      $defaultValues['Value4GaugeNormalAreaColor'] = "green";

      $defaultValues['ttn_payload_id'] = null;
      $defaultValues['NrOfUsedSensors'] = 2;
      $valuesDefined = true;

    } elseif ($sensorName == "BME280") {
      $defaultValues['nameValue1'] = "Temp";
      $defaultValues['Value1GaugeMinValue'] = 0;
      $defaultValues['Value1GaugeMaxValue'] = 40;
      $defaultValues['Value1GaugeRedAreaLowValue'] = 10;
      $defaultValues['Value1GaugeRedAreaLowColor'] = "red";
      $defaultValues['Value1GaugeRedAreaHighValue'] = 26;
      $defaultValues['Value1GaugeRedAreaHighColor'] = "red";
      $defaultValues['Value1GaugeNormalAreaColor'] = "green";

      $defaultValues['nameValue2'] = "Hum";
      $defaultValues['Value2GaugeMinValue'] = 0;
      $defaultValues['Value2GaugeMaxValue'] = 20;
      $defaultValues['Value2GaugeRedAreaLowValue'] = 0;
      $defaultValues['Value2GaugeRedAreaLowColor'] = "red";
      $defaultValues['Value2GaugeRedAreaHighValue'] = 0;
      $defaultValues['Value2GaugeRedAreaHighColor'] = "red";
      $defaultValues['Value2GaugeNormalAreaColor'] = "green";

      $defaultValues['nameValue3'] = "Pres";
      $defaultValues['Value3GaugeMinValue'] = 0;
      $defaultValues['Value3GaugeMaxValue'] = 3000;
      $defaultValues['Value3GaugeRedAreaLowValue'] = 0;
      $defaultValues['Value3GaugeRedAreaLowColor'] = "red";
      $defaultValues['Value3GaugeRedAreaHighValue'] = 0;
      $defaultValues['Value3GaugeRedAreaHighColor'] = "red";
      $defaultValues['Value3GaugeNormalAreaColor'] = "green";

      $defaultValues['nameValue4'] = "Dew.";
      $defaultValues['Value4GaugeMinValue'] = 0;
      $defaultValues['Value4GaugeMaxValue'] = 20;
      $defaultValues['Value4GaugeRedAreaLowValue'] = 0;
      $defaultValues['Value4GaugeRedAreaLowColor'] = "red";
      $defaultValues['Value4GaugeRedAreaHighValue'] = 0;
      $defaultValues['Value4GaugeRedAreaHighColor'] = "red";
      $defaultValues['Value4GaugeNormalAreaColor'] = "green";

      $defaultValues['ttn_payload_id'] = null;
      $defaultValues['NrOfUsedSensors'] = 4;
      $valuesDefined = true;

    } elseif ($sensorName == "DS2438") {
      $defaultValues['nameValue1'] = "V";
      $defaultValues['Value1GaugeMinValue'] = 0;
      $defaultValues['Value1GaugeMaxValue'] = 20;
      $defaultValues['Value1GaugeRedAreaLowValue'] = 0;
      $defaultValues['Value1GaugeRedAreaLowColor'] = "red";
      $defaultValues['Value1GaugeRedAreaHighValue'] = 0;
      $defaultValues['Value1GaugeRedAreaHighColor'] = "red";
      $defaultValues['Value1GaugeNormalAreaColor'] = "green";

      $defaultValues['nameValue2'] = "A";
      $defaultValues['Value2GaugeMinValue'] = 0;
      $defaultValues['Value2GaugeMaxValue'] = 20;
      $defaultValues['Value2GaugeRedAreaLowValue'] = 0;
      $defaultValues['Value2GaugeRedAreaLowColor'] = "red";
      $defaultValues['Value2GaugeRedAreaHighValue'] = 0;
      $defaultValues['Value2GaugeRedAreaHighColor'] = "red";
      $defaultValues['Value2GaugeNormalAreaColor'] = "green";

      $defaultValues['nameValue3'] = null;
      $defaultValues['Value3GaugeMinValue'] = 0;
      $defaultValues['Value3GaugeMaxValue'] = 20;
      $defaultValues['Value3GaugeRedAreaLowValue'] = 0;
      $defaultValues['Value3GaugeRedAreaLowColor'] = "red";
      $defaultValues['Value3GaugeRedAreaHighValue'] = 0;
      $defaultValues['Value3GaugeRedAreaHighColor'] = "red";
      $defaultValues['Value3GaugeNormalAreaColor'] = "green";

      $defaultValues['nameValue4'] = null;
      $defaultValues['Value4GaugeMinValue'] = 0;
      $defaultValues['Value4GaugeMaxValue'] = 20;
      $defaultValues['Value4GaugeRedAreaLowValue'] = 0;
      $defaultValues['Value4GaugeRedAreaLowColor'] = "red";
      $defaultValues['Value4GaugeRedAreaHighValue'] = 0;
      $defaultValues['Value4GaugeRedAreaHighColor'] = "red";
      $defaultValues['Value4GaugeNormalAreaColor'] = "green";

      $defaultValues['ttn_payload_id'] = null;
      $defaultValues['NrOfUsedSensors'] = 2;
      $valuesDefined = true;
      
    } elseif ($sensorName == "Digital") {
      $defaultValues['nameValue1'] = "Ch1";
      $defaultValues['Value1GaugeMinValue'] = 0;
      $defaultValues['Value1GaugeMaxValue'] = 1;
      $defaultValues['Value1GaugeRedAreaLowValue'] = 0;
      $defaultValues['Value1GaugeRedAreaLowColor'] = "red";
      $defaultValues['Value1GaugeRedAreaHighValue'] = 0;
      $defaultValues['Value1GaugeRedAreaHighColor'] = "red";
      $defaultValues['Value1GaugeNormalAreaColor'] = "green";

      $defaultValues['nameValue2'] = "Ch2";
      $defaultValues['Value2GaugeMinValue'] = 0;
      $defaultValues['Value2GaugeMaxValue'] = 1;
      $defaultValues['Value2GaugeRedAreaLowValue'] = 0;
      $defaultValues['Value2GaugeRedAreaLowColor'] = "red";
      $defaultValues['Value2GaugeRedAreaHighValue'] = 0;
      $defaultValues['Value2GaugeRedAreaHighColor'] = "red";
      $defaultValues['Value2GaugeNormalAreaColor'] = "green";

      $defaultValues['nameValue3'] = null;
      $defaultValues['Value3GaugeMinValue'] = 0;
      $defaultValues['Value3GaugeMaxValue'] = 1;
      $defaultValues['Value3GaugeRedAreaLowValue'] = 0;
      $defaultValues['Value3GaugeRedAreaLowColor'] = "red";
      $defaultValues['Value3GaugeRedAreaHighValue'] = 0;
      $defaultValues['Value3GaugeRedAreaHighColor'] = "red";
      $defaultValues['Value3GaugeNormalAreaColor'] = "green";

      $defaultValues['nameValue4'] = null;
      $defaultValues['Value4GaugeMinValue'] = 0;
      $defaultValues['Value4GaugeMaxValue'] = 1;
      $defaultValues['Value4GaugeRedAreaLowValue'] = 0;
      $defaultValues['Value4GaugeRedAreaLowColor'] = "red";
      $defaultValues['Value4GaugeRedAreaHighValue'] = 0;
      $defaultValues['Value4GaugeRedAreaHighColor'] = "red";
      $defaultValues['Value4GaugeNormalAreaColor'] = "green";

      $defaultValues['ttn_payload_id'] = null;
      $defaultValues['NrOfUsedSensors'] = 2;
      $valuesDefined = true;
    }

    if ($valuesDefined == true) {
      $statement = $pdo->prepare("INSERT INTO sensorconfig (boardid, typid, name," .
      "nameValue1, Value1GaugeMinValue, Value1GaugeMaxValue, Value1GaugeRedAreaLowValue, Value1GaugeRedAreaLowColor, Value1GaugeRedAreaHighValue, Value1GaugeRedAreaHighColor, Value1GaugeNormalAreaColor, Value1onDashboard," .
      "nameValue2, Value2GaugeMinValue, Value2GaugeMaxValue, Value2GaugeRedAreaLowValue, Value2GaugeRedAreaLowColor, Value2GaugeRedAreaHighValue, Value2GaugeRedAreaHighColor, Value2GaugeNormalAreaColor, Value2onDashboard," .
      "nameValue3, Value3GaugeMinValue, Value3GaugeMaxValue, Value3GaugeRedAreaLowValue, Value3GaugeRedAreaLowColor, Value3GaugeRedAreaHighValue, Value3GaugeRedAreaHighColor, Value3GaugeNormalAreaColor, Value3onDashboard," .
      "nameValue4, Value4GaugeMinValue, Value4GaugeMaxValue, Value4GaugeRedAreaLowValue, Value4GaugeRedAreaLowColor, Value4GaugeRedAreaHighValue, Value4GaugeRedAreaHighColor, Value4GaugeNormalAreaColor, Value4onDashboard," .
      "ttn_payload_id, NrOfUsedSensors, onDashboard ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $statement->execute(array($boardid, $mysensorTypId->id, $sensorName,
        $defaultValues['nameValue1'], $defaultValues['Value1GaugeMinValue'], $defaultValues['Value1GaugeMaxValue'], $defaultValues['Value1GaugeRedAreaLowValue'], $defaultValues['Value1GaugeRedAreaLowColor'], $defaultValues['Value1GaugeRedAreaHighValue'], $defaultValues['Value1GaugeRedAreaHighColor'], $defaultValues['Value1GaugeNormalAreaColor'],
        $defaultValues['Value1onDashboard'],
        $defaultValues['nameValue2'], $defaultValues['Value2GaugeMinValue'], $defaultValues['Value2GaugeMaxValue'], $defaultValues['Value2GaugeRedAreaLowValue'], $defaultValues['Value2GaugeRedAreaLowColor'], $defaultValues['Value2GaugeRedAreaHighValue'], $defaultValues['Value2GaugeRedAreaHighColor'], $defaultValues['Value2GaugeNormalAreaColor'], 
        $defaultValues['Value2onDashboard'],
        $defaultValues['nameValue3'], $defaultValues['Value3GaugeMinValue'], $defaultValues['Value3GaugeMaxValue'], $defaultValues['Value3GaugeRedAreaLowValue'], $defaultValues['Value3GaugeRedAreaLowColor'], $defaultValues['Value3GaugeRedAreaHighValue'], $defaultValues['Value3GaugeRedAreaHighColor'], $defaultValues['Value3GaugeNormalAreaColor'], 
        $defaultValues['Value3onDashboard'],
        $defaultValues['nameValue4'], $defaultValues['Value4GaugeMinValue'], $defaultValues['Value4GaugeMaxValue'], $defaultValues['Value4GaugeRedAreaLowValue'], $defaultValues['Value4GaugeRedAreaLowColor'], $defaultValues['Value4GaugeRedAreaHighValue'], $defaultValues['Value4GaugeRedAreaHighColor'], $defaultValues['Value4GaugeNormalAreaColor'], 
        $defaultValues['Value4onDashboard'],
        $defaultValues['ttn_payload_id'], $defaultValues['NrOfUsedSensors'], 1));
      $neue_id = $pdo->lastInsertId();
      return $neue_id;
    }
  }

  /*
  * Get Sensor type object of a given sensor sensorTypId id.
  */
  public static function getSensorType($sensorTypId) {
    $pdo = dbConfig::getInstance();
    $sensortyps = $pdo->prepare("SELECT * FROM sensortypes WHERE id = ? ORDER BY id LIMIT 1");
    $sensortyps->execute(array($sensorTypId));
    $SensorData2 = $sensortyps->fetch(PDO::FETCH_ASSOC);
    return $SensorData2;
  }

  /*
  * Get all Sensor type object of a given sensor sensorTypId id.
  */
  public static function getAllSensorType() {
    $pdo = dbConfig::getInstance();
    $sensortyps = $pdo->prepare("SELECT * FROM sensortypes ORDER BY id");
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
  public static function isUserRegistred($email) {
    $pdo = dbConfig::getInstance();
    $statement = $pdo->prepare("SELECT * FROM users WHERE email = ? ORDER BY id LIMIT 1");
    $statement->execute(array($email));
    $statement2 = $statement->fetch(PDO::FETCH_ASSOC);
    if ($statement2 != NULL) {
      $result = true;
    } else {
      $result = false;
    }
    //$result = $statement->execute();
    //return $statement->fetchAll();
    return $result;
  }

	/**
	 * Outputs an error message and stops the further exectution of the script.
	 */
	public function error($error_msg) {
		include("common/header.inc.php");
		include("common/error.inc.php");
		include("common/footer.inc.php");
		exit();
	}

  /**
	 * Write $text into a log file.
	 */
  private function write_to_log($text)
  {
    $format = "csv"; // csv or txt
    $datum_zeit = date("d.m.Y H:i:s");
    $site = $_SERVER['REQUEST_URI'];
    $dateiname = dirname(__FILE__)."/logs/log.$format";
    $header = array("Date", "Site", "Log");
    $json = json_encode($text);
    $infos = array($datum_zeit, $site, $json);
    if ($format == "csv") {
        $eintrag2 = '"' . implode('", "', $infos) . '"';
    } else {
        $eintrag2 = implode("\t", $infos);
    }
    $write_header = !file_exists($dateiname);
    $datei = fopen($dateiname, "a");
    if ($write_header) {
        if ($format == "csv") {
            $header_line = '"' . implode('", "', $header) . '"';
        } else {
            $header_line = implode("\t", $header);
        }
        fputs($datei, $header_line . "\n");
    }
    fputs($datei, $eintrag2 . "\n");
    fclose($datei);
  }
}
