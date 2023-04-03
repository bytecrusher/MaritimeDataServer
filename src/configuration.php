<?php
/**
 *
 * Used for handling configurations
 * 
 * @author: Guntmar Höche
 * @license: TBD
 */
require_once(dirname(__FILE__) . "/frontend/func/writeToLogFunction.func.php");

class configuration {
    static $db_host = null;
    static $db_name = null;
    static $db_user = null;
    static $db_password = null;
    static $api_key = null;
    static $baseurl = null;
    static $subdir = null;
    static $demoMode = null;
    static $md5secretstring = null;
    static $install_finished = null;
    static $admin_email_adress = null;

    function __construct() {
        self::$subdir = "/" . str_replace($_SERVER['DOCUMENT_ROOT'],"",__DIR__);
        //$domain = $_SERVER['SERVER_ADDR'];
        $domain= $_SERVER['HTTP_HOST'];
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
        self::$demoMode = $jsonData['demoMode'];
        self::$md5secretstring = $jsonData['md5secretstring'];
        self::$install_finished = $jsonData['install_finished'];
        self::$admin_email_adress = $jsonData['admin_email_adress'];
    }

    function setDemoMode($post) {
        try {
            self::$demoMode = $post['demoMode'];
            $path = __DIR__ . '/config.json';
            $jsonString = file_get_contents($path);
            $jsonData = json_decode($jsonString, true);
            $jsonData['demoMode'] = $post['demoMode'];
            $jsonString = json_encode($jsonData, JSON_PRETTY_PRINT);
            // Write in the file
            $fp = fopen($path, 'w');
            fwrite($fp, $jsonString);
            fclose($fp);
        } catch (PDOException $err) {
            writeToLogFunction::write_to_log("errorcode: " . $err->getCode(), $_SERVER["SCRIPT_FILENAME"]);
        }
    }
}
?>