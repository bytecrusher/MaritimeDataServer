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

  private static function tableColumnExists(PDO $pdo, $tableName, $columnName)
  {
    $statement = $pdo->prepare(
      "SELECT COUNT(*) AS columnCount
       FROM INFORMATION_SCHEMA.COLUMNS
       WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME = :tableName
         AND COLUMN_NAME = :columnName"
    );
    $statement->execute(array(
      'tableName' => $tableName,
      'columnName' => $columnName,
    ));
    $row = $statement->fetch(PDO::FETCH_ASSOC);
    return ((int)($row['columnCount'] ?? 0) > 0);
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

  public static function updateUserLanguage($language, $userId) {
    $pdo = dbConfig::getInstance();
    $language = mds_normalize_language($language);
    try {
      $statement = $pdo->prepare("UPDATE users SET language = :language, updatedAt=NOW() WHERE id = :userId");
      return $statement->execute(array('language' => $language, 'userId' => $userId ));
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: Language not successfully saved for user id: " . $userId, $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      throw new Exception('Language not successfully saved.');
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
      $passwordCodeValue = ($passwordCode === null || $passwordCode === '') ? null : hash('sha256', (string)$passwordCode);
      $passwordCodeTimeValue = $passwordCodeValue === null ? null : $myDate;
      return $statement->execute(array('passwordCode' => $passwordCodeValue, 'myDate' => $passwordCodeTimeValue, 'userId' => $userId));
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
      $statement = $pdo->prepare("SELECT * FROM users WHERE id = :userId AND (passwordCode = :passwordCodeHash OR passwordCode = :passwordCodeLegacy)");
      $statement->execute(array('passwordCodeHash' => hash('sha256', (string)$passwordCode), 'passwordCodeLegacy' => $passwordCode, 'userId' => $userId));
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
    $updated = false;
    foreach($post['active'] as $i=>$array_wert)
		{
      try {
        $statement = $pdo->prepare("UPDATE users SET active =?, userGroupAdmin=? WHERE id =?");
        $statement->execute(array($post['active'][$i], $post['userGroupAdmin'][$i], $i ));
        self::syncUserGlobalRole($pdo, (int)$i, (int)$post['userGroupAdmin'][$i]);
        $updated = true;
      } catch (PDOException $e) {
        writeToLogFunction::write_to_log("Error: User Status in DB not successfully updated for user id: " . $post['active'][$i], $_SERVER["SCRIPT_FILENAME"]);
        writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
        throw new Exception('User Status in DB not successfully updated.');
      }
		}
    return $updated;
  }

  private static function syncUserGlobalRole(PDO $pdo, $userId, $isAdmin) {
    if (!self::tableExists($pdo, 'roles') || !self::tableExists($pdo, 'user_roles')) {
      return;
    }

    $roleName = ((int)$isAdmin === 1) ? 'admin' : 'user';
    $cleanup = $pdo->prepare(
      "DELETE user_roles
       FROM user_roles
       INNER JOIN roles ON roles.id = user_roles.roleId
       WHERE user_roles.userId = ?
         AND roles.name IN ('admin', 'user')"
    );
    $cleanup->execute(array((int)$userId));

    $insert = $pdo->prepare(
      "INSERT IGNORE INTO user_roles (userId, roleId)
       SELECT ?, id FROM roles WHERE name = ? LIMIT 1"
    );
    $insert->execute(array((int)$userId, $roleName));
  }

  private static function tableExists(PDO $pdo, $tableName) {
    try {
      $statement = $pdo->prepare(
        "SELECT COUNT(*) AS tableCount
         FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = ?"
      );
      $statement->execute(array((string)$tableName));
      $row = $statement->fetch(PDO::FETCH_ASSOC);
      return ((int)($row['tableCount'] ?? 0) > 0);
    } catch (Throwable $e) {
      return false;
    }
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
      $setClauses = array("receive_notifications = :receive_notifications");
      $params = array(
        'receive_notifications' => $varReceiveNotifications,
        'userId' => $userId,
      );

      if (self::tableColumnExists($pdo, 'users', 'receive_offline_notifications')) {
        $setClauses[] = "receive_offline_notifications = :receive_offline_notifications";
        $params['receive_offline_notifications'] = $varReceiveOfflineNotifications;
      }

      if (self::tableColumnExists($pdo, 'users', 'receive_sensor_notifications')) {
        $setClauses[] = "receive_sensor_notifications = :receive_sensor_notifications";
        $params['receive_sensor_notifications'] = $varReceiveSensorNotifications;
      }

      if (count($setClauses) < 3) {
        writeToLogFunction::warning(
          'Notification preference save is using legacy users schema. Run notification_and_gauge_style_idempotent migration.',
          $_SERVER["SCRIPT_FILENAME"],
          array('userId' => $userId, 'updatedColumns' => $setClauses)
        );
      }

      $statement = $pdo->prepare("UPDATE users SET " . implode(', ', $setClauses) . " WHERE id = :userId");
      return $statement->execute($params);
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
    $eventTimelineWindowHours = isset($post['eventTimelineWindowHours']) ? (int)$post['eventTimelineWindowHours'] : 24;
    if ($dashboardUpdateInterval < 1) {
      $dashboardUpdateInterval = 1;
    }
    if (!in_array($preferredChartWindowDays, array(1, 7, 14, 30), true)) {
      $preferredChartWindowDays = 7;
    }
    if (!in_array($eventTimelineWindowHours, array(3, 6, 12, 24, 48, 72), true)) {
      $eventTimelineWindowHours = 24;
    }

    try {
      $setClauses = array(
        "dashboardUpdateInterval = :dashboardUpdateInterval",
        "dashboardOnlineOnly = :dashboardOnlineOnly",
        "preferredChartWindowDays = :preferredChartWindowDays",
      );
      $params = array(
        'dashboardUpdateInterval' => $dashboardUpdateInterval,
        'dashboardOnlineOnly' => $dashboardOnlineOnly,
        'preferredChartWindowDays' => $preferredChartWindowDays,
        'userId' => $userId
      );

      if (self::tableColumnExists($pdo, 'users', 'eventTimelineWindowHours')) {
        $setClauses[] = "eventTimelineWindowHours = :eventTimelineWindowHours";
        $params['eventTimelineWindowHours'] = $eventTimelineWindowHours;
      }

      $statement = $pdo->prepare("UPDATE users SET " . implode(', ', $setClauses) . " WHERE id = :userId");
      return $statement->execute($params);
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
      $insert->execute(array('userId' => $userId, 'identifier' => $identifier, 'securityToken' => myFunctions::hashSecurityToken($securityToken)));
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

  public static function deleteBoard($boardId) {
    $pdo = dbConfig::getInstance();
    $boardId = (int)$boardId;
    if ($boardId <= 0) {
      throw new InvalidArgumentException('A valid board ID is required.');
    }

    $deletionCounts = array();
    try {
      $pdo->beginTransaction();

      $boardStatement = $pdo->prepare("SELECT id, name, macAddress FROM boardConfig WHERE id = ? FOR UPDATE");
      $boardStatement->execute(array($boardId));
      $board = $boardStatement->fetch(PDO::FETCH_ASSOC);
      if (!$board) {
        throw new RuntimeException('Board does not exist.');
      }

      $sensorStatement = $pdo->prepare("SELECT id FROM sensorConfig WHERE boardId = ?");
      $sensorStatement->execute(array($boardId));
      $sensorIds = array_map('intval', $sensorStatement->fetchAll(PDO::FETCH_COLUMN));

      if (!empty($sensorIds)) {
        $sensorPlaceholders = implode(',', array_fill(0, count($sensorIds), '?'));
        foreach (array('sensor_permissions', 'sensorData', 'sensorChannelConfig') as $tableName) {
          if (($tableName === 'sensor_permissions') && !self::tableExists($pdo, $tableName)) {
            continue;
          }
          $foreignKeyColumn = $tableName === 'sensorChannelConfig' ? 'sensorConfigId' : 'sensorId';
          $deleteStatement = $pdo->prepare("DELETE FROM `" . $tableName . "` WHERE `" . $foreignKeyColumn . "` IN (" . $sensorPlaceholders . ")");
          $deleteStatement->execute($sensorIds);
          $deletionCounts[$tableName] = $deleteStatement->rowCount();
        }
      }

      $deleteSensors = $pdo->prepare("DELETE FROM sensorConfig WHERE boardId = ?");
      $deleteSensors->execute(array($boardId));
      $deletionCounts['sensorConfig'] = $deleteSensors->rowCount();

      if (self::tableExists($pdo, 'board_permissions')) {
        $deletePermissions = $pdo->prepare("DELETE FROM board_permissions WHERE boardId = ?");
        $deletePermissions->execute(array($boardId));
        $deletionCounts['board_permissions'] = $deletePermissions->rowCount();
      }

      $deleteBoard = $pdo->prepare("DELETE FROM boardConfig WHERE id = ?");
      $deleteBoard->execute(array($boardId));
      if ($deleteBoard->rowCount() !== 1) {
        throw new RuntimeException('Board row was not deleted.');
      }

      $pdo->commit();
      writeToLogFunction::info('Board and dependent data deleted.', __FILE__, array(
        'boardId' => $boardId,
        'boardName' => $board['name'] ?? null,
        'macAddress' => $board['macAddress'] ?? null,
        'deletedRows' => $deletionCounts,
      ));
      return true;
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      writeToLogFunction::error('Board deletion transaction failed.', __FILE__, array(
        'boardId' => $boardId,
        'deletedRowsBeforeRollback' => $deletionCounts,
        'error' => $e->getMessage(),
      ));
      throw new RuntimeException('Board could not be deleted.', 0, $e);
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
    $sensorId = isset($post['id']) ? (int)$post['id'] : 0;
    $channelNr = isset($post['channel']) ? (int)$post['channel'] : 0;
    $orderNumber = isset($post['orderNumber']) ? (int)$post['orderNumber'] : 0;
    if ($sensorId <= 0 || $channelNr <= 0) {
      return false;
    }
    try {
      $statement2 = $pdo->prepare("UPDATE sensorChannelConfig SET DashboardOrderNr=? WHERE sensorConfigId=? AND channelNr=?");
      return $statement2->execute(array($orderNumber, $sensorId, $channelNr));
    } catch (PDOException $e) {
      writeToLogFunction::write_to_log("Error: Sensor order number not saved.", $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      throw new Exception('Sensor order number not saved.');
    }
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
