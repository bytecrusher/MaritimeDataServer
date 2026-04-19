<?php
/**
 *
 * Used for handling configurations
 * 
 * @author: Guntmar Höche
 * @license: TBD
 */
require_once(__DIR__ . '/../Logging/writeToLogFunction.func.php');

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
    static $systemEmailAddress = null;
    static $applicationName = null;
    
    function __construct() {
        $projectRoot = dirname(__FILE__, 4);
        $legacyPublicRoot = $projectRoot . '/src';
        $modernConfigDir = $projectRoot . '/config';
        self::$subDir = self::detectSubDir($projectRoot, $legacyPublicRoot);

        #writeToLogFunction::write_to_log(self::$subDir, $_SERVER["SCRIPT_FILENAME"]);

        if (isset($_SERVER['HTTP_HOST'])) {
            $domain = $_SERVER['HTTP_HOST'];
        } else {
            $domain = "localhost";
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
        $path = $modernConfigDir . '/config.json';
        if (!file_exists($path)) {
            $path = $legacyPublicRoot . '/config/config.json';
        }

        if (file_exists($path)) {
            $jsonString = file_get_contents($path);
            $jsonData = json_decode($jsonString, true);
            self::$dbHost = $jsonData['dbHost'];
            self::$dbName = $jsonData['dbName'];
            self::$dbUser = $jsonData['dbUser'];
            self::$dbPassword = $jsonData['dbPassword'];
            self::$apiKey = "";
            if (array_key_exists('apiKey', $jsonData)) {
                self::$apiKey = $jsonData['apiKey'];
            } else {
                writeToLogFunction::write_to_log("Missing apiKey in config.", $_SERVER["SCRIPT_FILENAME"]);
            }

            self::$demoMode = "";
            if (array_key_exists('demoMode', $jsonData)) {
                self::$demoMode = $jsonData['demoMode'];
            } else {
                writeToLogFunction::write_to_log("Missing demoMode in config.", $_SERVER["SCRIPT_FILENAME"]);
            }

            self::$md5secretString = "";
            if (array_key_exists('md5secretString', $jsonData)) {
                self::$md5secretString = $jsonData['md5secretString'];
            } else {
                writeToLogFunction::write_to_log("Missing md5secretString in config.", $_SERVER["SCRIPT_FILENAME"]);
            }

            self::$installFinished = "";
            if (array_key_exists('installFinished', $jsonData)) {
                self::$installFinished = $jsonData['installFinished'];
            } else {
                writeToLogFunction::write_to_log("Missing installFinished in config.", $_SERVER["SCRIPT_FILENAME"]);
            }

            self::$adminEmailAddress = "";
            if (array_key_exists('adminEmailAddress', $jsonData)) {
                self::$adminEmailAddress = $jsonData['adminEmailAddress'];
            } else {
                //writeToLogFunction::write_to_log("Missing adminEmailAddress in config.", $_SERVER["SCRIPT_FILENAME"]);
            }

            self::$ShowQrCode = "";
            if (array_key_exists('ShowQrCode', $jsonData)) {
                self::$ShowQrCode = $jsonData['ShowQrCode'];
            } else {
                writeToLogFunction::write_to_log("Missing ShowQrCode in config.", $_SERVER["SCRIPT_FILENAME"]);
            }

            self::$sendEmails = "";
            if (array_key_exists('sendEmails', $jsonData)) {
                self::$sendEmails = $jsonData['sendEmails'];
            } else {
                writeToLogFunction::write_to_log("Missing sendEmails in config.", $_SERVER["SCRIPT_FILENAME"]);
            }

            self::$systemEmailAddress = "";
            if (array_key_exists('systemEmailAddress', $jsonData)) {
                self::$systemEmailAddress = $jsonData['systemEmailAddress'];
            } else {
                writeToLogFunction::write_to_log("Missing systemEmailAddress in config.", $_SERVER["SCRIPT_FILENAME"]);
            }

            self::$applicationName = "";
            if (array_key_exists('applicationName', $jsonData)) {
                self::$applicationName = $jsonData['applicationName'];
            } else {
                writeToLogFunction::write_to_log("Missing applicationName in config.", $_SERVER["SCRIPT_FILENAME"]);
            }

            self::$config_exist = true;
        } else {
            $path = false;
            self::$config_exist = false;
            writeToLogFunction::write_to_log("Missing config.json", $_SERVER["SCRIPT_FILENAME"]);
        }
    }

    function saveServerSettings($post) {
        try {
            $projectRoot = dirname(__FILE__, 4);
            $modernConfigDir = $projectRoot . '/config';
            if (!is_dir($modernConfigDir)) {
                mkdir($modernConfigDir, 0775, true);
            }
            self::$apiKey = $post['apiKey'];
            self::$demoMode = $post['demoMode'];
            self::$ShowQrCode = $post['ShowQrCode'];
            self::$sendEmails = $post['sendEmails'];
            self::$systemEmailAddress = $post['systemEmailAddress'];
            self::$applicationName = $post['applicationName'];
            $path = $modernConfigDir . '/config.json';
            $jsonString = file_get_contents($path);
            $jsonData = json_decode($jsonString, true);
            $jsonData['apiKey'] = $post['apiKey'];
            $jsonData['demoMode'] = $post['demoMode'];
            $jsonData['ShowQrCode'] = $post['ShowQrCode'];
            $jsonData['sendEmails'] = $post['sendEmails'];
            $jsonData['systemEmailAddress'] = $post['systemEmailAddress'];
            $jsonData['applicationName'] = $post['applicationName'];
            $jsonString = json_encode($jsonData, JSON_PRETTY_PRINT);
            // Write in the file
            $fp = fopen($path, 'w');
            fwrite($fp, $jsonString);
            fclose($fp);
        } catch (PDOException $err) {
            writeToLogFunction::write_to_log("error code: " . $err->getCode(), $_SERVER["SCRIPT_FILENAME"]);
        }
    }

    private static function detectSubDir($projectRoot, $legacyPublicRoot) {
        $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? "";
        $scriptFilename = $_SERVER['SCRIPT_FILENAME'] ?? "";
        $requestUri = $_SERVER['REQUEST_URI'] ?? "";

        if (($documentRoot !== "") && ($scriptFilename !== "") && str_starts_with($scriptFilename, $documentRoot)) {
            $relativeScript = str_replace($documentRoot, "", $scriptFilename);
            $relativeScript = str_replace('\\', '/', $relativeScript);

            if (str_starts_with($relativeScript, '/public/')) {
                return '/public' === dirname($relativeScript) ? '/public' : '/public';
            }

            foreach (array('/src/frontend/', '/src/receiver/', '/src/install/', '/src/simulator/', '/src/otafirmware/') as $prefix) {
                if (str_starts_with($relativeScript, $prefix)) {
                    return '/src';
                }
            }

            if (str_starts_with($relativeScript, '/src/')) {
                return '/src';
            }
        }

        if ($requestUri !== "") {
            if (strpos($requestUri, '/public/') !== false || str_ends_with($requestUri, '/public') || str_contains($requestUri, '/public?')) {
                return self::extractMountPath($requestUri, '/public');
            }

            foreach (array('/src/frontend/', '/src/receiver/', '/src/install/', '/src/simulator/', '/src/otafirmware/', '/src/') as $needle) {
                if (strpos($requestUri, $needle) !== false) {
                    return self::extractMountPath($requestUri, '/src');
                }
            }
        }

        if (is_dir($projectRoot . '/public')) {
            return self::pathFromDocumentRoot($projectRoot . '/public');
        }

        return self::pathFromDocumentRoot($legacyPublicRoot);
    }

    private static function extractMountPath($requestUri, $rootSegment) {
        $uriPath = parse_url($requestUri, PHP_URL_PATH);
        if ($uriPath === false || $uriPath === null) {
            $uriPath = $requestUri;
        }

        $position = strpos($uriPath, $rootSegment);
        if ($position === false) {
            return $rootSegment;
        }

        return substr($uriPath, 0, $position + strlen($rootSegment));
    }

    private static function pathFromDocumentRoot($absolutePath) {
        $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? "";
        if (($documentRoot !== "") && str_starts_with($absolutePath, $documentRoot)) {
            return rtrim(str_replace($documentRoot, "", $absolutePath), '/');
        }

        return rtrim($absolutePath, '/');
    }
}
