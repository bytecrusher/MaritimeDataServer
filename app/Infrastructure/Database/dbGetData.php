<?php
/**
 * class for get a user with a given email or id (deprecated?)
 * 
 * @author: Guntmar Hoeche
 * @license: TBD
 */
require_once(__DIR__ . "/dbConfig.func.php");

class dbGetData {
  public static function getUserById($id) {
    $pdo = dbConfig::getInstance();
    $statement = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $result = $statement->execute(array('id' => $id));
    return $statement->fetch();
  }

  public static function getBoardsByOwnerId($userId) {
    $pdo = dbConfig::getInstance();
    $userId = (int)$userId;
    if (self::isAdmin($pdo, $userId)) {
      $statement = $pdo->prepare("SELECT DISTINCT boardConfig.* FROM boardConfig ORDER BY boardConfig.id");
      $statement->execute();
      return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    if (self::tableExists($pdo, 'board_permissions')) {
      $statement = $pdo->prepare(
        "SELECT DISTINCT boardConfig.*
         FROM boardConfig
         LEFT JOIN board_permissions
           ON board_permissions.boardId = boardConfig.id
          AND board_permissions.userId = :permissionUserId
          AND board_permissions.canView = 1
         WHERE boardConfig.ownerUserId = :ownerUserId
            OR board_permissions.id IS NOT NULL
            OR EXISTS (
              SELECT 1
              FROM sensor_permissions
              INNER JOIN sensorConfig ON sensorConfig.id = sensor_permissions.sensorId
              WHERE sensorConfig.boardId = boardConfig.id
                AND sensor_permissions.userId = :sensorPermissionUserId
                AND sensor_permissions.canView = 1
            )
         ORDER BY boardConfig.id"
      );
      $statement->execute(array(
        'permissionUserId' => $userId,
        'ownerUserId' => $userId,
        'sensorPermissionUserId' => $userId,
      ));
      return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    $statement = $pdo->prepare("SELECT * FROM boardConfig WHERE ownerUserId = :userId ORDER BY id");
    $statement->execute(array('userId' => $userId));
    return $statement->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function getAllBoards() {
    $pdo = dbConfig::getInstance();
    $statement = $pdo->prepare("SELECT * FROM boardConfig ORDER BY id");
    $statement->execute();
    return $statement->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function getRecentTtnDebugRows($limit = 30) {
    $pdo = dbConfig::getInstance();
    $limit = max(1, min((int)$limit, 500));
    $statement = $pdo->prepare("SELECT * FROM ttnDataLoraBoatMonitor ORDER BY id DESC LIMIT " . $limit);
    $statement->execute();
    return $statement->fetchAll(PDO::FETCH_ASSOC);
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

  private static function isAdmin(PDO $pdo, $userId) {
    $statement = $pdo->prepare("SELECT userGroupAdmin FROM users WHERE id = ? LIMIT 1");
    $statement->execute(array((int)$userId));
    $row = $statement->fetch(PDO::FETCH_ASSOC);
    if ($row && (int)$row['userGroupAdmin'] === 1) {
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
}
