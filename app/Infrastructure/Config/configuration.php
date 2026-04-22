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
    static $mountPath = null;
    static $demoMode = null;
    static $md5secretString = null;
    static $installFinished = null;
    static $adminEmailAddress = null;
    static $ShowQrCode = null;
    static $sendEmails = null;
    static $systemEmailAddress = null;
    static $applicationName = null;
    static $ttnWebhookSecret = null;
    
    function __construct() {
        $projectRoot = dirname(__FILE__, 4);
        $modernConfigDir = $projectRoot . '/config';
        self::$mountPath = self::detectMountPath($projectRoot);
        self::$subDir = self::$mountPath;

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

            self::$ttnWebhookSecret = "";
            if (array_key_exists('ttnWebhookSecret', $jsonData)) {
                self::$ttnWebhookSecret = (string)$jsonData['ttnWebhookSecret'];
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

    private static function detectMountPath($projectRoot) {
        $requestUri = $_SERVER['REQUEST_URI'] ?? "";
        $requestPath = parse_url($requestUri, PHP_URL_PATH);
        if ($requestPath === false || $requestPath === null) {
            $requestPath = "";
        }

        $mountPathFromRequest = self::detectMountPathFromRequest($requestPath);
        if ($mountPathFromRequest !== null) {
            return $mountPathFromRequest;
        }

        $scriptFilename = $_SERVER['SCRIPT_FILENAME'] ?? "";
        $publicRoot = realpath($projectRoot . '/public');
        $realScriptFilename = $scriptFilename !== "" ? realpath($scriptFilename) : false;

        if ($publicRoot !== false && $realScriptFilename !== false && str_starts_with($realScriptFilename, $publicRoot)) {
            $relativeScript = substr($realScriptFilename, strlen($publicRoot));
            $relativeScript = str_replace('\\', '/', $relativeScript);
            if ($relativeScript === '') {
                $relativeScript = '/index.php';
            }

            if ($requestPath !== '') {
                if (str_ends_with($requestPath, $relativeScript)) {
                    return self::normalizeMountPath(substr($requestPath, 0, -strlen($relativeScript)));
                }

                if ($relativeScript === '/index.php' || $relativeScript === '/index.html') {
                    return self::normalizeMountPath(dirname($requestPath));
                }
            }
        }

        $scriptName = $_SERVER['SCRIPT_NAME'] ?? "";
        if ($scriptName !== "" && !self::looksLikeFilesystemPath($scriptName, $projectRoot)) {
            return self::normalizeMountPath(dirname(str_replace('\\', '/', $scriptName)));
        }

        return '';
    }

    private static function detectMountPathFromRequest($requestPath) {
        if ($requestPath === '' || $requestPath === '/') {
            return '';
        }

        $knownPrefixes = array(
            '/index.php',
            '/login.php',
            '/logout.php',
            '/internal.php',
            '/settings.php',
            '/formBoards.php',
            '/formSensors.php',
            '/register.php',
            '/resetPassword.php',
            '/activate.php',
            '/openstreetmaps.php',
            '/api/',
            '/webhooks/',
            '/ingest/',
            '/ota/',
            '/install/',
            '/tools/',
            '/debug/',
            '/receiver/',
            '/frontend/',
            '/simulator/',
            '/otafirmware/',
        );

        foreach ($knownPrefixes as $prefix) {
            $position = strpos($requestPath, $prefix);
            if ($position !== false) {
                return self::normalizeMountPath(substr($requestPath, 0, $position));
            }
        }

        return null;
    }

    private static function looksLikeFilesystemPath($path, $projectRoot) {
        $normalizedPath = str_replace('\\', '/', (string) $path);
        $normalizedProjectRoot = str_replace('\\', '/', $projectRoot);

        return str_starts_with($normalizedPath, $normalizedProjectRoot)
            || str_starts_with($normalizedPath, '/var/')
            || str_starts_with($normalizedPath, '/home/')
            || str_contains($normalizedPath, '/httpdocs/')
            || str_contains($normalizedPath, '/public_html/');
    }

    private static function normalizeMountPath($path) {
        if ($path === false || $path === null) {
            return '';
        }

        $normalizedPath = str_replace('\\', '/', (string) $path);
        if ($normalizedPath === '/' || $normalizedPath === '.' || $normalizedPath === '') {
            return '';
        }

        return rtrim($normalizedPath, '/');
    }
}
