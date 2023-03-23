<?php
/**
 *
 * Used for handling configurations
 * 
 * @author: Guntmar Höche
 * @license: TBD
 */

class configuration {
    static $db_host = null;
    static $db_name = null;
    static $db_user = null;
    static $db_password = null;
    static $api_key = null;
    static $baseurl = null;
    static $extbaseurl = null;
    static $subdir = null;
    static $demoMode = null;
    static $md5secretstring = null;

    function __construct() {
        $domain = $_SERVER['HTTP_HOST'];
        if (isset($_SERVER['HTTPS']) &&
            ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
            isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
            $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
            $prefix = 'https://';
        }
        else {
            $prefix = 'http://';
        }
        self::$baseurl = $prefix . $domain . self::$subdir;

        $path = __DIR__ . '/config.json';
        $jsonString = file_get_contents($path);
        $jsonData = json_decode($jsonString, true);
        self::$db_host = $jsonData['db_host'];
        self::$db_name = $jsonData['db_name'];
        self::$db_user = $jsonData['db_user'];
        self::$db_password = $jsonData['db_password'];

        self::$api_key = $jsonData['api_key'];
        self::$baseurl = $jsonData['baseurl'];
        self::$extbaseurl = $jsonData['extbaseurl'];
        self::$subdir = $jsonData['subdir'];

        self::$demoMode = $jsonData['demoMode'];

        self::$md5secretstring = $jsonData['md5secretstring'];
    }
}

?>