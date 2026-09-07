<?php
/**
 * a set of functions for handle some user and board functions.
 *
 * @author: Guntmar Höche
 * @license: TBD
 */

require_once(__DIR__ . '/../Support/password.func.php');
require_once(__DIR__ . '/../Infrastructure/Database/dbConfig.func.php');
require_once(__DIR__ . '/../Infrastructure/Config/configuration.php');
require_once(__DIR__ . '/../Infrastructure/Logging/writeToLogFunction.func.php');
require_once(__DIR__ . '/../Support/RandomColor.php');
require_once(__DIR__ . '/SensorGroupPolicy.php');
require_once(__DIR__ . '/TemperatureReading.php');
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
		if (function_exists('random_bytes')) {
			return bin2hex(random_bytes(32));
		}
		if (function_exists('openssl_random_pseudo_bytes')) {
			$strong = false;
			$bytes = openssl_random_pseudo_bytes(32, $strong);
			if ($bytes !== false && $strong === true) {
				return bin2hex($bytes);
			}
		}
		throw new RuntimeException('No secure random generator available.');
	}

	public static function hashSecurityToken($securityToken) {
		$secret = (string)(configuration::$md5secretString ?? '');
		if ($secret !== '') {
			return hash_hmac('sha256', (string)$securityToken, $secret);
		}

		return hash('sha256', (string)$securityToken);
	}

	public static function securityTokenMatches($securityToken, $storedHash) {
		$storedHash = (string)$storedHash;
		if ($storedHash === '') {
			return false;
		}

		if (hash_equals($storedHash, self::hashSecurityToken($securityToken))) {
			return true;
		}

		return strlen($storedHash) === 40 && hash_equals($storedHash, sha1((string)$securityToken));
	}

	/**
	 * Returns the URL to the site without the script name.
	 */
	public static function getSiteURL() {
		return rtrim(mds_absolute_url(), '/') . '/';
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
	        if (self::isUserAdmin((int)$userId)) {
	          $myBoards = $pdo->prepare("SELECT DISTINCT boardConfig.* FROM boardConfig ORDER BY boardConfig.id");
	          $result = $myBoards->execute();
	        } elseif (self::tableExists($pdo, 'board_permissions')) {
	          $myBoards = $pdo->prepare(
	            "SELECT DISTINCT boardConfig.*
	             FROM boardConfig
	             LEFT JOIN board_permissions
	               ON board_permissions.boardId = boardConfig.id
	              AND board_permissions.userId = ?
	              AND board_permissions.canView = 1
	             WHERE boardConfig.ownerUserId = ?
	                OR board_permissions.id IS NOT NULL
	                OR EXISTS (
	                  SELECT 1
	                  FROM sensor_permissions
	                  INNER JOIN sensorConfig ON sensorConfig.id = sensor_permissions.sensorId
	                  WHERE sensorConfig.boardId = boardConfig.id
	                    AND sensor_permissions.userId = ?
	                    AND sensor_permissions.canView = 1
	                )
	             ORDER BY boardConfig.id"
	          );
	          $result = $myBoards->execute(array((int)$userId, (int)$userId, (int)$userId));
	        } else {
	          $myBoards = $pdo->prepare("SELECT * FROM boardConfig WHERE ownerUserId = ? ORDER BY id");
	          $result = $myBoards->execute(array((int)$userId));
	        }
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
	        $myBoards = $pdo->prepare("SELECT * FROM boardConfig WHERE id = ? ORDER BY id LIMIT 1");
	        $result = $myBoards->execute(array((int)$boardId));
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
	        $myBoards = $pdo->prepare("SELECT boardId FROM sensorConfig WHERE id = ? ORDER BY id LIMIT 1");
	        $result = $myBoards->execute(array((int)$sensorId));
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
  public static function normalizeMacAddress($macAddress) {
    $rawMacAddress = trim((string)$macAddress);
    if ($rawMacAddress === '') {
      return '';
    }

    if (
      preg_match('/^[A-Fa-f0-9]{12}$/', $rawMacAddress) !== 1
      && preg_match('/^[A-Fa-f0-9]{2}([:-][A-Fa-f0-9]{2}){5}$/', $rawMacAddress) !== 1
    ) {
      return $rawMacAddress;
    }

    $hexMacAddress = strtoupper(preg_replace('/[^A-Fa-f0-9]/', '', $rawMacAddress));
    return implode(':', str_split($hexMacAddress, 2));
  }

  public static function getBoardByMacAddress($macAddress) {
    $normalizedMacAddress = self::normalizeMacAddress($macAddress);
    if ($normalizedMacAddress === '') {
      return null;
    }

    $isNormalizedMacAddress = preg_match('/^[A-F0-9]{2}(:[A-F0-9]{2}){5}$/', $normalizedMacAddress) === 1;
    $normalizedHexMacAddress = strtoupper(preg_replace('/[^A-Fa-f0-9]/', '', $normalizedMacAddress));
    if ($normalizedHexMacAddress === '' && $normalizedMacAddress === '') {
      return null;
    }

    $pdo = dbConfig::getInstance();
    if ($isNormalizedMacAddress) {
      $statement = $pdo->prepare(
        "SELECT * FROM boardConfig
         WHERE REPLACE(REPLACE(UPPER(macAddress), ':', ''), '-', '') = ?
         ORDER BY id LIMIT 1"
      );
      $statement->execute(array($normalizedHexMacAddress));
    } else {
      $statement = $pdo->prepare("SELECT * FROM boardConfig WHERE macAddress = ? ORDER BY id LIMIT 1");
      $statement->execute(array($normalizedMacAddress));
    }
    $board = $statement->fetch(PDO::FETCH_ASSOC);

    return $board ?: null;
  }

  public static function getBoardByTTN($ttnAppId, $ttnDevEui, $ttnDeviceId = null) {
    if (($ttnAppId == null) || (($ttnDevEui == null) && ($ttnDeviceId == null))) {
      return null;
    }

    $pdo = dbConfig::getInstance();
    if ($ttnDeviceId != null && $ttnDeviceId !== '') {
      // TTN's device_id is the stable application identifier. Prefer its exact
      // match over a legacy DevEUI-only row when both still exist.
      $sql = "SELECT * FROM boardConfig
              WHERE ttnAppId = ? AND (ttnDevId = ? OR ttnDevId = ?)
              ORDER BY CASE WHEN ttnDevId = ? THEN 0 ELSE 1 END, id
              LIMIT 1";
      $params = array($ttnAppId, $ttnDeviceId, $ttnDevEui, $ttnDeviceId);
    } else {
      $sql = "SELECT * FROM boardConfig WHERE ttnAppId = ? AND ttnDevId = ? ORDER BY id LIMIT 1";
      $params = array($ttnAppId, $ttnDevEui);
    }

    $myBoards = $pdo->prepare($sql);
    $myBoards->execute($params);
    return $myBoards->fetch(PDO::FETCH_ASSOC);
  }

  /*
  * Add Board by Board TTN appid and dev id. Only one dataset will return.
  */
  public static function addBoardByTTN($ttnAppId, $ttnDevId, $macAddress = null) {
    $pdo = dbConfig::getInstance();
    $boardMacAddress = self::normalizeMacAddress($macAddress);
    if ($boardMacAddress === '') {
      $boardMacAddress = "fakeMacAddress" . $ttnDevId;
    }

    $statement = $pdo->prepare("INSERT INTO boardConfig (macAddress, name, ttnAppId, ttnDevId, onDashboard, updateDataTimer, offlineDataTimer) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $statement->execute(array($boardMacAddress, "- new imported -", $ttnAppId, $ttnDevId, 1, 15, 15));
    $neue_id = $pdo->lastInsertId();
    return $neue_id;
  }

  public static function updateBoardMacAddressIfPlaceholder($boardId, $macAddress) {
    $normalizedMacAddress = self::normalizeMacAddress($macAddress);
    if ((int)$boardId <= 0 || $normalizedMacAddress === '') {
      return false;
    }

    $pdo = dbConfig::getInstance();
    $statement = $pdo->prepare("SELECT macAddress FROM boardConfig WHERE id = ? LIMIT 1");
    $statement->execute(array((int)$boardId));
    $board = $statement->fetch(PDO::FETCH_ASSOC);
    if (!$board) {
      return false;
    }

    $currentMacAddress = trim((string)($board['macAddress'] ?? ''));
    if ($currentMacAddress !== '' && stripos($currentMacAddress, 'fakeMacAddress') !== 0) {
      return false;
    }

    $updateStatement = $pdo->prepare("UPDATE boardConfig SET macAddress = ? WHERE id = ?");
    return $updateStatement->execute(array($normalizedMacAddress, (int)$boardId));
  }

  public static function updateBoardTTNIdentifiersIfEmpty($boardId, $ttnAppId, $ttnDevId) {
    if ((int)$boardId <= 0 || $ttnAppId === null || $ttnDevId === null || $ttnDevId === '') {
      return false;
    }

    $pdo = dbConfig::getInstance();
    $statement = $pdo->prepare(
      "UPDATE boardConfig
       SET ttnAppId = COALESCE(NULLIF(ttnAppId, ''), ?),
           ttnDevId = COALESCE(NULLIF(ttnDevId, ''), ?)
       WHERE id = ?"
    );

    return $statement->execute(array($ttnAppId, $ttnDevId, (int)$boardId));
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

  public static function getSensorActivitySummary(array $sensorIds, $maxAgeMinutes) {
    $sensorIds = self::normalizeIntList($sensorIds);
    $summary = array(
      'configured' => count($sensorIds),
      'withData' => 0,
      'current' => 0,
    );
    if (empty($sensorIds)) {
      return $summary;
    }

    $maxAgeMinutes = max(1, (int)$maxAgeMinutes);
    $placeholders = implode(', ', array_fill(0, count($sensorIds), '?'));
    $pdo = dbConfig::getInstance();
    $statement = $pdo->prepare(
      "SELECT sensorId, UNIX_TIMESTAMP(MAX(reading_time)) AS lastReading
       FROM sensorData
       WHERE sensorId IN ($placeholders)
       GROUP BY sensorId"
    );
    $statement->execute($sensorIds);
    $latestReadings = $statement->fetchAll(PDO::FETCH_ASSOC);
    $summary['withData'] = count($latestReadings);
    $currentThreshold = time() - ($maxAgeMinutes * 60);
    foreach ($latestReadings as $latestReading) {
      $readingTimestamp = (int)($latestReading['lastReading'] ?? 0);
      if ($readingTimestamp > 0 && $readingTimestamp >= $currentThreshold) {
        $summary['current']++;
      }
    }

    return $summary;
  }

  /*
  * Get the lastet sensor data of a given sensor id.
  */
	  public static function getLatestSensorData($sensorId, $maxNrOfValue = 1) {
	    if ($maxNrOfValue >= 1) {
	      $pdo = dbConfig::getInstance();
	      $sensorIds = self::normalizeIntList($sensorId);
	      if (empty($sensorIds)) {
	        return array();
	      }
	      $maxNrOfValue = max(1, (int)$maxNrOfValue);
	      $placeholders = implode(', ', array_fill(0, count($sensorIds), '?'));
	      $mySensors = $pdo->prepare("SELECT *, UNIX_TIMESTAMP(reading_time) AS receivedAt FROM sensorData WHERE sensorId IN ($placeholders) ORDER BY id DESC LIMIT $maxNrOfValue");
	      $mySensors->execute($sensorIds);
	      $SensorData = $mySensors->fetchAll(PDO::FETCH_ASSOC);
	      return $SensorData;
	    }
	  }

	  public static function validateSecurityToken($identifier, $securityToken) {
	    if (($identifier == null) || ($securityToken == null) || ($identifier === '') || ($securityToken === '')) {
	      return false;
	    }

	    $pdo = dbConfig::getInstance();
	    $statement = $pdo->prepare("SELECT userId, securityToken FROM securityTokens WHERE identifier = ? ORDER BY id DESC LIMIT 1");
	    $statement->execute(array($identifier));
	    $tokenRow = $statement->fetch(PDO::FETCH_ASSOC);

	    if (($tokenRow === false) || !isset($tokenRow['securityToken'])) {
	      return false;
	    }

	    if (!self::securityTokenMatches($securityToken, $tokenRow['securityToken'])) {
	      return false;
	    }

	    return (int)$tokenRow['userId'];
	  }

	  public static function isUserAdmin($userId) {
	    $pdo = dbConfig::getInstance();
	    $statement = $pdo->prepare("SELECT userGroupAdmin FROM users WHERE id = ? LIMIT 1");
	    $statement->execute(array((int)$userId));
	    $userRow = $statement->fetch(PDO::FETCH_ASSOC);
	    if (($userRow !== false) && ((int)$userRow['userGroupAdmin'] === 1)) {
	      return true;
	    }

	    if (!self::tableExists($pdo, 'roles') || !self::tableExists($pdo, 'user_roles')) {
	      return false;
	    }

	    $roleStatement = $pdo->prepare(
	      "SELECT user_roles.userId
	       FROM user_roles
	       INNER JOIN roles ON roles.id = user_roles.roleId
	       WHERE user_roles.userId = ? AND roles.name = 'admin'
	       LIMIT 1"
	    );
	    $roleStatement->execute(array((int)$userId));
	    return $roleStatement->fetch(PDO::FETCH_ASSOC) !== false;
	  }

	  public static function canUserAccessBoard($userId, $boardId) {
	    $permission = self::getBoardPermission((int)$userId, (int)$boardId);
	    return !empty($permission['canView']);
	  }

	  public static function canUserEditBoard($userId, $boardId) {
	    $permission = self::getBoardPermission((int)$userId, (int)$boardId);
	    return !empty($permission['canEdit']);
	  }

	  public static function canUserManageBoardAccess($userId, $boardId) {
	    $permission = self::getBoardPermission((int)$userId, (int)$boardId);
	    return !empty($permission['canManageUsers']);
	  }

	  public static function canUserAccessSensor($userId, $sensorId) {
	    $permission = self::getSensorPermission((int)$userId, (int)$sensorId);
	    return !empty($permission['canView']);
	  }

	  public static function canUserEditSensor($userId, $sensorId) {
	    $permission = self::getSensorPermission((int)$userId, (int)$sensorId);
	    return !empty($permission['canEdit']);
	  }

	  public static function canUserReceiveSensorAlert($userId, $sensorId) {
	    $permission = self::getSensorPermission((int)$userId, (int)$sensorId);
	    return !empty($permission['canReceiveAlerts']);
	  }

	  public static function getBoardPermission($userId, $boardId) {
	    $userId = (int)$userId;
	    $boardId = (int)$boardId;
	    $emptyPermission = self::permissionDefaults('none');
	    if (($userId <= 0) || ($boardId <= 0)) {
	      return $emptyPermission;
	    }

	    if (self::isUserAdmin($userId)) {
	      return self::permissionDefaults('admin');
	    }

	    $pdo = dbConfig::getInstance();
	    if (self::tableExists($pdo, 'board_permissions')) {
	      $statement = $pdo->prepare(
	        "SELECT role, canView, canEdit, canManageUsers, canReceiveAlerts
	         FROM board_permissions
	         WHERE boardId = ? AND userId = ?
	         LIMIT 1"
	      );
	      $statement->execute(array($boardId, $userId));
	      $row = $statement->fetch(PDO::FETCH_ASSOC);
	      if ($row !== false) {
	        return array(
	          'role' => $row['role'] ?: 'custom',
	          'canView' => (int)$row['canView'] === 1,
	          'canEdit' => (int)$row['canEdit'] === 1,
	          'canManageUsers' => (int)$row['canManageUsers'] === 1,
	          'canReceiveAlerts' => (int)$row['canReceiveAlerts'] === 1,
	          'source' => 'board_permissions',
	        );
	      }
	    }

	    $statement = $pdo->prepare(
	      "SELECT id FROM boardConfig WHERE id = ? AND ownerUserId = ? LIMIT 1"
	    );
	    $statement->execute(array($boardId, $userId));
	    if ($statement->fetch(PDO::FETCH_ASSOC) !== false) {
	      return self::permissionDefaults('owner');
	    }

	    return $emptyPermission;
	  }

	  public static function getSensorPermission($userId, $sensorId) {
	    $userId = (int)$userId;
	    $sensorId = (int)$sensorId;
	    $emptyPermission = self::permissionDefaults('none');
	    if (($userId <= 0) || ($sensorId <= 0)) {
	      return $emptyPermission;
	    }

	    if (self::isUserAdmin($userId)) {
	      return self::permissionDefaults('admin');
	    }

	    $pdo = dbConfig::getInstance();
	    $sensorStatement = $pdo->prepare("SELECT id, boardId FROM sensorConfig WHERE id = ? LIMIT 1");
	    $sensorStatement->execute(array($sensorId));
	    $sensorRow = $sensorStatement->fetch(PDO::FETCH_ASSOC);
	    if ($sensorRow === false) {
	      return $emptyPermission;
	    }

	    if (self::tableExists($pdo, 'sensor_permissions')) {
	      $statement = $pdo->prepare(
	        "SELECT role, canView, canEdit, canReceiveAlerts
	         FROM sensor_permissions
	         WHERE sensorId = ? AND userId = ?
	         LIMIT 1"
	      );
	      $statement->execute(array($sensorId, $userId));
	      $row = $statement->fetch(PDO::FETCH_ASSOC);
	      if ($row !== false) {
	        return array(
	          'role' => $row['role'] ?: 'custom',
	          'canView' => (int)$row['canView'] === 1,
	          'canEdit' => (int)$row['canEdit'] === 1,
	          'canManageUsers' => false,
	          'canReceiveAlerts' => (int)$row['canReceiveAlerts'] === 1,
	          'source' => 'sensor_permissions',
	        );
	      }
	    }

	    return self::getBoardPermission($userId, (int)$sensorRow['boardId']);
	  }

	  public static function getManageableBoards($userId) {
	    if (!self::permissionsStorageAvailable()) {
	      return array();
	    }

	    $boards = self::getMyBoards((int)$userId);
	    if (!is_array($boards)) {
	      return array();
	    }

	    return array_values(array_filter($boards, function ($board) use ($userId) {
	      return self::canUserManageBoardAccess((int)$userId, (int)($board['id'] ?? 0));
	    }));
	  }

	  public static function getBoardAccessList($boardId) {
	    $pdo = dbConfig::getInstance();
	    if (!self::tableExists($pdo, 'board_permissions')) {
	      return array();
	    }

	    $statement = $pdo->prepare(
	      "SELECT board_permissions.*, users.email, users.firstName, users.lastName
	       FROM board_permissions
	       INNER JOIN users ON users.id = board_permissions.userId
	       WHERE board_permissions.boardId = ?
	       ORDER BY board_permissions.role = 'owner' DESC, users.email"
	    );
	    $statement->execute(array((int)$boardId));
	    return $statement->fetchAll(PDO::FETCH_ASSOC);
	  }

	  public static function permissionsStorageAvailable() {
	    $pdo = dbConfig::getInstance();
	    return self::tableExists($pdo, 'board_permissions')
	      && self::tableExists($pdo, 'sensor_permissions')
	      && self::tableExists($pdo, 'permission_audit_log');
	  }

	  public static function getSensorAccessList($boardId) {
	    $pdo = dbConfig::getInstance();
	    if (!self::tableExists($pdo, 'sensor_permissions')) {
	      return array();
	    }

	    $statement = $pdo->prepare(
	      "SELECT sensor_permissions.*, users.email, users.firstName, users.lastName, sensorConfig.name AS sensorName
	       FROM sensor_permissions
	       INNER JOIN users ON users.id = sensor_permissions.userId
	       INNER JOIN sensorConfig ON sensorConfig.id = sensor_permissions.sensorId
	       WHERE sensorConfig.boardId = ?
	       ORDER BY sensorConfig.name, users.email"
	    );
	    $statement->execute(array((int)$boardId));
	    return $statement->fetchAll(PDO::FETCH_ASSOC);
	  }

	  public static function saveBoardPermission($actorUserId, $boardId, $targetUserId, $role) {
	    if (!self::permissionsStorageAvailable()) {
	      throw new RuntimeException('Permission migration has not been applied yet.');
	    }

	    $actorUserId = (int)$actorUserId;
	    $boardId = (int)$boardId;
	    $targetUserId = (int)$targetUserId;
	    if (!self::canUserManageBoardAccess($actorUserId, $boardId)) {
	      throw new RuntimeException('Access denied.');
	    }

	    $defaults = self::permissionDefaults($role);
	    if ($defaults['role'] === 'none' || $targetUserId <= 0) {
	      throw new InvalidArgumentException('Invalid permission role or user.');
	    }

	    $pdo = dbConfig::getInstance();
	    $statement = $pdo->prepare(
	      "INSERT INTO board_permissions (boardId, userId, role, canView, canEdit, canManageUsers, canReceiveAlerts)
	       VALUES (?, ?, ?, ?, ?, ?, ?)
	       ON DUPLICATE KEY UPDATE
	         role = VALUES(role),
	         canView = VALUES(canView),
	         canEdit = VALUES(canEdit),
	         canManageUsers = VALUES(canManageUsers),
	         canReceiveAlerts = VALUES(canReceiveAlerts),
	         updatedAt = CURRENT_TIMESTAMP"
	    );
	    $statement->execute(array(
	      $boardId,
	      $targetUserId,
	      $defaults['role'],
	      $defaults['canView'] ? 1 : 0,
	      $defaults['canEdit'] ? 1 : 0,
	      $defaults['canManageUsers'] ? 1 : 0,
	      $defaults['canReceiveAlerts'] ? 1 : 0,
	    ));
	    self::writePermissionAudit($actorUserId, $targetUserId, 'board', $boardId, 'save', null, $defaults['role']);
	  }

	  public static function removeBoardPermission($actorUserId, $boardId, $targetUserId) {
	    if (!self::permissionsStorageAvailable()) {
	      throw new RuntimeException('Permission migration has not been applied yet.');
	    }

	    $actorUserId = (int)$actorUserId;
	    $boardId = (int)$boardId;
	    $targetUserId = (int)$targetUserId;
	    if (!self::canUserManageBoardAccess($actorUserId, $boardId)) {
	      throw new RuntimeException('Access denied.');
	    }

	    $pdo = dbConfig::getInstance();
	    $ownerStatement = $pdo->prepare("SELECT ownerUserId FROM boardConfig WHERE id = ? LIMIT 1");
	    $ownerStatement->execute(array($boardId));
	    $ownerRow = $ownerStatement->fetch(PDO::FETCH_ASSOC);
	    if ($ownerRow && (int)$ownerRow['ownerUserId'] === $targetUserId) {
	      throw new RuntimeException('The primary board owner cannot be removed here.');
	    }

	    $statement = $pdo->prepare("DELETE FROM board_permissions WHERE boardId = ? AND userId = ?");
	    $statement->execute(array($boardId, $targetUserId));
	    self::writePermissionAudit($actorUserId, $targetUserId, 'board', $boardId, 'remove', null, null);
	  }

	  public static function saveSensorPermission($actorUserId, $boardId, $sensorId, $targetUserId, $role) {
	    if (!self::permissionsStorageAvailable()) {
	      throw new RuntimeException('Permission migration has not been applied yet.');
	    }

	    $actorUserId = (int)$actorUserId;
	    $boardId = (int)$boardId;
	    $sensorId = (int)$sensorId;
	    $targetUserId = (int)$targetUserId;
	    if (!self::canUserManageBoardAccess($actorUserId, $boardId)) {
	      throw new RuntimeException('Access denied.');
	    }

	    $sensorRow = self::getSensorConfig($sensorId);
	    if (!$sensorRow || (int)$sensorRow['boardId'] !== $boardId) {
	      throw new RuntimeException('Sensor does not belong to the selected board.');
	    }

	    $defaults = self::permissionDefaults($role);
	    if ($defaults['role'] === 'none' || $targetUserId <= 0) {
	      throw new InvalidArgumentException('Invalid permission role or user.');
	    }

	    $pdo = dbConfig::getInstance();
	    $statement = $pdo->prepare(
	      "INSERT INTO sensor_permissions (sensorId, userId, role, canView, canEdit, canReceiveAlerts)
	       VALUES (?, ?, ?, ?, ?, ?)
	       ON DUPLICATE KEY UPDATE
	         role = VALUES(role),
	         canView = VALUES(canView),
	         canEdit = VALUES(canEdit),
	         canReceiveAlerts = VALUES(canReceiveAlerts),
	         updatedAt = CURRENT_TIMESTAMP"
	    );
	    $statement->execute(array(
	      $sensorId,
	      $targetUserId,
	      $defaults['role'],
	      $defaults['canView'] ? 1 : 0,
	      $defaults['canEdit'] ? 1 : 0,
	      $defaults['canReceiveAlerts'] ? 1 : 0,
	    ));
	    self::writePermissionAudit($actorUserId, $targetUserId, 'sensor', $sensorId, 'save', null, $defaults['role']);
	  }

	  public static function removeSensorPermission($actorUserId, $boardId, $sensorId, $targetUserId) {
	    if (!self::permissionsStorageAvailable()) {
	      throw new RuntimeException('Permission migration has not been applied yet.');
	    }

	    $actorUserId = (int)$actorUserId;
	    $boardId = (int)$boardId;
	    $sensorId = (int)$sensorId;
	    $targetUserId = (int)$targetUserId;
	    if (!self::canUserManageBoardAccess($actorUserId, $boardId)) {
	      throw new RuntimeException('Access denied.');
	    }

	    $sensorRow = self::getSensorConfig($sensorId);
	    if (!$sensorRow || (int)$sensorRow['boardId'] !== $boardId) {
	      throw new RuntimeException('Sensor does not belong to the selected board.');
	    }

	    $pdo = dbConfig::getInstance();
	    $statement = $pdo->prepare("DELETE FROM sensor_permissions WHERE sensorId = ? AND userId = ?");
	    $statement->execute(array($sensorId, $targetUserId));
	    self::writePermissionAudit($actorUserId, $targetUserId, 'sensor', $sensorId, 'remove', null, null);
	  }

	  public static function permissionDefaults($role) {
	    $role = strtolower(trim((string)$role));
	    if ($role === 'admin') {
	      return array('role' => 'admin', 'canView' => true, 'canEdit' => true, 'canManageUsers' => true, 'canReceiveAlerts' => true, 'source' => 'admin');
	    }
	    if ($role === 'owner') {
	      return array('role' => 'owner', 'canView' => true, 'canEdit' => true, 'canManageUsers' => true, 'canReceiveAlerts' => true, 'source' => 'owner');
	    }
	    if ($role === 'user' || $role === 'editor') {
	      return array('role' => 'user', 'canView' => true, 'canEdit' => true, 'canManageUsers' => false, 'canReceiveAlerts' => true, 'source' => 'role');
	    }
	    if ($role === 'observer') {
	      return array('role' => 'observer', 'canView' => true, 'canEdit' => false, 'canManageUsers' => false, 'canReceiveAlerts' => true, 'source' => 'role');
	    }

	    return array('role' => 'none', 'canView' => false, 'canEdit' => false, 'canManageUsers' => false, 'canReceiveAlerts' => false, 'source' => 'none');
	  }

	  private static function writePermissionAudit($actorUserId, $targetUserId, $resourceType, $resourceId, $action, $oldValue, $newValue) {
	    $pdo = dbConfig::getInstance();
	    if (!self::tableExists($pdo, 'permission_audit_log')) {
	      return;
	    }

	    $statement = $pdo->prepare(
	      "INSERT INTO permission_audit_log (actorUserId, targetUserId, resourceType, resourceId, action, oldValue, newValue)
	       VALUES (?, ?, ?, ?, ?, ?, ?)"
	    );
	    $statement->execute(array(
	      (int)$actorUserId,
	      (int)$targetUserId,
	      (string)$resourceType,
	      (int)$resourceId,
	      (string)$action,
	      $oldValue,
	      $newValue,
	    ));
	  }

	  private static function tableExists(PDO $pdo, $tableName) {
	    static $cache = array();
	    $tableName = (string)$tableName;
	    if (isset($cache[$tableName])) {
	      return $cache[$tableName];
	    }

	    try {
	      $statement = $pdo->prepare(
	        "SELECT COUNT(*) AS tableCount
	         FROM INFORMATION_SCHEMA.TABLES
	         WHERE TABLE_SCHEMA = DATABASE()
	           AND TABLE_NAME = ?"
	      );
	      $statement->execute(array($tableName));
	      $row = $statement->fetch(PDO::FETCH_ASSOC);
	      $cache[$tableName] = ((int)($row['tableCount'] ?? 0) > 0);
	    } catch (Throwable $e) {
	      $cache[$tableName] = false;
	    }

	    return $cache[$tableName];
	  }

	  private static function normalizeIntList($value) {
	    if (is_array($value)) {
	      $items = $value;
	    } else {
	      $items = explode(',', (string)$value);
	    }

	    $normalized = array();
	    foreach ($items as $item) {
	      $item = trim((string)$item);
	      if ($item === '') {
	        continue;
	      }
	      if (filter_var($item, FILTER_VALIDATE_INT) === false) {
	        continue;
	      }
	      $normalized[] = (int)$item;
	    }

	    return array_values(array_unique($normalized));
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
    $mySensorsChannels = $pdo->prepare("SELECT sensorChannelConfig.*, sensorTypes.name AS resolvedType FROM sensorChannelConfig JOIN sensorConfig ON sensorConfig.id = sensorChannelConfig.sensorConfigId JOIN sensorTypes ON sensorTypes.id = sensorConfig.typId WHERE sensorConfigId = ? AND channelNr = ? ORDER BY sensorChannelConfig.id ");
    $mySensorsChannels->execute(array($id, $channelNr));
    //$mySensorsChannelsOfBoard = $mySensorsChannels->fetchAll(PDO::FETCH_ASSOC);
    $mySensorsChannelsOfBoard = $mySensorsChannels->fetch(PDO::FETCH_ASSOC);
    if (is_array($mySensorsChannelsOfBoard) && $mySensorsChannelsOfBoard['resolvedType'] === 'DS18B20') {
      $mySensorsChannelsOfBoard = TemperatureReading::channelDefaults($mySensorsChannelsOfBoard);
    }
    return $mySensorsChannelsOfBoard;
  }

  /*
  * Add SensorConfig Object if a given board id
  */
		  public function addSensorConfig($boardId, $typIdName, $sensorName, &$created = null) {
		    $pdo = dbConfig::getInstance();
		    $created = false;
		    $valuesDefined = false;
	    $sensorTypeStatement = $pdo->prepare("SELECT id, name FROM sensorTypes WHERE name = ? LIMIT 1");
	    $sensorTypeStatement->execute(array($typIdName));
	    $mySensorTypId = $sensorTypeStatement->fetchObject('sensorTyp');
    if (!$mySensorTypId) {
      throw new Exception('Unknown sensor type: ' . $typIdName);
    }
    $singletonSensorType = SensorGroupPolicy::isSingletonType($mySensorTypId->name);
    if ($singletonSensorType) {
      $existingSensorId = SensorGroupPolicy::findExistingSensorId($pdo, $boardId, $mySensorTypId->id);
      if ($existingSensorId !== null) {
        return $existingSensorId;
      }
    }
    $sensorTypeMeta = $pdo->prepare("SELECT * FROM sensorTypes WHERE id = ? LIMIT 1");
    $sensorTypeMeta->execute(array($mySensorTypId->id));
    $sensorTypeMeta = $sensorTypeMeta->fetch(PDO::FETCH_ASSOC);

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
        $defaultValuesPerChannel['GaugeStyle'] = 'classic';
        $defaultValuesPerChannel['AlertEnabled'] = 0;
        $defaultValuesPerChannel['AlertLowValue'] = null;
        $defaultValuesPerChannel['AlertHighValue'] = null;
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

    } elseif ($mySensorTypId->name == "DS18B20") {
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

    if ($valuesDefined == false) {
      $genericValueCount = max(1, min(4, (int)($sensorTypeMeta['MaxNrOfValues'] ?? 1)));
      for ($channelNr = 1; $channelNr <= $genericValueCount; $channelNr++) {
        $defaultValuesPerChannel['name'] = "Value" . $channelNr;
        $defaultValuesPerChannel['description'] = $sensorName . " value " . $channelNr;
        $defaultValuesPerChannel['channelNr'] = $channelNr;
        $defaultValuesPerChannel['locationOfMeasurement'] = "";
        $defaultValuesPerChannel['GaugeMinValue'] = 0;
        $defaultValuesPerChannel['GaugeMaxValue'] = 100;
        $defaultValuesPerChannel['GaugeRedAreaLowValue'] = 0;
        $defaultValuesPerChannel['GaugeRedAreaLowColor'] = $red;
        $defaultValuesPerChannel['GaugeRedAreaHighValue'] = 0;
        $defaultValuesPerChannel['GaugeRedAreaHighColor'] = $red;
        $defaultValuesPerChannel['GaugeNormalAreaColor'] = $green;
        $defaultValuesPerChannel['onDashboard'] = 0;
        $defaultValuesPerChannel['ChartColor'] = RandomColor::one();
        array_push($defaultValuesPerChannelArray, $defaultValuesPerChannel);
      }

      $defaultValues['ttnPayloadId'] = null;
      $defaultValues['NrOfUsedSensors'] = $genericValueCount;
      $valuesDefined = true;
    }

    if ($valuesDefined == true) {
      $defaultGaugeStyle = configuration::$defaultGaugeStyle ?: 'classic';
      $singletonLockName = null;
      try {
        if ($singletonSensorType) {
          $singletonLockName = SensorGroupPolicy::acquireCreationLock($pdo, $boardId, $mySensorTypId->id);
          $existingSensorId = SensorGroupPolicy::findExistingSensorId($pdo, $boardId, $mySensorTypId->id);
          if ($existingSensorId !== null) {
            SensorGroupPolicy::releaseCreationLock($pdo, $singletonLockName);
            return $existingSensorId;
          }
        }

        $statement = $pdo->prepare("INSERT INTO sensorConfig (boardId, typId, name," .
        "NrOfUsedSensors, onDashboard ) VALUES (?, ?, ?, ?, ?)");
        $statement->execute(array($boardId, $mySensorTypId->id, $sensorName, $defaultValues['NrOfUsedSensors'], 1));
        $neue_id = $pdo->lastInsertId();
        if ($singletonLockName !== null) {
          SensorGroupPolicy::releaseCreationLock($pdo, $singletonLockName);
          $singletonLockName = null;
        }
      } catch (PDOException $e) {
        if ($singletonLockName !== null) {
          SensorGroupPolicy::releaseCreationLock($pdo, $singletonLockName);
        }
        writeToLogFunction::write_to_log("Error: Sensor config not updated successfully.", $_SERVER["SCRIPT_FILENAME"]);
        writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
        throw new Exception('Sensor not updated successfully.');
      }

      foreach($defaultValuesPerChannelArray as $defaultValuesPerChannelSingle) {
        $defaultValuesPerChannelSingle['GaugeStyle'] = $defaultValuesPerChannelSingle['GaugeStyle'] ?? $defaultGaugeStyle;
        try {
          $statement2 = $pdo->prepare("INSERT INTO sensorChannelConfig  SET sensorConfigId=?, name=?, description=?, channelNr=?, locationOfMeasurement=?, GaugeMinValue=?, GaugeMaxValue=?,  GaugeRedAreaLowValue =?, GaugeRedAreaLowColor=?, GaugeRedAreaHighValue=?, GaugeRedAreaHighColor=?, GaugeNormalAreaColor=?, GaugeStyle=?, AlertEnabled=?, AlertLowValue=?, AlertHighValue=?, onDashboard=?, ChartColor=?");
          $statement2->execute(array($neue_id, $defaultValuesPerChannelSingle['name'], $defaultValuesPerChannelSingle['description'], $defaultValuesPerChannelSingle['channelNr'], $defaultValuesPerChannelSingle['locationOfMeasurement'],  $defaultValuesPerChannelSingle['GaugeMinValue'], $defaultValuesPerChannelSingle['GaugeMaxValue'],  $defaultValuesPerChannelSingle['GaugeRedAreaLowValue'], $defaultValuesPerChannelSingle['GaugeRedAreaLowColor'], $defaultValuesPerChannelSingle['GaugeRedAreaHighValue'], $defaultValuesPerChannelSingle['GaugeRedAreaHighColor'], $defaultValuesPerChannelSingle['GaugeNormalAreaColor'], $defaultValuesPerChannelSingle['GaugeStyle'] ?? 'classic', $defaultValuesPerChannelSingle['AlertEnabled'] ?? 0, $defaultValuesPerChannelSingle['AlertLowValue'] ?? null, $defaultValuesPerChannelSingle['AlertHighValue'] ?? null, $defaultValuesPerChannelSingle['onDashboard'], $defaultValuesPerChannelSingle['ChartColor']));
        } catch (PDOException $e) {
          writeToLogFunction::write_to_log("Error: sensorChannelConfig not updated successfully.", $_SERVER["SCRIPT_FILENAME"]);
          writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
          throw new Exception('sensorChannelConfig not updated successfully.');
        }
      }
      $created = true;
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
    if (is_array($SensorData2) && ($SensorData2['name'] ?? '') === 'DS18B20') {
      for ($channel = 1; $channel <= 4; $channel++) {
        if (trim((string)($SensorData2['siUnitVal' . $channel] ?? '')) === '') {
          $SensorData2['siUnitVal' . $channel] = '&deg;C';
        }
      }
    }
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
    $sharedAccessQueries = array();
    if (self::tableExists($pdo, 'board_permissions')) {
      $sharedAccessQueries[] = "SELECT userId, boardId FROM board_permissions WHERE canView = 1";
    }
    if (self::tableExists($pdo, 'sensor_permissions')) {
      $sharedAccessQueries[] =
        "SELECT sensor_permissions.userId, sensorConfig.boardId
         FROM sensor_permissions
         INNER JOIN sensorConfig ON sensorConfig.id = sensor_permissions.sensorId
         WHERE sensor_permissions.canView = 1";
    }

    $sharedJoin = "LEFT JOIN (SELECT NULL AS userId, 0 AS sharedBoardCount) shared ON 1 = 0";
    if (!empty($sharedAccessQueries)) {
      $sharedUnion = implode(" UNION ", $sharedAccessQueries);
      $sharedJoin =
        "LEFT JOIN (
           SELECT sharedAccess.userId, COUNT(DISTINCT sharedAccess.boardId) AS sharedBoardCount
           FROM ($sharedUnion) sharedAccess
           INNER JOIN boardConfig sharedBoard ON sharedBoard.id = sharedAccess.boardId
           WHERE sharedBoard.ownerUserId IS NULL OR sharedBoard.ownerUserId <> sharedAccess.userId
           GROUP BY sharedAccess.userId
         ) shared ON shared.userId = users.id";
    }

    $statement = $pdo->prepare(
      "SELECT users.id,
              users.email,
              users.firstName,
              users.lastName,
              users.active,
              users.userGroupAdmin,
              COALESCE(owned.ownedBoardCount, 0) AS ownedBoardCount,
              COALESCE(shared.sharedBoardCount, 0) AS sharedBoardCount
       FROM users
       LEFT JOIN (
         SELECT ownerUserId, COUNT(*) AS ownedBoardCount
         FROM boardConfig
         WHERE ownerUserId IS NOT NULL
         GROUP BY ownerUserId
       ) owned ON owned.ownerUserId = users.id
       $sharedJoin
       ORDER BY users.id"
    );
    $statement->execute();
    return $statement->fetchAll(PDO::FETCH_ASSOC);
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
    $statement = $pdo->prepare("SELECT alreadyNotified FROM boardConfig WHERE id =?");
    $statement->execute(array($boardId));
    $result = $statement->fetch(PDO::FETCH_ASSOC);
    if (($result !== false) && isset($result['alreadyNotified'])) {
      return (int)$result['alreadyNotified'];
    }
    return 0;
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
		include(dirname(__FILE__, 2) . "/Presentation/Common/header.inc.php");
		include(dirname(__FILE__, 2) . "/Presentation/Common/error.inc.php");
		include(dirname(__FILE__, 2) . "/Presentation/Common/footer.inc.php");
	    writeToLogFunction::write_to_log("Error: function error was triggered.", $_SERVER["SCRIPT_FILENAME"]);
			exit();
	}
}
