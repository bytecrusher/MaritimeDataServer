<?php
/**
 * Class for handling DB connection.
 *
 * @author: Guntmar Hoeche
 * @license: TBD
 */

require_once(__DIR__ . '/../Config/configuration.php');
require_once(__DIR__ . '/../Logging/writeToLogFunction.func.php');

class dbConfig {
  private static $pdo = null;
  private static $object;

  private function __construct ()
  {
    $config  = new configuration();

    try {
      self::$pdo = new PDO(
        "mysql:host=" . $config::$dbHost . ";dbname=" . $config::$dbName . ";charset=utf8mb4",
        $config::$dbUser,
        $config::$dbPassword,
        array(
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
          PDO::ATTR_EMULATE_PREPARES => false,
        )
      );
      self::$pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
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
