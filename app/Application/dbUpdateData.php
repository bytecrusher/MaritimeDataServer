<?php
/**
 * class for updating data into DB
 * 
 * @author: Guntmar Hoeche
 * @license: TBD
 */

require_once(__DIR__ . "/../Infrastructure/Database/dbConfig.func.php");
require_once(__DIR__ . "/../Domain/User/user.class.php");
require_once(__DIR__ . "/../Infrastructure/Logging/writeToLogFunction.func.php");

class dbUpdateData {
  private static function normalizeAlertSettings(array $post)
  {
    $alertEnabled = isset($post['AlertEnabled']) ? 1 : 0;
    $alertLowValue = isset($post['AlertLowValue']) && $post['AlertLowValue'] !== '' ? $post['AlertLowValue'] : null;
    $alertHighValue = isset($post['AlertHighValue']) && $post['AlertHighValue'] !== '' ? $post['AlertHighValue'] : null;
    if (!$alertEnabled) {
      $alertLowValue = null;
      $alertHighValue = null;
    }

    return array(
      'alertEnabled' => $alertEnabled,
      'alertLowValue' => $alertLowValue,
      'alertHighValue' => $alertHighValue,
      'gaugeStyle' => $post['GaugeStyle'] ?? 'classic',
    );
  }
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
    $expFormat = mktime(date("H"), date("i"), date("s"), date("m") ,date("d")+1, date("Y"));
		$myDate = date("Y-m-d H:i:s",$expFormat);
    try {
      // TODO rename passwordCodeTime into passwordCodeExpireTime
      $statement = $pdo->prepare("UPDATE users SET passwordCode = :passwordCode, passwordCodeTime = :myDate WHERE id = :userId");
      return $statement->execute(array('passwordCode' => $passwordCode, 'myDate' => $myDate, 'userId' => $userId));
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: User Password reset not successfully saved for user id: " . $userId, $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      throw new Exception('User Password reset update in DB not successfully saved.');
    }
  }

  /**
  * read User Password code (for password reset).
  * @ return bool — TRUE on success or FALSE on failure.
  * @throws Exception — Return Exception message on error.
  */
  public static function readUserPasswordCode($passwordCode, $userId) {
    $pdo = dbConfig::getInstance();
    try {
      $statement = $pdo->prepare("SELECT * FROM users WHERE id = :userId && passwordCode = :passwordCode");
      //$sensortyps = $pdo->prepare("SELECT boardConfig.*, users.email FROM boardConfig, users WHERE offlineDataTimer != 0 && alreadyNotified = 0 && ownerUserId = users.id");
      //return $statement->execute(array('passwordCode' => sha1($passwordCode), 'userId' => $userId));
      //return $statement->execute(array('passwordCode' => $passwordCode, 'userId' => $userId));
      $statement->execute(array('passwordCode' => $passwordCode, 'userId' => $userId));
      //$sensortyps->execute();
      //$SensorData2 = $sensortyps->fetchAll(PDO::FETCH_ASSOC);
      return $statement->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: User Password reset not successfully read for user id: " . $userId, $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      throw new Exception('User Password reset read in DB not successfully.');
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
    $varReceiveNotifications = isset($post['receiveNotifications']) ? (int)$post['receiveNotifications'] : 0;
    $varReceiveOfflineNotifications = isset($post['receiveOfflineNotifications']) ? (int)$post['receiveOfflineNotifications'] : 0;
    $varReceiveSensorNotifications = isset($post['receiveSensorNotifications']) ? (int)$post['receiveSensorNotifications'] : 0;
    try {
      $statement = $pdo->prepare(
        "UPDATE users
         SET receive_notifications = :receive_notifications,
             receive_offline_notifications = :receive_offline_notifications,
             receive_sensor_notifications = :receive_sensor_notifications
         WHERE id = :userId"
      );
      return $statement->execute(array(
        'receive_notifications' => $varReceiveNotifications,
        'receive_offline_notifications' => $varReceiveOfflineNotifications,
        'receive_sensor_notifications' => $varReceiveSensorNotifications,
        'userId' => $userId
      ));
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: User  ReceiveNotifications in DB not successfully updated for user id: " . $userId, $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      throw new Exception('User ReceiveNotifications in DB not successfully updated.');
    }
  }

  public static function updateUserDashboardPreferences($post, $userId) {
    $pdo = dbConfig::getInstance();
    $dashboardUpdateInterval = isset($post['updateInterval']) ? (int)$post['updateInterval'] : 15;
    $dashboardOnlineOnly = isset($post['dashboardOnlineOnly']) ? 1 : 0;
    $preferredChartWindowDays = isset($post['preferredChartWindowDays']) ? (int)$post['preferredChartWindowDays'] : 7;
    if ($dashboardUpdateInterval < 1) {
      $dashboardUpdateInterval = 1;
    }
    if (!in_array($preferredChartWindowDays, array(1, 7, 14, 30), true)) {
      $preferredChartWindowDays = 7;
    }

    try {
      $statement = $pdo->prepare(
        "UPDATE users
         SET dashboardUpdateInterval = :dashboardUpdateInterval,
             dashboardOnlineOnly = :dashboardOnlineOnly,
             preferredChartWindowDays = :preferredChartWindowDays
         WHERE id = :userId"
      );
      return $statement->execute(array(
        'dashboardUpdateInterval' => $dashboardUpdateInterval,
        'dashboardOnlineOnly' => $dashboardOnlineOnly,
        'preferredChartWindowDays' => $preferredChartWindowDays,
        'userId' => $userId
      ));
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: User dashboard preferences in DB not successfully updated for user id: " . $userId, $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      throw new Exception('User dashboard preferences in DB not successfully updated.');
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
  * @return $securityToken
  * bool — TRUE on success or FALSE on failure.
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
    mds_set_remember_login_cookies($identifier, $securityToken);
    //return true;
    return $securityToken;
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
    try {
      $statement2 = $pdo->prepare("UPDATE sensorConfig SET name=?, description=?, typId=?, locationOfMeasurement=?, NrOfUsedSensors=?, onDashboard=? WHERE id=?");
      $returnStatement = $statement2->execute(array($post['name'], $post['description'], $post['typId'], $post['locationOfMeasurement'], $post['NrOfUsedSensors'], $post['onDashboard'], $post['id']));
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: Sensor not updated successfully.", $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      throw new Exception('Sensor not updated successfully.');
    }
    for ($i = 1; $i <= $post['NrOfUsedSensors']; $i++) {
      if (!isset($post['Value' . $i . 'onDashboard'])) {
        $myVar['onDashboard'] = 0;
      } else {
        $myVar['onDashboard'] = 1;
      }
      $myVar['id'] = $post['id'];
      //$myVar['name'] = $post['nameValue' . $i];
      $myVar['name'] = $post['name'];
      try {
        writeToLogFunction::write_to_log("updateSensor Channel nr: " . $i, $_SERVER["SCRIPT_FILENAME"]);
        $statement2 = $pdo->prepare("UPDATE sensorChannelConfig  SET onDashboard=? ,name=? WHERE sensorConfigId=? AND channelNr=?");
        $statement2->execute(array($myVar['onDashboard'], $myVar['name'], $myVar['id'], $i));
      } catch (PDOException $e) {
        writeToLogFunction::write_to_log("Error: Sensor not updated successfully.", $_SERVER["SCRIPT_FILENAME"]);
        writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
        throw new Exception('Sensor not updated successfully.');
      }
    }
    return $returnStatement;
  }

    /**
  * Update Sensor channels.
  * @return bool — TRUE on success or FALSE on failure.
  * @throws Exception — Return Exception message on error.
  */
  public static function updateSensorChannels($post) {
    $pdo = dbConfig::getInstance();
    if (!isset($post['onDashboard'])) {
      $post['onDashboard'] = 0;
    }
    try { // todo check channelNr and sensorConfigId
      $statement2 = $pdo->prepare("UPDATE sensorChannelConfig  SET sensorConfigId=?, name=?, channelNr=?, locationOfMeasurement=?, onDashboard=? WHERE id=?");
      $channelNr = $post['channelNr'] ?? ($post['channel'] ?? 1);
      return $statement2->execute(array($post['sensorConfigId'], $post['name'], $channelNr, $post['locationOfMeasurement'], $post['onDashboard'], $post['id']));
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
        $statement2 = $pdo->prepare("UPDATE sensorChannelConfig SET DashboardOrderNr=? WHERE sensorConfigId=? AND channelNr=?");
  	    return $statement2->execute(array($post['orderNumber'], $post['id'], $post['channel']));
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
    $onDashboardVar = isset($post['onDashboard']) ? 1 : 0;
    $channelNr = (int)($post['channel'] ?? 1);
    $alertSettings = self::normalizeAlertSettings($post);
    try {
      $statementSensor = $pdo->prepare("UPDATE sensorConfig SET name=?, description=?, locationOfMeasurement=?, onDashboard=? WHERE id=?");
      $statementSensor->execute(array(
        $post['name'],
        $post['description'],
        $post['locationOfMeasurement'],
        $onDashboardVar,
        $post['id']
      ));

      $statementChannel = $pdo->prepare("UPDATE sensorChannelConfig SET name=?, GaugeMinValue=?, GaugeMaxValue=?, GaugeRedAreaLowValue=?, GaugeRedAreaLowColor=?, GaugeRedAreaHighValue=?, GaugeRedAreaHighColor=?, GaugeNormalAreaColor=?, GaugeStyle=?, AlertEnabled=?, AlertLowValue=?, AlertHighValue=?, onDashboard=?, ChartColor=? WHERE sensorConfigId=? AND channelNr=?");
      return $statementChannel->execute(array(
        $post['nameValue' . $channelNr] ?? ($post['nameValue'] ?? $post['name']),
        $post['Value' . $channelNr . 'GaugeMinValue'] ?? $post['GaugeMinValue'],
        $post['Value' . $channelNr . 'GaugeMaxValue'] ?? $post['GaugeMaxValue'],
        $post['Value' . $channelNr . 'GaugeRedAreaLowValue'] ?? $post['GaugeRedAreaLowValue'],
        $post['Value' . $channelNr . 'GaugeRedAreaLowColor'] ?? $post['GaugeRedAreaLowColor'],
        $post['Value' . $channelNr . 'GaugeRedAreaHighValue'] ?? $post['GaugeRedAreaHighValue'],
        $post['Value' . $channelNr . 'GaugeRedAreaHighColor'] ?? $post['GaugeRedAreaHighColor'],
        $post['Value' . $channelNr . 'GaugeNormalAreaColor'] ?? $post['GaugeNormalAreaColor'],
        $alertSettings['gaugeStyle'],
        $alertSettings['alertEnabled'],
        $alertSettings['alertLowValue'],
        $alertSettings['alertHighValue'],
        $onDashboardVar,
        $post['ChartColor'],
        $post['id'],
        $channelNr
      ));
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: Sensor not updated successfully.", $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      throw new Exception('Sensor not updated successfully.');
    }
  }

    /**
  * Update Sensor Channel modal.
  * @return bool — TRUE on success or FALSE on failure.
  * @throws Exception — Return Exception message on error.
  */
  public static function updateSensorChannelModal($post) {
    $pdo = dbConfig::getInstance();

    $onDashboardVar = 1;
    if (!isset($post['onDashboard'])) {
      $onDashboardVar = 0;
    }
    $alertSettings = self::normalizeAlertSettings($post);
    try {
      $statement2 = $pdo->prepare("UPDATE sensorChannelConfig SET name=?, GaugeMinValue=?, GaugeMaxValue=?, GaugeRedAreaLowValue=?, GaugeRedAreaLowColor=?, GaugeRedAreaHighValue=?, GaugeRedAreaHighColor=?, GaugeNormalAreaColor=?, GaugeStyle=?, AlertEnabled=?, AlertLowValue=?, AlertHighValue=?, onDashboard=?, ChartColor=? WHERE sensorConfigId=? AND channelNr=?");
      return $statement2->execute(array($post['nameValue'], $post['GaugeMinValue'], $post['GaugeMaxValue'], $post['GaugeRedAreaLowValue'], $post['GaugeRedAreaLowColor'], $post['GaugeRedAreaHighValue'], $post['GaugeRedAreaHighColor'], $post['GaugeNormalAreaColor'], $alertSettings['gaugeStyle'], $alertSettings['alertEnabled'], $alertSettings['alertLowValue'], $alertSettings['alertHighValue'], $onDashboardVar, $post['ChartColor'], $post['id'], $post['channel']));
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
