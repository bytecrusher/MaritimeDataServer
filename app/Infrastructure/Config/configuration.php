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
    static $otaUpdateSecret = null;
    static $defaultGaugeStyle = null;
    static $defaultDashboardOnlineOnly = null;
    static $defaultChartWindowDays = null;
    static $privacyContactEmail = null;
    static $logRetentionDays = null;
    static $logMaxFileSizeMb = null;
    static $passwordResetRetentionDays = null;
    static $telemetryRetentionDays = null;
    static $gpsRetentionDays = null;
    static $ttnDebugRetentionDays = null;
    static $securityTokenRetentionDays = null;
    static $imprintCompanyName = null;
    static $imprintAddress = null;
    static $imprintEmail = null;
    static $imprintPhone = null;
    static $googleSiteVerification = null;
    
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

            self::$otaUpdateSecret = "";
            if (array_key_exists('otaUpdateSecret', $jsonData)) {
                self::$otaUpdateSecret = (string)$jsonData['otaUpdateSecret'];
            }

            self::$defaultGaugeStyle = 'classic';
            if (array_key_exists('defaultGaugeStyle', $jsonData) && is_string($jsonData['defaultGaugeStyle']) && $jsonData['defaultGaugeStyle'] !== '') {
                self::$defaultGaugeStyle = $jsonData['defaultGaugeStyle'];
            }

            self::$defaultDashboardOnlineOnly = '0';
            if (array_key_exists('defaultDashboardOnlineOnly', $jsonData)) {
                self::$defaultDashboardOnlineOnly = (string)$jsonData['defaultDashboardOnlineOnly'];
            }

            self::$defaultChartWindowDays = '7';
            if (array_key_exists('defaultChartWindowDays', $jsonData) && is_numeric($jsonData['defaultChartWindowDays'])) {
                self::$defaultChartWindowDays = (string)$jsonData['defaultChartWindowDays'];
            }

            self::$privacyContactEmail = '';
            if (array_key_exists('privacyContactEmail', $jsonData)) {
                self::$privacyContactEmail = (string)$jsonData['privacyContactEmail'];
            }
            if (self::$privacyContactEmail === '') {
                self::$privacyContactEmail = (string)(self::$adminEmailAddress ?: self::$systemEmailAddress);
            }

            self::$logRetentionDays = '90';
            if (array_key_exists('logRetentionDays', $jsonData) && is_numeric($jsonData['logRetentionDays'])) {
                self::$logRetentionDays = (string)min(3650, max(1, (int)$jsonData['logRetentionDays']));
            }

            self::$logMaxFileSizeMb = '10';
            if (array_key_exists('logMaxFileSizeMb', $jsonData) && is_numeric($jsonData['logMaxFileSizeMb'])) {
                self::$logMaxFileSizeMb = (string)min(1024, max(1, (int)$jsonData['logMaxFileSizeMb']));
            }

            self::$passwordResetRetentionDays = '30';
            if (array_key_exists('passwordResetRetentionDays', $jsonData) && is_numeric($jsonData['passwordResetRetentionDays'])) {
                self::$passwordResetRetentionDays = (string)max(1, (int)$jsonData['passwordResetRetentionDays']);
            }

            self::$telemetryRetentionDays = '365';
            if (array_key_exists('telemetryRetentionDays', $jsonData) && is_numeric($jsonData['telemetryRetentionDays'])) {
                self::$telemetryRetentionDays = (string)max(1, (int)$jsonData['telemetryRetentionDays']);
            }

            self::$gpsRetentionDays = '90';
            if (array_key_exists('gpsRetentionDays', $jsonData) && is_numeric($jsonData['gpsRetentionDays'])) {
                self::$gpsRetentionDays = (string)max(1, (int)$jsonData['gpsRetentionDays']);
            }

            self::$ttnDebugRetentionDays = '30';
            if (array_key_exists('ttnDebugRetentionDays', $jsonData) && is_numeric($jsonData['ttnDebugRetentionDays'])) {
                self::$ttnDebugRetentionDays = (string)max(1, (int)$jsonData['ttnDebugRetentionDays']);
            }

            self::$securityTokenRetentionDays = '45';
            if (array_key_exists('securityTokenRetentionDays', $jsonData) && is_numeric($jsonData['securityTokenRetentionDays'])) {
                self::$securityTokenRetentionDays = (string)max(1, (int)$jsonData['securityTokenRetentionDays']);
            }

            self::$imprintCompanyName = (string)($jsonData['imprintCompanyName'] ?? self::$applicationName ?? '');
            self::$imprintAddress = (string)($jsonData['imprintAddress'] ?? '');
            self::$imprintEmail = (string)($jsonData['imprintEmail'] ?? self::$adminEmailAddress ?? self::$systemEmailAddress ?? '');
            self::$imprintPhone = (string)($jsonData['imprintPhone'] ?? '');
            self::$googleSiteVerification = trim((string)($jsonData['googleSiteVerification'] ?? ''));

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
            $allowedGaugeStyles = array('classic', 'minimal', 'bold', 'arc', 'ring', 'clock', 'industrial');
            $defaultGaugeStyle = $post['defaultGaugeStyle'] ?? 'classic';
            if (!in_array($defaultGaugeStyle, $allowedGaugeStyles, true)) {
                $defaultGaugeStyle = 'classic';
            }
            $defaultChartWindowDays = isset($post['defaultChartWindowDays']) ? (int)$post['defaultChartWindowDays'] : 7;
            if (!in_array($defaultChartWindowDays, array(1, 7, 14, 30), true)) {
                $defaultChartWindowDays = 7;
            }
            if (!is_dir($modernConfigDir)) {
                mkdir($modernConfigDir, 0775, true);
            }
            self::$apiKey = $post['apiKey'];
            self::$demoMode = $post['demoMode'];
            self::$ShowQrCode = $post['ShowQrCode'];
            self::$sendEmails = $post['sendEmails'];
            self::$systemEmailAddress = $post['systemEmailAddress'];
            self::$applicationName = $post['applicationName'];
            self::$otaUpdateSecret = trim((string)($post['otaUpdateSecret'] ?? self::$otaUpdateSecret));
            self::$defaultGaugeStyle = $defaultGaugeStyle;
            self::$defaultDashboardOnlineOnly = $post['defaultDashboardOnlineOnly'] ?? '0';
            self::$defaultChartWindowDays = (string)$defaultChartWindowDays;
            self::$privacyContactEmail = trim((string)($post['privacyContactEmail'] ?? self::$privacyContactEmail));
            self::$logRetentionDays = (string)min(3650, max(1, (int)($post['logRetentionDays'] ?? self::$logRetentionDays ?: 90)));
            self::$logMaxFileSizeMb = (string)min(1024, max(1, (int)($post['logMaxFileSizeMb'] ?? self::$logMaxFileSizeMb ?: 10)));
            self::$passwordResetRetentionDays = (string)max(1, (int)($post['passwordResetRetentionDays'] ?? self::$passwordResetRetentionDays ?: 30));
            self::$telemetryRetentionDays = (string)max(1, (int)($post['telemetryRetentionDays'] ?? self::$telemetryRetentionDays ?: 365));
            self::$gpsRetentionDays = (string)max(1, (int)($post['gpsRetentionDays'] ?? self::$gpsRetentionDays ?: 90));
            self::$ttnDebugRetentionDays = (string)max(1, (int)($post['ttnDebugRetentionDays'] ?? self::$ttnDebugRetentionDays ?: 30));
            self::$securityTokenRetentionDays = (string)max(1, (int)($post['securityTokenRetentionDays'] ?? self::$securityTokenRetentionDays ?: 45));
            self::$imprintCompanyName = trim((string)($post['imprintCompanyName'] ?? self::$imprintCompanyName));
            self::$imprintAddress = trim((string)($post['imprintAddress'] ?? self::$imprintAddress));
            self::$imprintEmail = trim((string)($post['imprintEmail'] ?? self::$imprintEmail));
            self::$imprintPhone = trim((string)($post['imprintPhone'] ?? self::$imprintPhone));
            self::$googleSiteVerification = trim((string)($post['googleSiteVerification'] ?? self::$googleSiteVerification));
            $path = $modernConfigDir . '/config.json';
            $jsonString = file_exists($path) ? file_get_contents($path) : false;
            $jsonData = $jsonString !== false ? json_decode($jsonString, true) : array();
            if (!is_array($jsonData)) {
                $jsonData = array();
            }
            $jsonData['apiKey'] = $post['apiKey'];
            $jsonData['demoMode'] = $post['demoMode'];
            $jsonData['ShowQrCode'] = $post['ShowQrCode'];
            $jsonData['sendEmails'] = $post['sendEmails'];
            $jsonData['systemEmailAddress'] = $post['systemEmailAddress'];
            $jsonData['applicationName'] = $post['applicationName'];
            $jsonData['otaUpdateSecret'] = self::$otaUpdateSecret;
            $jsonData['defaultGaugeStyle'] = self::$defaultGaugeStyle;
            $jsonData['defaultDashboardOnlineOnly'] = self::$defaultDashboardOnlineOnly;
            $jsonData['defaultChartWindowDays'] = self::$defaultChartWindowDays;
            $jsonData['privacyContactEmail'] = self::$privacyContactEmail;
            $jsonData['logRetentionDays'] = self::$logRetentionDays;
            $jsonData['logMaxFileSizeMb'] = self::$logMaxFileSizeMb;
            $jsonData['passwordResetRetentionDays'] = self::$passwordResetRetentionDays;
            $jsonData['telemetryRetentionDays'] = self::$telemetryRetentionDays;
            $jsonData['gpsRetentionDays'] = self::$gpsRetentionDays;
            $jsonData['ttnDebugRetentionDays'] = self::$ttnDebugRetentionDays;
            $jsonData['securityTokenRetentionDays'] = self::$securityTokenRetentionDays;
            $jsonData['imprintCompanyName'] = self::$imprintCompanyName;
            $jsonData['imprintAddress'] = self::$imprintAddress;
            $jsonData['imprintEmail'] = self::$imprintEmail;
            $jsonData['imprintPhone'] = self::$imprintPhone;
            $jsonData['googleSiteVerification'] = self::$googleSiteVerification;
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
            '/en/',
            '/de/',
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
