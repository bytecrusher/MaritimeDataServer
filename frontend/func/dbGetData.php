<?php
/**
 * class for get a user with a given email or id (depreachted?)
 * 
 * @author: Guntmar Hoeche
 * @license: TBD
 */
include_once("dbConfig.func.php");

class dbGetData {
  public static function getUserOld($email) {
    $pdo = dbConfig::getInstance();
    $statement = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $result = $statement->execute(array('email' => $email));
    return $statement->fetch();
  }

  public static function getUserById($id) {
    $pdo = dbConfig::getInstance();
    $statement = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $result = $statement->execute(array('id' => $id));
    return $statement->fetch();
  }
}
?>
