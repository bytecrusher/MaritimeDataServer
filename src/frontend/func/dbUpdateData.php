<?php
/**
 * class for updating data into DB
 * 
 * @author: Guntmar Hoeche
 * @license: TBD
 */

include_once(dirname(__FILE__) . "/dbConfig.func.php");
require_once(dirname(__FILE__) . "/user.class.php");
require_once(dirname(__FILE__) . "/writeToLogFunction.func.php");

class dbUpdateData {
  /**
  * Update User Data.
  * @return bool — TRUE on success or FALSE on failure.
  * @throws Exception — Return Exception message on error.
  */
  public static function updateUserData($post, $userid) {
    $pdo = dbConfig::getInstance();
    $firstname = trim($post['firstname']);
    $lastname = trim($post['lastname']);
    if($firstname == "" || $lastname == "") {
      throw new Exception('Firstname and Lastname shall not be empty for userid: ' . $userid);
    } else {
      try {
        $statement = $pdo->prepare("UPDATE users SET firstname = :firstname, lastname = :lastname, updated_at=NOW() WHERE id = :userid");
        $result = $statement->execute(array('firstname' => $firstname, 'lastname'=> $lastname, 'userid' => $userid ));
        return $result;
      } catch (PDOException $e) {
        writeToLogFunction::write_to_log("Error: User Data not saved for userid: " . $userid, $_SERVER["SCRIPT_FILENAME"]);
        writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
        throw new Exception('User Data not saved.');
      }
    }
  }

  /**
  * Update Timezone.
  * @return bool — TRUE on success or FALSE on failure.
  * @throws Exception — Return Exception message on error.
  */
  public static function updateUserTimeZoneData($post, $userid) {
    $pdo = dbConfig::getInstance();
    $Timezone = trim($post['Timezone']);
    if($Timezone == "") {
      throw new Exception('Timezone infunction updateUserTimeZoneData shall not be empty for user: ' . $userid);
    } else {
      try {
        $statement = $pdo->prepare("UPDATE users SET Timezone = :Timezone, updated_at=NOW() WHERE id = :userid");
        $result = $statement->execute(array('Timezone' => $Timezone, 'userid' => $userid ));
        return $result;
      } catch (PDOException $e) {
        writeToLogFunction::write_to_log("Error: Timezone not successfully saved for userid: " . $userid, $_SERVER["SCRIPT_FILENAME"]);
        writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
        throw new Exception('Timezone not successfully saved.');
      }
    }
  }

  /**
  * Update User Email Adress.
  * @return bool — TRUE on success or FALSE on failure.
  * @throws Exception — Return Exception message on error.
  */
  public static function updateUserMail($post, $userid) {
    $pdo = dbConfig::getInstance();
    $email = trim($post['email']);
    try {
      $statement = $pdo->prepare("UPDATE users SET email = :email WHERE id = :userid");
      $result = $statement->execute(array('email' => $email, 'userid' => $userid ));
      return $result;
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: User Mail not successfully saved for userid: " . $userid, $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      throw new Exception('User Mail not successfully saved.');
    }
  }

  /**
  * Update User Password.
  * @return bool — TRUE on success or FALSE on failure.
  * @throws Exception — Return Exception message on error.
  */
  public static function updateUserPassword($password_hash, $userid) {
    $pdo = dbConfig::getInstance();
    try {
      $statement = $pdo->prepare("UPDATE users SET password = :password WHERE id = :userid");
      $result = $statement->execute(array('password' => $password_hash, 'userid' => $userid ));
      return $result;
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: User Password not successfully saved for userid: " . $userid, $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      throw new Exception('User Password not successfully saved.');
    }
  }

  /**
  * Update User Passwordcode (for password reset).
  * @return bool — TRUE on success or FALSE on failure.
  * @throws Exception — Return Exception message on error.
  */
  public static function updateUserPasswordcode($passwordcode, $userid) {
    $pdo = dbConfig::getInstance();
    try {
      $statement = $pdo->prepare("UPDATE users SET passwordcode = :passwordcode, passwordcode_time = NOW() WHERE id = :userid");
      return $statement->execute(array('passwordcode' => sha1($passwordcode), 'userid' => $userid));
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: User Password reset not successfully saved for userid: " . $userid, $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      throw new Exception('User Password resest Update in DB not successfully saved.');
    }
  }

  /**
  * Update User Status.
  * @return bool — TRUE on success or FALSE on failure.
  * @throws Exception — Return Exception message on error.
  */
  public static function updateUserStatus($post) {
    $pdo = dbConfig::getInstance();
    $result = null;
    foreach($post['active'] as $i=>$array_wert)
		{
      try {
        $statement = $pdo->prepare("UPDATE users SET active =?, usergroup_admin=? WHERE id =?");
        $statement->execute(array($post['active'][$i], $post['usergroup_admin'][$i], $i ));
        return true;
      } catch (PDOException $e) {
        writeToLogFunction::write_to_log("Error: User Status in DB not successfully updated for userid: " . $post['active'][$i], $_SERVER["SCRIPT_FILENAME"]);
        writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
        throw new Exception('User Status in DB not successfully updated.');
      }
		}
    return false;
  }

  /**
  * Activate User Status.
  * @return bool — TRUE on success or FALSE on failure.
  * @throws Exception — Return Exception message on error.
  */
  public static function activateUserStatus($userId) {
    $pdo = dbConfig::getInstance();
    $result = null;
    try {
      $statement = $pdo->prepare("UPDATE users SET active =1 WHERE id =?");
      $pdoresult = $statement->execute(array($userId));
      $changedrows = $statement->rowCount();
      if($changedrows == 1 ) {
        return true;
      } else {
        return false;
      }
        
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: User Status in DB not successfully updated for userid: " . $userId, $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      throw new Exception('User Status in DB not successfully updated.');
    }
    return false;
  }

  /**
  * Update User Dashboard update interval.
  * @return bool — TRUE on success or FALSE on failure.
  * @throws Exception — Return Exception message on error.
  */
  public static function updateUserDashboardupdateInterval($post, $userid) {
    $pdo = dbConfig::getInstance();
    $dashboardUpdateInterval = ($post['updateInterval']);
    try {
      $statement = $pdo->prepare("UPDATE users SET dashboardUpdateInterval = :dashboardUpdateInterval WHERE id = :userid");
      return $statement->execute(array('dashboardUpdateInterval' => $dashboardUpdateInterval, 'userid' => $userid ));
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: User Dashboard Update Interval in DB not successfully updated for userid: " . $userid, $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      throw new Exception('User Dashboard Update Interval in DB not successfully updated.');
    }
  }

  /**
  * Update User Dashboard updateUserReceiveNotifications.
  * @return bool — TRUE on success or FALSE on failure.
  * @throws Exception — Return Exception message on error.
  */
  public static function updateUserReceiveNotifications($post, $userid) {
    $pdo = dbConfig::getInstance();
    $varReceiveNotifications = ($post['receiveNotifications']);
    try {
      $statement = $pdo->prepare("UPDATE users SET receive_notifications = :receive_notifications WHERE id = :userid");
      return $statement->execute(array('receive_notifications' => $varReceiveNotifications, 'userid' => $userid ));
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: User  ReceiveNotifications in DB not successfully updated for userid: " . $userid, $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      throw new Exception('User ReceiveNotifications in DB not successfully updated.');
    }
  }

  /**
  * Insert User.
  * @return bool — TRUE on success or FALSE on failure.
  * @throws Exception — Return Exception message on error.
  */
  public static function insertUser($email, $password_hash, $vorname, $nachname) {
    $pdo = dbConfig::getInstance();
    try {
      $statement = $pdo->prepare("INSERT INTO users (email, password, firstname, lastname) VALUES (:email, :password, :firstname, :lastname)");
      //return $statement->execute(array('email' => $email, 'password' => $password_hash, 'firstname' => $vorname, 'lastname' => $nachname));
      $statement->execute(array('email' => $email, 'password' => $password_hash, 'firstname' => $vorname, 'lastname' => $nachname));
      return $pdo->lastInsertId();
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: User not inserted successfully for email: " . $email, $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      throw new Exception('User not inserted successfully.');
    }
  }

  /**
  * Insert Admin User.
  * @return bool — TRUE on success or FALSE on failure.
  * @throws Exception — Return Exception message on error.
  */
  public static function insertAdmin($email, $password_hash, $vorname, $nachname) {
    $pdo = dbConfig::getInstance();
    try {
      $statement = $pdo->prepare("INSERT INTO users (email, password, firstname, lastname, usergroup_admin, active ) VALUES (:email, :password, :firstname, :lastname, :usergroup_admin, :active )");
      return $statement->execute(array('email' => $email, 'password' => $password_hash, 'firstname' => $vorname, 'lastname' => $nachname, 'usergroup_admin' => '1', 'active' => '1'));
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: Admin not inserted successfully for email: " . $email, $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      throw new Exception('Admin not inserted successfully.');
    }
  }

  /**
  * Insert Securitytoken.
  * @return bool — TRUE on success or FALSE on failure.
  * @throws Exception — Return Exception message on error.
  */
  public static function insertSecuritytoken($userid) {
    $pdo = dbConfig::getInstance();
    $identifier = myFunctions::random_string();
    $securitytoken = myFunctions::random_string();
    try {
      $insert = $pdo->prepare("INSERT INTO securitytokens (user_id, identifier, securitytoken) VALUES (:user_id, :identifier, :securitytoken)");
      $insert->execute(array('user_id' => $userid, 'identifier' => $identifier, 'securitytoken' => sha1($securitytoken)));
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: insertSecuritytoken not inserted successfully for userid: " . $userid, $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      throw new Exception('insertSecuritytoken not inserted successfully.');
    }
    setcookie("identifier",$identifier,time()+(3600*24*365)); //Valid for 1 year
    setcookie("securitytoken",$securitytoken,time()+(3600*24*365)); //Valid for 1 year
  }

  /**
  * Update Board.
  * @return bool — TRUE on success or FALSE on failure.
  * @throws Exception — Return Exception message on error.
  */
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
    if ($post['ownerid'] == "") {
      $post['ownerid'] = null;
    }
    if ($post['updateDataTimer'] == "") {
      $post['updateDataTimer'] = 15;
    }
    if ($post['offlineDataTimer'] == "") {
      $post['offlineDataTimer'] = 15;
    }
    try {
      $statement2 = $pdo->prepare("UPDATE boardconfig SET name=?, location=?, owner_userid=?, description=?, ttn_app_id=?, ttn_dev_id=?, performupdate=?, alarmOnUnavailable=?, onDashboard=?, updateDataTimer=?, offlineDataTimer=? WHERE id=?");
      return $statement2->execute(array($post['name'], $post['location'], $post['ownerid'], $post['description'], $post['ttn_app_id'], $post['ttn_dev_id'], $performupdate, $alarmOnUnavailable, $onDashboard, $post['updateDataTimer'], $post['offlineDataTimer'], $post['id']));
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: Board not updated successfully for userid: " . $post['ownerid'], $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      throw new Exception('Board not updated successfully.');
    }
  }

  /**
  * Update Board.
  * @return bool — TRUE on success or FALSE on failure.
  * @throws Exception — Return Exception message on error.
  */
  public static function removeBoardOwner($post) {
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
    if ($post['ownerid'] == "") {
      $post['ownerid'] = null;
    }
    try {
      //$statement2 = $pdo->prepare("UPDATE boardconfig SET name=?, location=?, owner_userid=?, description=?, ttn_app_id=?, ttn_dev_id=?, performupdate=?, alarmOnUnavailable=?, onDashboard=?, updateDataTimer=? WHERE id=?");

      $statement2 = $pdo->prepare("UPDATE boardconfig SET owner_userid=NULL WHERE id=?");

      return $statement2->execute(array($post['id']));
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: Board not updated successfully for userid: " . $post['ownerid'], $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      throw new Exception('Board not updated successfully.');
    }
  }

  /**
  * Update Sensor.
  * @return bool — TRUE on success or FALSE on failure.
  * @throws Exception — Return Exception message on error.
  */
  public static function updateSensor($post) {
    $pdo = dbConfig::getInstance();
    if (!isset($post['onDashboard'])) {
      $post['onDashboard'] = 0;
    }
    $Value1onDashboardvar = $Value2onDashboardvar = $Value3onDashboardvar = $Value4onDashboardvar = 0;
    if (isset($post['Value1onDashboard'])) {
      $Value1onDashboardvar = $post['Value1onDashboard'];
    }
    if (isset($post['Value2onDashboard'])) {
      $Value2onDashboardvar = $post['Value2onDashboard'];
    }
    if (isset($post['Value3onDashboard'])) {
      $Value3onDashboardvar = $post['Value3onDashboard'];
    }
    if (isset($post['Value4onDashboard'])) {
      $Value4onDashboardvar = $post['Value4onDashboard'];
    }

    try {
      $statement2 = $pdo->prepare("UPDATE sensorconfig SET name=?, description=?, typid=?, locationOfMeasurement=?, nameValue1=?, Value1onDashboard=?, nameValue2=?, Value2onDashboard=?, nameValue3=?, Value3onDashboard=?, nameValue4=?, Value4onDashboard=?, NrOfUsedSensors=?, onDashboard=? WHERE id=?");
      return $statement2->execute(array($post['name'], $post['description'], $post['typid'], $post['locationOfMeasurement'], $post['nameValue1'], $Value1onDashboardvar, $post['nameValue2'], $Value2onDashboardvar, $post['nameValue3'], $Value3onDashboardvar, $post['nameValue4'], $Value4onDashboardvar, $post['NrOfUsedSensors'], $post['onDashboard'], $post['id']));
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: Sensor not updated successfully.", $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      throw new Exception('Sensor not updated successfully.');
    }
  }

  /**
  * Update Sensor order number.
  * @return bool — TRUE on success or FALSE on failure.
  * @throws Exception — Return Exception message on error.
  */
  public static function updateSensorOrderNumber($post) {
    $pdo = dbConfig::getInstance();
    if (json_encode($post['channel']) != null) {
      try {
        $statement2 = $pdo->prepare("UPDATE sensorconfig SET Value" . $post['channel'] . "DashboardOrdnerNr=? WHERE id=?");
  	    return $statement2->execute(array($post['ordnernumber'], $post['id']));
      } catch (PDOException $e) {
        writeToLogFunction::write_to_log("Error: Sensor order number not saved.", $_SERVER["SCRIPT_FILENAME"]);
        writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
        throw new Exception('Sensor order number not saved.');
      }
    }
  }

  /**
  * Update Sensor modal.
  * @return bool — TRUE on success or FALSE on failure.
  * @throws Exception — Return Exception message on error.
  */
  public static function updateSensorModal($post) {
    $pdo = dbConfig::getInstance();
    $Value1onDashboardvar = $Value2onDashboardvar = $Value3onDashboardvar = $Value4onDashboardvar = 0;
    if (isset($post['Value1onDashboard'])) {
      $Value1onDashboardvar = $post['Value1onDashboard'];
    }
    if (isset($post['Value2onDashboard'])) {
      $Value2onDashboardvar = $post['Value2onDashboard'];
    }
    if (isset($post['Value3onDashboard'])) {
      $Value3onDashboardvar = $post['Value3onDashboard'];
    }
    if (isset($post['Value4onDashboard'])) {
      $Value4onDashboardvar = $post['Value4onDashboard'];
    }

    $onDashboardvar = true;
    try {
      if ($post['channel'] == 1) {
        $statement2 = $pdo->prepare("UPDATE sensorconfig SET name=?, description=?, locationOfMeasurement=?, nameValue1=?, Value1GaugeMinValue=?, Value1GaugeMaxValue=?, Value1GaugeRedAreaLowValue=?, Value1GaugeRedAreaLowColor=?, Value1GaugeRedAreaHighValue=?, Value1GaugeRedAreaHighColor=?, Value1GaugeNormalAreaColor=?, Value1onDashboard=?, onDashboard=? WHERE id=?");
        return $statement2->execute(array($post['name'], $post['description'], $post['locationOfMeasurement'], $post['nameValue1'], $post['Value1GaugeMinValue'], $post['Value1GaugeMaxValue'], $post['Value1GaugeRedAreaLowValue'], $post['Value1GaugeRedAreaLowColor'], $post['Value1GaugeRedAreaHighValue'], $post['Value1GaugeRedAreaHighColor'], $post['Value1GaugeNormalAreaColor'], $Value1onDashboardvar, $onDashboardvar, $post['id']));
      } else if ($post['channel'] == 2) {
        $statement2 = $pdo->prepare("UPDATE sensorconfig SET name=?, description=?, locationOfMeasurement=?, nameValue2=?, Value2GaugeMinValue=?, Value2GaugeMaxValue=?, Value2GaugeRedAreaLowValue=?, Value2GaugeRedAreaLowColor=?, Value2GaugeRedAreaHighValue=?, Value2GaugeRedAreaHighColor=?,Value2GaugeNormalAreaColor=?, Value2onDashboard=?, onDashboard=? WHERE id=?");
        return $statement2->execute(array($post['name'], $post['description'], $post['locationOfMeasurement'], $post['nameValue2'], $post['Value2GaugeMinValue'], $post['Value2GaugeMaxValue'], $post['Value2GaugeRedAreaLowValue'], $post['Value2GaugeRedAreaLowColor'], $post['Value2GaugeRedAreaHighValue'], $post['Value2GaugeRedAreaHighColor'], $post['Value2GaugeNormalAreaColor'], $Value2onDashboardvar, $onDashboardvar, $post['id']));
      } else if ($post['channel'] == 3) {
        $statement2 = $pdo->prepare("UPDATE sensorconfig SET name=?, description=?, locationOfMeasurement=?, nameValue3=?, Value3GaugeMinValue=?, Value3GaugeMaxValue=?, Value3GaugeRedAreaLowValue=?, Value3GaugeRedAreaLowColor=?, Value3GaugeRedAreaHighValue=?, Value3GaugeRedAreaHighColor=?,Value3GaugeNormalAreaColor=?, Value3onDashboard=?, onDashboard=? WHERE id=?");
        return $statement2->execute(array($post['name'], $post['description'], $post['locationOfMeasurement'], $post['nameValue3'], $post['Value3GaugeMinValue'], $post['Value3GaugeMaxValue'], $post['Value3GaugeRedAreaLowValue'], $post['Value3GaugeRedAreaLowColor'], $post['Value3GaugeRedAreaHighValue'], $post['Value3GaugeRedAreaHighColor'], $post['Value3GaugeNormalAreaColor'], $Value3onDashboardvar, $onDashboardvar, $post['id']));
      } else if ($post['channel'] == 4) {
        $statement2 = $pdo->prepare("UPDATE sensorconfig SET name=?, description=?, locationOfMeasurement=?, nameValue4=?, Value4GaugeMinValue=?, Value4GaugeMaxValue=?, Value4GaugeRedAreaLowValue=?, Value4GaugeRedAreaLowColor=?, Value4GaugeRedAreaHighValue=?, Value4GaugeRedAreaHighColor=?,Value4GaugeNormalAreaColor=?, Value4onDashboard=?, onDashboard=? WHERE id=?");
        return $statement2->execute(array($post['name'], $post['description'], $post['locationOfMeasurement'], $post['nameValue4'], $post['Value4GaugeMinValue'], $post['Value4GaugeMaxValue'], $post['Value4GaugeRedAreaLowValue'], $post['Value4GaugeRedAreaLowColor'], $post['Value4GaugeRedAreaHighValue'], $post['Value4GaugeRedAreaHighColor'], $post['Value4GaugeNormalAreaColor'], $Value4onDashboardvar, $onDashboardvar, $post['id']));
      } 
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: Sensor not updated successfully.", $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      throw new Exception('Sensor not updated successfully.');
    }
  }

  /**
  * Add new board to user.
  * @return bool — TRUE on success or FALSE on failure.
  * @throws Exception — Return Exception message on error.
  */
  public static function addNewBoardToUser($post, $userid) {
    $pdo = dbConfig::getInstance();
    $return = false;
    if ( ($post['valueType'] == "ttn") && (json_encode($post['inputValue']) != null) ) {
      if (json_encode($post['inputValue']) != null) {
        try {
          $statement2 = $pdo->prepare("SELECT * FROM boardconfig WHERE ttn_dev_id = ?");
          $statement2->execute([$post['inputValue']]);
          $returnBoard = $statement2->fetch();
        } catch (Exception $e) {
          writeToLogFunction::write_to_log("Error: Unable to loads boards.", $_SERVER["SCRIPT_FILENAME"]);
          writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
          throw new Exception('Boards not loaded.');
        }
        
        if ($returnBoard) {
          if ($returnBoard['owner_userid'] == false) {
            try {
              $sql = "UPDATE boardconfig SET owner_userid=? WHERE id=?";
              $return = $pdo->prepare($sql)->execute([$userid, $returnBoard['id']]);
            } catch (Exception $e) {
              writeToLogFunction::write_to_log("Error: Unable to add board to the userid: " . $userid, $_SERVER["SCRIPT_FILENAME"]);
              writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
              throw new Exception('Board not added.');
            }
          } else {
            throw new Exception('Board has an owner.');
          }
        } else {
          throw new Exception('TTN ID not found.');
        }
      } else if ( ($post['valueType'] == "mac")  && (json_encode($post['inputValue']) != null) ) {
        try {
          $statement2 = $pdo->prepare("SELECT * FROM boardconfig WHERE macaddress = ?");
          $statement2->execute([$post['inputValue']]);
          $returnBoard = $statement2->fetch();
        } catch (Exception $e) {
          writeToLogFunction::write_to_log("Error: Unable to load boards.", $_SERVER["SCRIPT_FILENAME"]);
          writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
          throw new Exception('Boards not loaded.');
        }
          if ($returnBoard) {
            if ($returnBoard['owner_userid'] == false) {
              try {
                $sql = "UPDATE boardconfig SET owner_userid=? WHERE id=?";
                $return = $pdo->prepare($sql)->execute([$userid, $returnBoard['id']]);
              } catch (Exception $e) {
                writeToLogFunction::write_to_log("Error: Unable to add board to the userid: " . $userid, $_SERVER["SCRIPT_FILENAME"]);
                writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
                throw new Exception('Board not added.');
              }
            } else {
              throw new Exception('Board has an owner.');
            }
          } else {
            throw new Exception('mac not found.');
          }
      }
    }
    return $return;
  }
}