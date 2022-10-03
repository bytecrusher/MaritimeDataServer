<?php
/**
 * class for updating data into DB
 * 
 * @author: Guntmar Hoeche
 * @license: TBD
 */

//include_once("func/dbConfig.func.php");
include_once(dirname(__FILE__)."/dbConfig.func.php");
//require_once("func/user.class.php");
require_once(dirname(__FILE__)."/user.class.php");

class dbUpdateData {

  public static function updateUserData($post, $userid) {
    $pdo = dbConfig::getInstance();
    $firstname = trim($post['firstname']);
    $lastname = trim($post['lastname']);
    if($firstname == "" || $lastname == "") {
      return false;
    } else {
      $statement = $pdo->prepare("UPDATE users SET firstname = :firstname, lastname = :lastname, updated_at=NOW() WHERE id = :userid");
      $result = $statement->execute(array('firstname' => $firstname, 'lastname'=> $lastname, 'userid' => $userid ));
      return "User Data sucessfully saved.";
    }
  }

  public static function updateUserMail($post, $userid) {
    $pdo = dbConfig::getInstance();
    $email = trim($post['email']);
    $statement = $pdo->prepare("UPDATE users SET email = :email WHERE id = :userid");
    $result = $statement->execute(array('email' => $email, 'userid' => $userid ));
    return "E-Mail address successfully saved.";
  }

  public static function updateUserPassword($password_hash, $userid) {
    $pdo = dbConfig::getInstance();
    $statement = $pdo->prepare("UPDATE users SET password = :password WHERE id = :userid");
    $result = $statement->execute(array('password' => $password_hash, 'userid' => $userid ));
    return "Password successfully saved.";
  }

  public static function updateUserPasswordcode($passwordcode, $userid) {
    $pdo = dbConfig::getInstance();
    $statement = $pdo->prepare("UPDATE users SET passwordcode = :passwordcode, passwordcode_time = NOW() WHERE id = :userid");
    return $statement->execute(array('passwordcode' => sha1($passwordcode), 'userid' => $userid));
  }

  public static function updateUserStatus($post) {
    $pdo = dbConfig::getInstance();
    $result = null;
    foreach($post['active'] as $i=>$array_wert)
		{
			$statement = $pdo->prepare("UPDATE users SET active =?, usergroup_admin=? WHERE id =?");
			$result = $statement->execute(array($post['active'][$i], $post['usergroup_admin'][$i], $i ));
		}
		if ($result) {
			return "Users updated.";
		} else {
			return false;
		}
  }

  public static function updateUserDashboardupdateInterval($post, $userid) {
    $pdo = dbConfig::getInstance();
    $dashboardUpdateInterval = ($post['updateInterval']);
    $statement = $pdo->prepare("UPDATE users SET dashboardUpdateInterval = :dashboardUpdateInterval WHERE id = :userid");
    $result = $statement->execute(array('dashboardUpdateInterval' => $dashboardUpdateInterval, 'userid' => $userid ));
    return "Dashboard Update Interval successfully saved.";
  }

  public static function insertUser($email, $password_hash, $vorname, $nachname) {
    $pdo = dbConfig::getInstance();
    $statement = $pdo->prepare("INSERT INTO users (email, password, firstname, lastname) VALUES (:email, :password, :firstname, :lastname)");
    return $statement->execute(array('email' => $email, 'password' => $password_hash, 'firstname' => $vorname, 'lastname' => $nachname));
  }

  public static function insertSecuritytoken($userid) {
    $pdo = dbConfig::getInstance();
    $identifier = myFunctions::random_string();
    $securitytoken = myFunctions::random_string();

    $insert = $pdo->prepare("INSERT INTO securitytokens (user_id, identifier, securitytoken) VALUES (:user_id, :identifier, :securitytoken)");
    $insert->execute(array('user_id' => $userid, 'identifier' => $identifier, 'securitytoken' => sha1($securitytoken)));
    setcookie("identifier",$identifier,time()+(3600*24*365)); //Valid for 1 year
    setcookie("securitytoken",$securitytoken,time()+(3600*24*365)); //Valid for 1 year
  }

  public static function updateBoard($post) {
    $pdo = dbConfig::getInstance();
   	 if (!isset($post['performupdate'])) {
   		$performupdate = 0;
   	 } else {
   		$performupdate = 1;
   	 }
   	 if (!isset($post['alarmOnUnavailable'])) {
   		$alarmOnUnavailable = 0;
   	 } else {
   		$alarmOnUnavailable = 1;
   	 }
    if (!isset($post['onDashboard'])) {
     $onDashboard = 0;
    } else {
     $onDashboard = 1;
    }
   	 $statement2 = $pdo->prepare("UPDATE boardconfig SET name=?, location=?, description=?, ttn_app_id=?, ttn_dev_id=?, performupdate=?, alarmOnUnavailable=?, onDashboard=?, updateDataTimer=? WHERE id=?");
   	 $statement2->execute(array($post['name'], $post['location'], $post['description'], $post['ttn_app_id'], $post['ttn_dev_id'], $performupdate, $alarmOnUnavailable, $onDashboard, $post['updateDataTimer'], $post['id']));
   	 if ($statement2) {
      return "Board changes saved.";
   	 } else {
      return false;
   	 }
  }

  public static function updateSensor($post) {
    $pdo = dbConfig::getInstance();
    $statement2 = $pdo->prepare("UPDATE sensorconfig SET name=?, description=?, typid=?, locationOfMeasurement=?, nameValue1=?, nameValue2=?, nameValue3=?, nameValue4=?, NrOfUsedSensors=?, onDashboard=? WHERE id=?");
  	$statement2->execute(array($post['name'], $post['description'], $post['typid'], $post['locationOfMeasurement'], $post['nameValue1'], $post['nameValue2'], $post['nameValue3'], $post['nameValue4'], $post['NrOfUsedSensors'], $post['onDashboard'], $post['id']));
  	if ($statement2) {
      return "Sensor changes successfully saved.";
  	 } else {
      return false;
  	 }
  }

  public static function updateSensorOrderNumber($post) {
    $pdo = dbConfig::getInstance();
    if ($post['channel'] != null) {
      $statement2 = $pdo->prepare("UPDATE sensorconfig SET Value" . $post['channel'] . "DashboardOrdnerNr=? WHERE id=?");
  	  $statement2->execute(array($post['ordnernumber'], $post['id']));
    }
    
  	if ($statement2) {
      return "Sensor order successfully saved.";
  	 } else {
      return false;
  	 }
  }

  public static function updateSensorModal($post) {
    $pdo = dbConfig::getInstance();
    if ($post['channel'] == 1) {
      $statement2 = $pdo->prepare("UPDATE sensorconfig SET name=?, description=?, typid=?, locationOfMeasurement=?, nameValue1=?, Value1GaugeMinValue=?, Value1GaugeMaxValue=?, Value1GaugeRedAreaLowValue=?, Value1GaugeRedAreaLowColor=?, Value1GaugeRedAreaHighValue=?, Value1GaugeRedAreaHighColor=?, Value1GaugeNormalAreaColor=?, Value1DashboardOrdnerNr=?, onDashboard=? WHERE id=?");
      $statement2->execute(array($post['name'], $post['description'], $post['typid'], $post['locationOfMeasurement'], $post['nameValue1'], $post['Value1GaugeMinValue'], $post['Value1GaugeMaxValue'], $post['Value1GaugeRedAreaLowValue'], $post['Value1GaugeRedAreaLowColor'], $post['Value1GaugeRedAreaHighValue'], $post['Value1GaugeRedAreaHighColor'], $post['Value1GaugeNormalAreaColor'], $post['Value1DashboardOrdnerNr'], $post['onDashboard'], $post['id']));
    } else if ($post['channel'] == 2) {
      $statement2 = $pdo->prepare("UPDATE sensorconfig SET name=?, description=?, typid=?, locationOfMeasurement=?, nameValue2=?, Value2GaugeMinValue=?, Value2GaugeMaxValue=?, Value2GaugeRedAreaLowValue=?, Value2GaugeRedAreaLowColor=?, Value2GaugeRedAreaHighValue=?, Value2GaugeRedAreaHighColor=?,Value2GaugeNormalAreaColor=?, Value2DashboardOrdnerNr=?, onDashboard=? WHERE id=?");
      $statement2->execute(array($post['name'], $post['description'], $post['typid'], $post['locationOfMeasurement'], $post['nameValue2'], $post['Value2GaugeMinValue'], $post['Value2GaugeMaxValue'], $post['Value2GaugeRedAreaLowValue'], $post['Value2GaugeRedAreaLowColor'], $post['Value2GaugeRedAreaHighValue'], $post['Value2GaugeRedAreaHighColor'], $post['Value2GaugeNormalAreaColor'], $post['Value2DashboardOrdnerNr'], $post['onDashboard'], $post['id']));
    } else if ($post['channel'] == 3) {
      $statement2 = $pdo->prepare("UPDATE sensorconfig SET name=?, description=?, typid=?, locationOfMeasurement=?, nameValue3=?, Value3GaugeMinValue=?, Value3GaugeMaxValue=?, Value3GaugeRedAreaLowValue=?, Value3GaugeRedAreaLowColor=?, Value3GaugeRedAreaHighValue=?, Value3GaugeRedAreaHighColor=?,Value3GaugeNormalAreaColor=?, Value3DashboardOrdnerNr=?, onDashboard=? WHERE id=?");
      $statement2->execute(array($post['name'], $post['description'], $post['typid'], $post['locationOfMeasurement'], $post['nameValue3'], $post['Value3GaugeMinValue'], $post['Value3GaugeMaxValue'], $post['Value3GaugeRedAreaLowValue'], $post['Value3GaugeRedAreaLowColor'], $post['Value3GaugeRedAreaHighValue'], $post['Value3GaugeRedAreaHighColor'], $post['Value3GaugeNormalAreaColor'], $post['Value3DashboardOrdnerNr'], $post['onDashboard'], $post['id']));
    } else if ($post['channel'] == 4) {
      $statement2 = $pdo->prepare("UPDATE sensorconfig SET name=?, description=?, typid=?, locationOfMeasurement=?, nameValue4=?, Value4GaugeMinValue=?, Value4GaugeMaxValue=?, Value4GaugeRedAreaLowValue=?, Value4GaugeRedAreaLowColor=?, Value4GaugeRedAreaHighValue=?, Value4GaugeRedAreaHighColor=?,Value4GaugeNormalAreaColor=?, Value4DashboardOrdnerNr=?, onDashboard=? WHERE id=?");
      $statement2->execute(array($post['name'], $post['description'], $post['typid'], $post['locationOfMeasurement'], $post['nameValue4'], $post['Value4GaugeMinValue'], $post['Value4GaugeMaxValue'], $post['Value4GaugeRedAreaLowValue'], $post['Value4GaugeRedAreaLowColor'], $post['Value4GaugeRedAreaHighValue'], $post['Value4GaugeRedAreaHighColor'], $post['Value4GaugeNormalAreaColor'], $post['Value4DashboardOrdnerNr'], $post['onDashboard'], $post['id']));
    } 

  	if ($statement2) {
      return "Sensor changes successfully saved.";
  	 } else {
      return false;
  	 }
  }
}
?>
