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
    catch (PDOException $e) {
        writeToLogFunction::error(
          'Database connection could not be established.',
          __FILE__,
          array(
            'sqlState' => (string)$e->getCode(),
            'error' => $e->getMessage(),
          )
        );
        throw new RuntimeException('Database connection unavailable.', 0, $e);
    }
  }

  public static function getInstance ()
  {
      if(self::$pdo === null)
        self::$object = new dbConfig;
      return self::$pdo;
  }
}
