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
    static $admin_email_address = null;
    static $ShowQrCode = null;
    static $send_emails = null;
    
    function __construct() {
        self::$subdir = "/" . str_replace($_SERVER['DOCUMENT_ROOT'],"",__DIR__);
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
        self::$baseurl = $prefix . $domain . self::$subdir;

        $path = "";
        if (file_exists(__DIR__ . '/config.json')) {
            $path = __DIR__ . '/config.json';
            $jsonString = file_get_contents($path);
            $jsonData = json_decode($jsonString, true);
            self::$db_host = $jsonData['db_host'];
            self::$db_name = $jsonData['db_name'];
            self::$db_user = $jsonData['db_user'];
            self::$db_password = $jsonData['db_password'];
            self::$api_key = "";
            if (array_key_exists('api_key', $jsonData)) {
                self::$api_key = $jsonData['api_key'];
            }

            self::$demoMode = "";
            if (array_key_exists('demoMode', $jsonData)) {
                self::$demoMode = $jsonData['demoMode'];
            }

            self::$md5secretstring = "";
            if (array_key_exists('md5secretstring', $jsonData)) {
                self::$md5secretstring = $jsonData['md5secretstring'];
            }

            self::$install_finished = "";
            if (array_key_exists('install_finished', $jsonData)) {
                self::$install_finished = $jsonData['install_finished'];
            }

            self::$admin_email_address = "";
            if (array_key_exists('admin_email_address', $jsonData)) {
                self::$admin_email_address = $jsonData['admin_email_address'];
            }

            self::$ShowQrCode = "";
            if (array_key_exists('ShowQrCode', $jsonData)) {
                self::$ShowQrCode = $jsonData['ShowQrCode'];
            }

            self::$send_emails = "";
            if (array_key_exists('send_emails', $jsonData)) {
                self::$send_emails = $jsonData['send_emails'];
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
            self::$api_key = $post['apikey'];
            self::$send_emails = $post['send_emails'];
            $path = __DIR__ . '/config.json';
            $jsonString = file_get_contents($path);
            $jsonData = json_decode($jsonString, true);
            $jsonData['demoMode'] = $post['demoMode'];
            $jsonData['ShowQrCode'] = $post['ShowQrCode'];
            $jsonData['api_key'] = $post['apikey'];
            $jsonData['send_emails'] = $post['send_emails'];
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