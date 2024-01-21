<?php
/**
 * Class for handling DB connection.
 *
 * @author: Guntmar Hoeche
 * @license: TBD
 */

require_once(__DIR__ . '/../../configuration.php');
require_once(dirname(__FILE__) . "/writeToLogFunction.func.php");

class dbConfig {
  private static $pdo = null;
  private static $object;

  private function __construct ()
  {
    $config  = new configuration();

    try {
      self::$pdo = new PDO("mysql:host=" . $config::$db_host . ";dbname=" . $config::$db_name, $config::$db_user, $config::$db_password);
    }
    catch(PDOException $e)
    {
        //$e->getMessage());
        writeToLogFunction::write_to_log("Error while create PDO object: ", $_SERVER["SCRIPT_FILENAME"]);
        writeToLogFunction::write_to_log($e, $_SERVER["SCRIPT_FILENAME"]);
        exit();
    }
  }

  public static function getInstance ()
  {
      if(self::$pdo === null)
        self::$object = new dbConfig;
      return self::$pdo;
  }
}
