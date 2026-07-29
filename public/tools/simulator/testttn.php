<?php
/**
 * File for testing the TTN-style API endpoint `ttn.php`.
 *
 * Baut aus den POST-Daten ein TTN-ähnliches JSON und schickt
 * es per HTTP(S) an die im Formular gewählte Ziel-URL.
 *
 * @author  Guntmar Hoeche
 * @license TBD
 */

require_once dirname(__DIR__, 3) . "/bootstrap/app.php";
require_once dirname(__DIR__, 3) . "/app/Application/myFunctions.func.php";
require_once dirname(__DIR__, 3) . "/app/Infrastructure/Logging/writeToLogFunction.func.php";
require_once __DIR__ . "/mdsSimulatorConfig.php";

// Einheitlicher Response-Typ für das Ajax im Simulator
header('Content-Type: text/plain; charset=utf-8');
mds_start_session();
if (empty($_SESSION['userId']) || !myFunctions::isUserAdmin((int)$_SESSION['userId'])) {
    http_response_code(403);
    echo 'Admin permissions required.';
    exit;
}
if (!mds_verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo 'Invalid CSRF token.';
    exit;
}

// -------------------------------------------------------
// Eingaben validieren
// -------------------------------------------------------
$url = trim($_POST['url'] ?? '');
if ($url === '') {
    echo 'Fehler: Keine Ziel-URL angegeben.';
    exit;
}

if (!filter_var($url, FILTER_VALIDATE_URL)) {
    echo 'Fehler: Ungültige Ziel-URL.';
    exit;
}

$scheme = parse_url($url, PHP_URL_SCHEME);
if (!in_array($scheme, ['http', 'https'], true)) {
    echo 'Fehler: Nur http/https-URLs sind erlaubt.';
    exit;
}

function mds_simulator_normalize_url($url)
{
    $parts = parse_url($url);
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
        return null;
    }

    $scheme = strtolower((string)$parts['scheme']);
    if (!in_array($scheme, ['http', 'https'], true)) {
        return null;
    }

    $host = strtolower((string)$parts['host']);
    $port = isset($parts['port']) ? (int)$parts['port'] : null;
    $portPart = '';
    if ($port !== null && !(($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443))) {
        $portPart = ':' . $port;
    }

    $path = $parts['path'] ?? '/';
    return $scheme . '://' . $host . $portPart . $path;
}

function mds_simulator_allowed_urls()
{
    $config = new configuration();
    $simulatorConfig = new mdsSimulatorConfig();
    $urls = array((string)$config::$baseurl . '/webhooks/ttn.php');
    foreach ((array)$simulatorConfig::$mdsDestination as $destinationUrl) {
        $urls[] = (string)$destinationUrl;
    }

    $normalized = array();
    foreach ($urls as $allowedUrl) {
        $normalizedUrl = mds_simulator_normalize_url($allowedUrl);
        if ($normalizedUrl !== null) {
            $normalized[$normalizedUrl] = true;
        }
    }

    return $normalized;
}

$normalizedUrl = mds_simulator_normalize_url($url);
if ($normalizedUrl === null || !isset(mds_simulator_allowed_urls()[$normalizedUrl])) {
    http_response_code(403);
    echo 'Fehler: Ziel-URL ist nicht in der Simulator-Allowlist.';
    exit;
}

// Sensor-/TTN-Werte aus POST holen, mit Defaults und Typkonvertierung
$ttnCounter  = (int)($_POST['ttncounter'] ?? 0);
$dewpoint    = (float)($_POST['dewpoint'] ?? 0);
$humidity    = (float)($_POST['humidity'] ?? 0);
$latitude    = (float)($_POST['latitude'] ?? 0);
$longitude   = (float)($_POST['longitude'] ?? 0);
$level1      = (float)($_POST['level1'] ?? 0);
$level2      = (float)($_POST['level2'] ?? 0);
$pressure    = (float)($_POST['pressure'] ?? 0);
$tempbattery = (float)($_POST['tempbattery'] ?? 0);
$temperature = (float)($_POST['temperature'] ?? 0);
$voltage     = (float)($_POST['voltage'] ?? 0);
$voltage2    = (float)($_POST['voltage2'] ?? 0);

// -------------------------------------------------------
// Basisdaten für TTN-Payload
// -------------------------------------------------------
$dev_eui        = "devEuiSimulator";
$application_id = "ttnSimulator";
$gateway_id     = "simulatorGateway";

// TTN-Zeitstempel sind UTC mit 'Z'-Suffix
date_default_timezone_set('UTC');

// Basis-Zeitstempel ohne Fractional-Seconds
$timestampBase = gmdate('Y-m-d\TH:i:s');

// Für die Felder in der Payload bauen wir feste Fractional-Seconds an –
// für die Simulation reicht das vollkommen aus.
$timestampReceived     = $timestampBase . ".864652960Z";
$timestampRxMeta       = $timestampBase . ".611876010Z";
$timestampUplinkRecv   = $timestampBase . ".657373330Z";

// -------------------------------------------------------
// Payload aufbauen
// -------------------------------------------------------
$payloadArray = [
    "end_device_ids" => [
        "device_id"        => "eui-" . $dev_eui,
        "application_ids"  => [
            "application_id" => $application_id,
        ],
        "dev_eui"          => $dev_eui,
        "dev_addr"         => "ABCDEFGH",
    ],
    "correlation_ids" => [
        "as:up:01G635544G2R7TXKEVJ0JXFP6B",
        "gs:conn:01G6268WMXK0X0GWBCNBK9F3YZ",
        "gs:up:host:01G6268WN5TTBP5CJDEHTZ2VWE",
        "gs:uplink:01G63553Y0PN39R0TWN33X9E5T",
        "ns:uplink:01G63553Y1VD0Z6J49E0MZ18ZV",
        "rpc:/ttn.lorawan.v3.GsNs/HandleUplink:01G63553Y1S0DJ5D0QK2PHQ9RW",
        "rpc:/ttn.lorawan.v3.NsAs/HandleUplink:01G635544F71RHD4A6MGPGBM57",
    ],
    "received_at" => $timestampReceived,
    "uplink_message" => [
        "f_port"       => 1,
        "f_cnt"        => 6,
        "frm_payload"  => "ghredFHTeFiLhDbffzewSderGjKLJgfftrO",
        "decoded_payload" => [
            "mainPowerOn" => 1,              // Main switch on / 12 V / always on
            "altitude"    => 1,              // GPS
            "counter"     => $ttnCounter,    // TTN Frame counter
            "dewpoint"    => $dewpoint,      // BME280
            "hdop"        => "1.1",          // BME280?
            "humidity"    => $humidity,      // BME280
            "latitude"    => $latitude,      // GPS
            "level1"      => $level1,        // ADC Value
            "level2"      => $level2,        // ADC Value
            "longitude"   => $longitude,     // GPS
            "position"    => [               // GPS
                "context" => [
                    "lat" => 0,
                    "lng" => 0,
                ],
                "value"   => 0,
            ],
            "pressure"    => $pressure,      // BME280
            "relay"       => 0,              // Relais status
            "tempbattery" => $tempbattery,
            "temperature" => $temperature,   // BME280
            "voltage"     => $voltage,
            "voltage2"    => $voltage2,
        ],
        "rx_metadata" => [
            [
                "gateway_ids" => [
                    "gateway_id" => $gateway_id,
                    "eui"        => $dev_eui,
                ],
                "time"         => $timestampRxMeta,
                "timestamp"    => 2316218076,
                "rssi"         => -35,
                "channel_rssi" => -35,
                "snr"          => 9.75,
                "uplink_token" => "CisKKQoddGhldGhpbmdzaW5kb29ybG9yYXdhbmdhdGV3YXkSCFigy//+gEdsg5Tgh8Uw3rwInv7GlQYQy7D+uAIg4JbsyrSuByoMCJ7+xpUGEKr54aMC",
            ],
        ],
        "settings" => [
            "data_rate" => [
                "lora" => [
                    "bandwidth"        => 125000,
                    "spreading_factor" => 10,
                ],
            ],
            "coding_rate" => "4/5",
            "frequency"   => "868100000",
            "timestamp"   => 2316218076,
            "time"        => $timestampRxMeta,
        ],
        "received_at"       => $timestampUplinkRecv,
        "consumed_airtime"  => "0.534528s",
        "network_ids"       => [
            "net_id"         => "000013",
            "tenant_id"      => "ttn",
            "cluster_id"     => "eu1",
            "cluster_address"=> "eu1.cloud.thethings.network",
        ],
    ],
];

$payloadJson = json_encode($payloadArray);
if ($payloadJson === false) {
    writeToLogFunction::write_to_log(
        'JSON encoding failed: ' . json_last_error_msg(),
        $_SERVER["SCRIPT_FILENAME"]
    );
    echo 'Fehler: Konnte JSON-Payload nicht erzeugen.';
    exit;
}

// -------------------------------------------------------
// cURL-Request ausführen
// -------------------------------------------------------
$cURL = null;

try {
    $cURL = curl_init($url);

    if ($cURL === false) {
        writeToLogFunction::write_to_log('cURL: failed to initialize', $_SERVER["SCRIPT_FILENAME"]);
        $cURL = null;
        throw new Exception('failed to initialize');
    }

    curl_setopt($cURL, CURLOPT_POST, true);
    curl_setopt($cURL, CURLOPT_POSTFIELDS, $payloadJson);
    curl_setopt($cURL, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($cURL);

    if ($result === false) {
        $errNo  = curl_errno($cURL);
        $errMsg = curl_error($cURL);
        writeToLogFunction::write_to_log(
            'cURL error #' . $errNo . ': ' . $errMsg,
            $_SERVER["SCRIPT_FILENAME"]
        );
        throw new Exception($errMsg, $errNo);
    }

    $httpReturnCode = curl_getinfo($cURL, CURLINFO_HTTP_CODE);
    if ($httpReturnCode < 200 || $httpReturnCode >= 300) {
        writeToLogFunction::write_to_log(
            "HTTP Error: " . $httpReturnCode . " – Response: " . $result,
            $_SERVER["SCRIPT_FILENAME"]
        );
        echo "HTTP-Fehler: " . $httpReturnCode;
    } else {
        echo "Daten gesendet";
    }
} catch (Exception $e) {
    writeToLogFunction::write_to_log(
        'Curl failed with error: ' . $e->getCode() . ' ' . $e->getMessage(),
        $_SERVER["SCRIPT_FILENAME"]
    );
    echo 'Fehler: ' . htmlspecialchars(
        sprintf('Curl #%d: %s', $e->getCode(), $e->getMessage()),
        ENT_QUOTES,
        'UTF-8'
    );
} finally {
    if ($cURL !== null) {
        curl_close($cURL);
    }
}
