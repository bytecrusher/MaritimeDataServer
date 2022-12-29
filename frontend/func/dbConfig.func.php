<?php
/**
 * Class for handling DB connection.
 *
 * @author: Guntmar Hoeche
 * @license: TBD
 */

//require_once(dirname(__FILE__).'/../../configuration.php');
require_once(__DIR__ . '/../../configuration.php');

class dbConfig {
  private static $pdo = null;
  private static $objekt;

  private function __construct ()
  {
    $config  = new configuration();

    try {
      self::$pdo = new PDO("mysql:host=" . $config::$db_host . ";dbname=" . $config::$db_name, $config::$db_user, $config::$db_password);
    }
    catch(PDOException $e)
    {
        //printf('Error opening database.<br><br>%s',
        //$e->getMessage());
        exit();
    }
  }

  public static function getInstance ()
  {
      if(self::$pdo === null)
        self::$objekt = new dbConfig;
      return self::$pdo;
  }
}
