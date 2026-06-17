<?PHP
/**
 * Handle request from collectors (MDC) and delivering firmware updates to MDCs
 * // TODO: MDC send request direct to git
 * 
 * @author: Guntmar Hoeche
 * @license: TBD
 */

// Script checking if a newer firmware exist and the configuration allows to update.
// If yes, the script delivers the firmware to the controller
header('Content-type: text/plain; charset=utf8', true);
$headers = array_change_key_case(getallheaders(), CASE_LOWER);

require_once(dirname(__DIR__, 2) . "/bootstrap/app.php");
require_once(dirname(__DIR__, 2) . "/app/Application/myFunctions.func.php");

$otaDefaultFirmwareName = 'firmware';

function check_header($name, $value = false) {
    global $headers;
    $name = strtolower((string)$name);
    if(!isset($headers[$name])) {
        return false;
    }
    if($value && $headers[$name] != $value) {
        return false;
    }
    return true;
}

function require_ota_secret() {
    global $headers;
    $config = new configuration();
    $expectedSecret = (string)$config::$otaUpdateSecret;
    if ($expectedSecret === '') {
        header($_SERVER["SERVER_PROTOCOL"].' 403 Forbidden', true, 403);
        write_to_log("OTA update secret is not configured.");
        echo "OTA update secret is not configured.\n";
        exit();
    }

    $providedSecret = (string)($headers['x-mds-ota-secret'] ?? '');
    if ($providedSecret === '' || !hash_equals($expectedSecret, $providedSecret)) {
        header($_SERVER["SERVER_PROTOCOL"].' 403 Forbidden', true, 403);
        write_to_log("OTA update secret validation failed.");
        echo "OTA update secret validation failed.\n";
        exit();
    }
}

function ota_response($statusCode, $message) {
    header($_SERVER["SERVER_PROTOCOL"].' ' . (int)$statusCode . ' ' . $message, true, (int)$statusCode);
    write_to_log($message);
    echo $message . "\n";
    exit();
}

function sendFile($path) {
    if (!is_file($path)) {
        ota_response(404, "firmware file not found: " . basename($path));
    }

    header($_SERVER["SERVER_PROTOCOL"].' 200 OK', true, 200);
    header('Content-Type: application/octet-stream', true);
    header('Content-Disposition: attachment; filename='.basename($path));
    header('Content-Length: '.filesize($path), true);
    header('x-MD5: '.md5_file($path), true);
    readfile($path);
}

if(!check_header('User-Agent', 'ESP32-http-Update')) {
    header($_SERVER["SERVER_PROTOCOL"].' 403 Forbidden', true, 403);
    write_to_log("only for ESP32 updater!");
    echo "only for ESP32 updater!\n";
    exit();
}

require_ota_secret();

if(
    !check_header('x-ESP32-STA-MAC') ||
    //!check_header('X-Esp32-AP-MAC') ||
    //!check_header('X-Esp32-free-space') ||
    //!check_header('X-Esp32-sketch-size') ||
    !check_header('x-ESP32-sketch-md5') ||
    //!check_header('X-Esp32-chip-size') ||
    !check_header('x-ESP32-sdk-version') ||
    !check_header('x-ESP32-version')
) {
    header($_SERVER["SERVER_PROTOCOL"].' 403 Forbidden', true, 403);

    $logString = "";
    $logString = "STA mac " . ($headers['x-esp32-sta-mac'] ?? '') .
    ", host " . ($headers['host'] ?? '') .
    ", AP mac " . ($headers['x-esp32-ap-mac'] ?? '') .
    ", free space " . ($headers['x-esp32-free-space'] ?? '') .
    ", sketch size " . ($headers['x-esp32-sketch-size'] ?? '') .
    ", sketch md5 " . ($headers['x-esp32-sketch-md5'] ?? '') .
    ", chip size " . ($headers['x-esp32-chip-size'] ?? '') .
    ", version " . ($headers['x-esp32-version'] ?? '') .
    ", sdk version " . ($headers['x-esp32-sdk-version'] ?? '');

    write_to_log($logString);
    echo "only for ESP32 updater! (header)\n";
    exit();
}

$espMac = myFunctions::normalizeMacAddress((string)$headers['x-esp32-sta-mac']);
$board = myFunctions::getBoardByMacAddress($espMac);
if(!$board) {
    ota_response(404, "ESP MAC not configured for updates: " . $espMac);
}

$firmwareName = $otaDefaultFirmwareName;
$localBinary = dirname(__DIR__, 2) . "/var/ota/bin/" . $firmwareName . ".bin";
if (!is_file($localBinary)) {
    ota_response(404, "firmware file not found: " . basename($localBinary));
}

$currentSketchMd5 = strtolower(trim((string)($headers['x-esp32-sketch-md5'] ?? '')));
$serverBinaryMd5 = strtolower((string)md5_file($localBinary));
$currentVersion = trim((string)($headers['x-esp32-version'] ?? ''));
$knownDeviceVersion = trim((string)($board['firmwareVersion'] ?? ''));
$performUpdate = (int)($board['performUpdate'] ?? 0) === 1;

write_to_log(array(
    'otaRequest' => 'resolved',
    'boardId' => $board['id'] ?? null,
    'macAddress' => $espMac,
    'performUpdate' => $performUpdate ? '1' : '0',
    'deviceVersionHeader' => $currentVersion,
    'storedDeviceVersion' => $knownDeviceVersion,
    'deviceSketchMd5' => $currentSketchMd5,
    'serverBinaryMd5' => $serverBinaryMd5,
    'firmwareFile' => basename($localBinary)
));

if (!$performUpdate) {
    header('x-MDS-OTA-Status: disabled', true);
    write_to_log("OTA disabled for device: " . $espMac);
    header($_SERVER["SERVER_PROTOCOL"].' 304 Not Modified', true, 304);
    exit();
}

if ($currentSketchMd5 !== '' && hash_equals($serverBinaryMd5, $currentSketchMd5)) {
    header('x-MDS-OTA-Status: current', true);
    write_to_log("OTA firmware already current: " . $espMac);
    header($_SERVER["SERVER_PROTOCOL"].' 304 Not Modified', true, 304);
    exit();
}

write_to_log("send file to " . $espMac);
sendFile($localBinary);
exit();

function write_to_log($text)
{
    $format = "csv"; //possile: csv and txt
    $datum_zeit = date("d.m.Y H:i:s");
    $site = $_SERVER['REQUEST_URI'];
    $logDir = dirname(__DIR__, 2) . "/var/ota/logs";
    if (!is_dir($logDir)) {
        mkdir($logDir, 0775, true);
    }
    $dateiname = $logDir . "/log.$format";
    $header = array("Datum", "File", "Log");

    $newvalue = "";
    if (is_array($text)) {
        foreach ($text as $value => $v) {
            $newvalue = $newvalue . $value . ": " . $v . ", ";
        }
    } else {
        $newvalue = $text;
    }
    
    $infos = array($datum_zeit, $site, $newvalue);
    if ($format == "csv") {
        $eintrag2 = '"' . implode('", "', $infos) . '"';
    } else {
        $eintrag2 = implode("\t", $infos);
    }
    $write_header = !file_exists($dateiname);
    $datei = fopen($dateiname, "a");
    if ($write_header) {
        if ($format == "csv") {
            $header_line = '"' . implode('", "', $header) . '"';
        } else {
            $header_line = implode("\t", $header);
        }
        fputs($datei, $header_line . "\n");
    }
    fputs($datei, $eintrag2 . "\n");
    fclose($datei);
}
