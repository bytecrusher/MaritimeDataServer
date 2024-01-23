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
  public static function updateUserData($post, $userId) {
    $pdo = dbConfig::getInstance();
    $firstName = trim($post['firstName']);
    $lastName = trim($post['lastName']);
    if($firstName == "" || $lastName == "") {
      throw new Exception('First name and last name shall not be empty for user id: ' . $userId);
    } else {
      try {
        $statement = $pdo->prepare("UPDATE users SET firstName = :firstName, lastName = :lastName, updatedAt=NOW() WHERE id = :userId");
        $result = $statement->execute(array('firstName' => $firstName, 'lastName'=> $lastName, 'userId' => $userId ));
        return $result;
      } catch (PDOException $e) {
        writeToLogFunction::write_to_log("Error: User Data not saved for userId: " . $userId, $_SERVER["SCRIPT_FILENAME"]);
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
  public static function updateUserTimeZoneData($post, $userId) {
    $pdo = dbConfig::getInstance();
    $Timezone = trim($post['Timezone']);
    if($Timezone == "") {
      throw new Exception('Timezone in function updateUserTimeZoneData shall not be empty for user: ' . $userId);
    } else {
      try {
        $statement = $pdo->prepare("UPDATE users SET Timezone = :Timezone, updatedAt=NOW() WHERE id = :userId");
        $result = $statement->execute(array('Timezone' => $Timezone, 'userId' => $userId ));
        return $result;
      } catch (PDOException $e) {
        writeToLogFunction::write_to_log("Error: Timezone not successfully saved for user id: " . $userId, $_SERVER["SCRIPT_FILENAME"]);
        writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
        throw new Exception('Timezone not successfully saved.');
      }
    }
  }

  /**
  * Update User Email Address.
  * @return bool — TRUE on success or FALSE on failure.
  * @throws Exception — Return Exception message on error.
  */
  public static function updateUserMail($post, $userId) {
    $pdo = dbConfig::getInstance();
    $email = trim($post['email']);
    try {
      $statement = $pdo->prepare("UPDATE users SET email = :email WHERE id = :userId");
      $result = $statement->execute(array('email' => $email, 'userId' => $userId ));
      return $result;
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: User Mail not successfully saved for user id: " . $userId, $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      throw new Exception('User Mail not successfully saved.');
    }
  }

  /**
  * Update User Password.
  * @return bool — TRUE on success or FALSE on failure.
  * @throws Exception — Return Exception message on error.
  */
  public static function updateUserPassword($password_hash, $userId) {
    $pdo = dbConfig::getInstance();
    try {
      $statement = $pdo->prepare("UPDATE users SET password = :password WHERE id = :userId");
      $result = $statement->execute(array('password' => $password_hash, 'userId' => $userId ));
      return $result;
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: User Password not successfully saved for user id: " . $userId, $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      throw new Exception('User Password not successfully saved.');
    }
  }

  /**
  * Update User Password code (for password reset).
  * @return bool — TRUE on success or FALSE on failure.
  * @throws Exception — Return Exception message on error.
  */
  public static function updateUserPasswordCode($passwordCode, $userId) {
    $pdo = dbConfig::getInstance();
    try {
      $statement = $pdo->prepare("UPDATE users SET passwordCode = :passwordCode, passwordCodeTime = NOW() WHERE id = :userId");
      return $statement->execute(array('passwordCode' => sha1($passwordCode), 'userId' => $userId));
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: User Password reset not successfully saved for user id: " . $userId, $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      throw new Exception('User Password reset update in DB not successfully saved.');
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
        $statement = $pdo->prepare("UPDATE users SET active =?, userGroupAdmin=? WHERE id =?");
        $statement->execute(array($post['active'][$i], $post['userGroupAdmin'][$i], $i ));
        return true;
      } catch (PDOException $e) {
        writeToLogFunction::write_to_log("Error: User Status in DB not successfully updated for user id: " . $post['active'][$i], $_SERVER["SCRIPT_FILENAME"]);
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
      $pdoResult = $statement->execute(array($userId));
      $changedRows = $statement->rowCount();
      if($changedRows == 1 ) {
        return true;
      } else {
        return false;
      }
        
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: User Status in DB not successfully updated for userId: " . $userId, $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      throw new Exception('User Status in DB not successfully updated.');
    }
  }

  /**
  * Update User Dashboard update interval.
  * @return bool — TRUE on success or FALSE on failure.
  * @throws Exception — Return Exception message on error.
  */
  public static function updateUserDashboardUpdateInterval($post, $userId) {
    $pdo = dbConfig::getInstance();
    $dashboardUpdateInterval = ($post['updateInterval']);
    try {
      $statement = $pdo->prepare("UPDATE users SET dashboardUpdateInterval = :dashboardUpdateInterval WHERE id = :userId");
      return $statement->execute(array('dashboardUpdateInterval' => $dashboardUpdateInterval, 'userId' => $userId ));
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: User Dashboard Update Interval in DB not successfully updated for user id: " . $userId, $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      throw new Exception('User Dashboard Update Interval in DB not successfully updated.');
    }
  }

  /**
  * Update User Dashboard updateUserReceiveNotifications.
  * @return bool — TRUE on success or FALSE on failure.
  * @throws Exception — Return Exception message on error.
  */
  public static function updateUserReceiveNotifications($post, $userId) {
    $pdo = dbConfig::getInstance();
    $varReceiveNotifications = ($post['receiveNotifications']);
    try {
      $statement = $pdo->prepare("UPDATE users SET receive_notifications = :receive_notifications WHERE id = :userId");
      return $statement->execute(array('receive_notifications' => $varReceiveNotifications, 'userId' => $userId ));
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: User  ReceiveNotifications in DB not successfully updated for user id: " . $userId, $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      throw new Exception('User ReceiveNotifications in DB not successfully updated.');
    }
  }

  /**
  * Insert User.
  * @return bool — TRUE on success or FALSE on failure.
  * @throws Exception — Return Exception message on error.
  */
  public static function insertUser($email, $password_hash, $firstName, $lastName) {
    $pdo = dbConfig::getInstance();
    try {
      $statement = $pdo->prepare("INSERT INTO users (email, password, firstName, lastName) VALUES (:email, :password, :firstName, :lastName)");
      //return $statement->execute(array('email' => $email, 'password' => $password_hash, 'firstName' => $firstName, 'lastname' => $lastName));
      $statement->execute(array('email' => $email, 'password' => $password_hash, 'firstName' => $firstName, 'lastName' => $lastName));
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
  public static function insertAdmin($email, $password_hash, $firstName, $lastName) {
    $pdo = dbConfig::getInstance();
    try {
      $statement = $pdo->prepare("INSERT INTO users (email, password, firstName, lastName, userGroupAdmin, active ) VALUES (:email, :password, :firstName, :lastName, :userGroupAdmin, :active )");
      return $statement->execute(array('email' => $email, 'password' => $password_hash, 'firstName' => $firstName, 'lastName' => $lastName, 'userGroupAdmin' => '1', 'active' => '1'));
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: Admin not inserted successfully for email: " . $email, $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      throw new Exception('Admin not inserted successfully.');
    }
  }

  /**
  * Insert Security token.
  * @return bool — TRUE on success or FALSE on failure.
  * @throws Exception — Return Exception message on error.
  */
  public static function insertSecurityToken($userId) {
    $pdo = dbConfig::getInstance();
    $identifier = myFunctions::random_string();
    $securityToken = myFunctions::random_string();
    try {
      $insert = $pdo->prepare("INSERT INTO securityTokens (userId, identifier, securityToken) VALUES (:userId, :identifier, :securityToken)");
      $insert->execute(array('userId' => $userId, 'identifier' => $identifier, 'securityToken' => sha1($securityToken)));
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: insertSecurityToken not inserted successfully for user id: " . $userId, $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      throw new Exception('insertSecurityToken not inserted successfully.');
    }
    setcookie("identifier",$identifier,time()+(3600*24*365)); //Valid for 1 year
    setcookie("securityToken",$securityToken,time()+(3600*24*365)); //Valid for 1 year
    return true;
  }

  /**
  * Update Board.
  * @return bool — TRUE on success or FALSE on failure.
  * @throws Exception — Return Exception message on error.
  */
  public static function updateBoard($post) {
    $pdo = dbConfig::getInstance();
   	 if (!isset($post['performUpdate'])) {
   		$performUpdate = 0;
   	 } else {
   		$performUpdate = 1;
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
    if ($post['ownerId'] == "") {
      $post['ownerId'] = null;
    }
    if ($post['updateDataTimer'] == "") {
      $post['updateDataTimer'] = 15;
    }
    if ($post['offlineDataTimer'] == "") {
      $post['offlineDataTimer'] = 15;
    }
    try {
      $statement2 = $pdo->prepare("UPDATE boardConfig SET name=?, location=?, ownerUserId=?, description=?, ttnAppId=?, ttnDevId=?, performUpdate=?, alarmOnUnavailable=?, onDashboard=?, updateDataTimer=?, offlineDataTimer=? WHERE id=?");
      return $statement2->execute(array($post['name'], $post['location'], $post['ownerId'], $post['description'], $post['ttnAppId'], $post['ttnDevId'], $performUpdate, $alarmOnUnavailable, $onDashboard, $post['updateDataTimer'], $post['offlineDataTimer'], $post['id']));
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: Board not updated successfully for user id: " . $post['ownerId'], $_SERVER["SCRIPT_FILENAME"]);
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
   	 if (!isset($post['performUpdate'])) {
   		$performUpdate = 0;
   	 } else {
   		$performUpdate = 1;
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
    if ($post['ownerId'] == "") {
      $post['ownerId'] = null;
    }
    try {
      //$statement2 = $pdo->prepare("UPDATE boardConfig SET name=?, location=?, ownerUserId=?, description=?, ttnAppId=?, ttnDevId=?, performUpdate=?, alarmOnUnavailable=?, onDashboard=?, updateDataTimer=? WHERE id=?");

      $statement2 = $pdo->prepare("UPDATE boardConfig SET ownerUserId=NULL WHERE id=?");

      return $statement2->execute(array($post['id']));
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: Board not updated successfully for user id: " . $post['ownerId'], $_SERVER["SCRIPT_FILENAME"]);
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
    $Value1onDashboardVar = $Value2onDashboardVar = $Value3onDashboardVar = $Value4onDashboardVar = 0;
    if (isset($post['Value1onDashboard'])) {
      $Value1onDashboardVar = $post['Value1onDashboard'];
    }
    if (isset($post['Value2onDashboard'])) {
      $Value2onDashboardVar = $post['Value2onDashboard'];
    }
    if (isset($post['Value3onDashboard'])) {
      $Value3onDashboardVar = $post['Value3onDashboard'];
    }
    if (isset($post['Value4onDashboard'])) {
      $Value4onDashboardVar = $post['Value4onDashboard'];
    }

    try {
      $statement2 = $pdo->prepare("UPDATE sensorConfig SET name=?, description=?, typId=?, locationOfMeasurement=?, nameValue1=?, Value1onDashboard=?, nameValue2=?, Value2onDashboard=?, nameValue3=?, Value3onDashboard=?, nameValue4=?, Value4onDashboard=?, NrOfUsedSensors=?, onDashboard=? WHERE id=?");
      return $statement2->execute(array($post['name'], $post['description'], $post['typId'], $post['locationOfMeasurement'], $post['nameValue1'], $Value1onDashboardVar, $post['nameValue2'], $Value2onDashboardVar, $post['nameValue3'], $Value3onDashboardVar, $post['nameValue4'], $Value4onDashboardVar, $post['NrOfUsedSensors'], $post['onDashboard'], $post['id']));
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
        $statement2 = $pdo->prepare("UPDATE sensorConfig SET Value" . $post['channel'] . "DashboardOrderNr=? WHERE id=?");
  	    return $statement2->execute(array($post['orderNumber'], $post['id']));
      } catch (PDOException $e) {
        writeToLogFunction::write_to_log("Error: Sensor order number not saved.", $_SERVER["SCRIPT_FILENAME"]);
        writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
        throw new Exception('Sensor order number not saved.');
      }
    }
    return true;
  }

  /**
  * Update Sensor modal.
  * @return bool — TRUE on success or FALSE on failure.
  * @throws Exception — Return Exception message on error.
  */
  public static function updateSensorModal($post) {
    $pdo = dbConfig::getInstance();
    $Value1onDashboardVar = $Value2onDashboardVar = $Value3onDashboardVar = $Value4onDashboardVar = 0;
    if (isset($post['Value1onDashboard'])) {
      $Value1onDashboardVar = $post['Value1onDashboard'];
    }
    if (isset($post['Value2onDashboard'])) {
      $Value2onDashboardVar = $post['Value2onDashboard'];
    }
    if (isset($post['Value3onDashboard'])) {
      $Value3onDashboardVar = $post['Value3onDashboard'];
    }
    if (isset($post['Value4onDashboard'])) {
      $Value4onDashboardVar = $post['Value4onDashboard'];
    }

    $onDashboardVar = true;
    try {
      if ($post['channel'] == 1) {
        $statement2 = $pdo->prepare("UPDATE sensorConfig SET name=?, description=?, locationOfMeasurement=?, nameValue1=?, Value1GaugeMinValue=?, Value1GaugeMaxValue=?, Value1GaugeRedAreaLowValue=?, Value1GaugeRedAreaLowColor=?, Value1GaugeRedAreaHighValue=?, Value1GaugeRedAreaHighColor=?, Value1GaugeNormalAreaColor=?, Value1onDashboard=?, onDashboard=? WHERE id=?");
        return $statement2->execute(array($post['name'], $post['description'], $post['locationOfMeasurement'], $post['nameValue1'], $post['Value1GaugeMinValue'], $post['Value1GaugeMaxValue'], $post['Value1GaugeRedAreaLowValue'], $post['Value1GaugeRedAreaLowColor'], $post['Value1GaugeRedAreaHighValue'], $post['Value1GaugeRedAreaHighColor'], $post['Value1GaugeNormalAreaColor'], $Value1onDashboardVar, $onDashboardVar, $post['id']));
      } else if ($post['channel'] == 2) {
        $statement2 = $pdo->prepare("UPDATE sensorConfig SET name=?, description=?, locationOfMeasurement=?, nameValue2=?, Value2GaugeMinValue=?, Value2GaugeMaxValue=?, Value2GaugeRedAreaLowValue=?, Value2GaugeRedAreaLowColor=?, Value2GaugeRedAreaHighValue=?, Value2GaugeRedAreaHighColor=?,Value2GaugeNormalAreaColor=?, Value2onDashboard=?, onDashboard=? WHERE id=?");
        return $statement2->execute(array($post['name'], $post['description'], $post['locationOfMeasurement'], $post['nameValue2'], $post['Value2GaugeMinValue'], $post['Value2GaugeMaxValue'], $post['Value2GaugeRedAreaLowValue'], $post['Value2GaugeRedAreaLowColor'], $post['Value2GaugeRedAreaHighValue'], $post['Value2GaugeRedAreaHighColor'], $post['Value2GaugeNormalAreaColor'], $Value2onDashboardVar, $onDashboardVar, $post['id']));
      } else if ($post['channel'] == 3) {
        $statement2 = $pdo->prepare("UPDATE sensorConfig SET name=?, description=?, locationOfMeasurement=?, nameValue3=?, Value3GaugeMinValue=?, Value3GaugeMaxValue=?, Value3GaugeRedAreaLowValue=?, Value3GaugeRedAreaLowColor=?, Value3GaugeRedAreaHighValue=?, Value3GaugeRedAreaHighColor=?,Value3GaugeNormalAreaColor=?, Value3onDashboard=?, onDashboard=? WHERE id=?");
        return $statement2->execute(array($post['name'], $post['description'], $post['locationOfMeasurement'], $post['nameValue3'], $post['Value3GaugeMinValue'], $post['Value3GaugeMaxValue'], $post['Value3GaugeRedAreaLowValue'], $post['Value3GaugeRedAreaLowColor'], $post['Value3GaugeRedAreaHighValue'], $post['Value3GaugeRedAreaHighColor'], $post['Value3GaugeNormalAreaColor'], $Value3onDashboardVar, $onDashboardVar, $post['id']));
      } else if ($post['channel'] == 4) {
        $statement2 = $pdo->prepare("UPDATE sensorConfig SET name=?, description=?, locationOfMeasurement=?, nameValue4=?, Value4GaugeMinValue=?, Value4GaugeMaxValue=?, Value4GaugeRedAreaLowValue=?, Value4GaugeRedAreaLowColor=?, Value4GaugeRedAreaHighValue=?, Value4GaugeRedAreaHighColor=?,Value4GaugeNormalAreaColor=?, Value4onDashboard=?, onDashboard=? WHERE id=?");
        return $statement2->execute(array($post['name'], $post['description'], $post['locationOfMeasurement'], $post['nameValue4'], $post['Value4GaugeMinValue'], $post['Value4GaugeMaxValue'], $post['Value4GaugeRedAreaLowValue'], $post['Value4GaugeRedAreaLowColor'], $post['Value4GaugeRedAreaHighValue'], $post['Value4GaugeRedAreaHighColor'], $post['Value4GaugeNormalAreaColor'], $Value4onDashboardVar, $onDashboardVar, $post['id']));
      } 
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: Sensor not updated successfully.", $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      throw new Exception('Sensor not updated successfully.');
    }
    return true;
  }

  /**
  * Add new board to user.
  * @return bool — TRUE on success or FALSE on failure.
  * @throws Exception — Return Exception message on error.
  */
  public static function addNewBoardToUser($post, $userId) {
    $pdo = dbConfig::getInstance();
    $return = false;
    if ( ($post['valueType'] == "ttn") && (json_encode($post['inputValue']) != null) ) {
      if (json_encode($post['inputValue']) != null) {
        try {
          $statement2 = $pdo->prepare("SELECT * FROM boardConfig WHERE ttnDevId = ?");
          $statement2->execute([$post['inputValue']]);
          $returnBoard = $statement2->fetch();
        } catch (Exception $e) {
          writeToLogFunction::write_to_log("Error: Unable to loads boards.", $_SERVER["SCRIPT_FILENAME"]);
          writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
          throw new Exception('Boards not loaded.');
        }
        
        if ($returnBoard) {
          if ($returnBoard['ownerUserId'] == false) {
            try {
              $sql = "UPDATE boardConfig SET ownerUserId=? WHERE id=?";
              $return = $pdo->prepare($sql)->execute([$userId, $returnBoard['id']]);
            } catch (Exception $e) {
              writeToLogFunction::write_to_log("Error: Unable to add board to the user id: " . $userId, $_SERVER["SCRIPT_FILENAME"]);
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
          $statement2 = $pdo->prepare("SELECT * FROM boardConfig WHERE macAddress = ?");
          $statement2->execute([$post['inputValue']]);
          $returnBoard = $statement2->fetch();
        } catch (Exception $e) {
          writeToLogFunction::write_to_log("Error: Unable to load boards.", $_SERVER["SCRIPT_FILENAME"]);
          writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
          throw new Exception('Boards not loaded.');
        }
          if ($returnBoard) {
            if ($returnBoard['ownerUserId'] == false) {
              try {
                $sql = "UPDATE boardConfig SET ownerUserId=? WHERE id=?";
                $return = $pdo->prepare($sql)->execute([$userId, $returnBoard['id']]);
              } catch (Exception $e) {
                writeToLogFunction::write_to_log("Error: Unable to add board to the user id: " . $userId, $_SERVER["SCRIPT_FILENAME"]);
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