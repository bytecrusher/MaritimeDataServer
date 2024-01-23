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
    static $config_exist = null;
    static $dbHost = null;
    static $dbName = null;
    static $dbUser = null;
    static $dbPassword = null;
    static $apiKey = null;
    static $baseurl = null;
    static $subDir = null;
    static $demoMode = null;
    static $md5secretString = null;
    static $installFinished = null;
    static $adminEmailAddress = null;
    static $ShowQrCode = null;
    static $sendEmails = null;
    
    function __construct() {
        self::$subDir = "/" . str_replace($_SERVER['DOCUMENT_ROOT'],"",__DIR__);
        if (isset($_SERVER['HTTP_HOST'])) {
            $domain = $_SERVER['HTTP_HOST'];
        } else {
            $domain = "/";
        }
        if (isset($_SERVER['HTTPS']) &&
            ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
            isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
            $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
            $prefix = 'https://';
        }
        else {
            $prefix = 'http://';
        }
        self::$baseurl = $prefix . $domain . self::$subDir;

        $path = "";
        if (file_exists(__DIR__ . '/config.json')) {
            $path = __DIR__ . '/config.json';
            $jsonString = file_get_contents($path);
            $jsonData = json_decode($jsonString, true);
            self::$dbHost = $jsonData['dbHost'];
            self::$dbName = $jsonData['dbName'];
            self::$dbUser = $jsonData['dbUser'];
            self::$dbPassword = $jsonData['dbPassword'];
            self::$apiKey = "";
            if (array_key_exists('apiKey', $jsonData)) {
                self::$apiKey = $jsonData['apiKey'];
            }

            self::$demoMode = "";
            if (array_key_exists('demoMode', $jsonData)) {
                self::$demoMode = $jsonData['demoMode'];
            }

            self::$md5secretString = "";
            if (array_key_exists('md5secretString', $jsonData)) {
                self::$md5secretString = $jsonData['md5secretString'];
            }

            self::$installFinished = "";
            if (array_key_exists('installFinished', $jsonData)) {
                self::$installFinished = $jsonData['installFinished'];
            }

            self::$adminEmailAddress = "";
            if (array_key_exists('adminEmailAddress', $jsonData)) {
                self::$adminEmailAddress = $jsonData['adminEmailAddress'];
            }

            self::$ShowQrCode = "";
            if (array_key_exists('ShowQrCode', $jsonData)) {
                self::$ShowQrCode = $jsonData['ShowQrCode'];
            }

            self::$sendEmails = "";
            if (array_key_exists('sendEmails', $jsonData)) {
                self::$sendEmails = $jsonData['sendEmails'];
            }

            self::$config_exist = true;
        } else {
            $path = false;
            self::$config_exist = false;
        }
    }

    function saveServerSettings($post) {
        try {
            self::$demoMode = $post['demoMode'];
            self::$ShowQrCode = $post['ShowQrCode'];
            self::$apiKey = $post['apiKey'];
            self::$sendEmails = $post['sendEmails'];
            $path = __DIR__ . '/config.json';
            $jsonString = file_get_contents($path);
            $jsonData = json_decode($jsonString, true);
            $jsonData['demoMode'] = $post['demoMode'];
            $jsonData['ShowQrCode'] = $post['ShowQrCode'];
            $jsonData['apiKey'] = $post['apiKey'];
            $jsonData['sendEmails'] = $post['sendEmails'];
            $jsonString = json_encode($jsonData, JSON_PRETTY_PRINT);
            // Write in the file
            $fp = fopen($path, 'w');
            fwrite($fp, $jsonString);
            fclose($fp);
        } catch (PDOException $err) {
            writeToLogFunction::write_to_log("error code: " . $err->getCode(), $_SERVER["SCRIPT_FILENAME"]);
        }
    }
}