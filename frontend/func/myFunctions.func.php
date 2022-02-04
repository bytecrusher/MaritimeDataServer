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
  public static function getMyBoards($id) {
    if (!$id == null) {
      $pdo = dbConfig::getInstance();
      $myboards = $pdo->prepare("SELECT * FROM boardconfig WHERE owner_userid = " . $id . " ORDER BY id");
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
  * ToDo: get "boardtyp->name" instead of boardtypid. This name should be the name of the firmware.
  */
  public static function getBoardByMac($boardMac) {
    if (!$boardMac == null) {
      $pdo = dbConfig::getInstance();
      $myboards = $pdo->prepare("SELECT * FROM boardconfig WHERE macaddress = '" . $boardMac . "' ORDER BY id LIMIT 1");
      //var_dump($myboards);
      $result = $myboards->execute();
      $myboards2 = $myboards->fetch(PDO::FETCH_ASSOC);
      return $myboards2;
      return $myboards;
    }
  }

  /*
  * Get all sensors of a given board id.
  */
  public static function getAllSensorsOfBoard($id) {
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
  * Get Sensor type of a given sensor id.
  */
  public static function getSensorType($sensorId) {
    $pdo = dbConfig::getInstance();
    $sensortyps = $pdo->prepare("SELECT * FROM sensortypes WHERE id = ? ORDER BY id LIMIT 1");
    $sensortyps->execute(array($sensorId));
    $SensorData2 = $sensortyps->fetch(PDO::FETCH_ASSOC);
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

	/**
	 * Outputs an error message and stops the further exectution of the script.
	 */
	public function error($error_msg) {
		include("common/header.inc.php");
		include("common/error.inc.php");
		include("common/footer.inc.php");
		exit();
	}
}
