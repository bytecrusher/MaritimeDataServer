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
}
