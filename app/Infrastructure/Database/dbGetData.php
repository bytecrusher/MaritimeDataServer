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
    $statement = $pdo->prepare("SELECT * FROM boardConfig WHERE ownerUserId = :userId ORDER BY id");
    $statement->execute(array('userId' => (int)$userId));
    return $statement->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function getAllBoards() {
    $pdo = dbConfig::getInstance();
    $statement = $pdo->query("SELECT * FROM boardConfig ORDER BY id");
    return $statement->fetchAll(PDO::FETCH_ASSOC);
  }

  public static function getRecentTtnDebugRows($limit = 30) {
    $pdo = dbConfig::getInstance();
    $limit = max(1, min((int)$limit, 500));
    $statement = $pdo->query("SELECT * FROM ttnDataLoraBoatMonitor ORDER BY id DESC LIMIT " . $limit);
    return $statement->fetchAll(PDO::FETCH_ASSOC);
  }
}
