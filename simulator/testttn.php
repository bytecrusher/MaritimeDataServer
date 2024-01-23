<?php
/**
 * File for testing the ttn API "ttn.php".
 * 
 * @author: Guntmar Hoeche
 * @license: TBD
 */

$url = $_POST['url'];
$dev_eui = "devEuiSimulator";
$application_id = "ttnSimulator";
$gateway_id = "simulatorGateway";

$datum = date("Y-m-d");
$zeit = date("H:i:s.");
$timestamp = $datum . "-T" . $zeit;

try {
    // Create a new cURL resource
    $cURL = curl_init($url);

    // Check if initialization had gone wrong*    
    if ($cURL === false) {
        throw new Exception('failed to initialize');
    }
    $payload = json_encode(array(
        "end_device_ids" => array(
            "device_id" => "eui-" . $dev_eui,
            "application_ids" => array(
                "application_id" => $application_id
            ),
            "dev_eui" => $dev_eui,
            "dev_addr" => "ABCDEFGH"
        ),
        "correlation_ids" => [
            "as:up:01G635544G2R7TXKEVJ0JXFP6B",
            "gs:conn:01G6268WMXK0X0GWBCNBK9F3YZ",
            "gs:up:host:01G6268WN5TTBP5CJDEHTZ2VWE",
            "gs:uplink:01G63553Y0PN39R0TWN33X9E5T",
            "ns:uplink:01G63553Y1VD0Z6J49E0MZ18ZV",
            "rpc:/ttn.lorawan.v3.GsNs/HandleUplink:01G63553Y1S0DJ5D0QK2PHQ9RW",
            "rpc:/ttn.lorawan.v3.NsAs/HandleUplink:01G635544F71RHD4A6MGPGBM57"
        ],
        "received_at" => $timestamp . ".864652960Z",
        "uplink_message" => array(
            "f_port" => 1,
            "f_cnt" => 6,
            "frm_payload" => "ghredFHTeFiLhDbffzewSderGjKLJgfftrO",
            "decoded_payload" => array(
                "alarm1" => 1,                  // Digi
                "altitude" => 1,                // GPS
                "counter" => $_POST['ttncounter'],                 // TTN Frame counter
                "dewpoint" => $_POST['dewpoint'],              // BME280
                "hdop" => "1.1",                // BME280?
                "humidity" => $_POST['humidity'],             // BME280
                "latitude" => $_POST['latitude'],                // GPS
                "level1" => $_POST['level1'],             // ADC Value
                "level2" => $_POST['level2'],             // ADC Value
                "longitude" => $_POST['longitude'],               // GPS
                "position" => array(            // GPS
                    "context" => array(
                        "lat" => 0,"lng" => 0
                    ),
                    "value" => 0
                ),
                "pressure" => $_POST['pressure'],             // BME250
                "relay" => 0,                   // Relais status
                "tempbattery" => $_POST['tempbattery'],
                "temperature" => $_POST['temperature'],       // BME280
                "voltage" => $_POST['voltage'],
                "voltage2" => $_POST['voltage2'],
            ),
            "rx_metadata" => array(
                array(
                    "gateway_ids" => array(
                        "gateway_id" => $gateway_id,
                        "eui" => $dev_eui
                    ),
                    "time" => $timestamp . ".611876010Z",
                    "timestamp" => 2316218076,
                    "rssi" => -35,
                    "channel_rssi" => -35,
                    "snr" => 9.75,
                    "uplink_token" => "CisKKQoddGhldGhpbmdzaW5kb29ybG9yYXdhbmdhdGV3YXkSCFigy//+gEdsg5Tgh8Uw3rwInv7GlQYQy7D+uAIg4JbsyrSuByoMCJ7+xpUGEKr54aMC"
                )
            ),
            "settings" => array(
                "data_rate" => array(
                    "lora" => array(
                        "bandwidth" => 125000,
                        "spreading_factor" => 10
                    )
                ),
                    "coding_rate" => "4/5",
                    "frequency" => "868100000",
                    "timestamp" => 2316218076,
                    "time" => $timestamp . ".611876010Z"
            ),
            "received_at" => $timestamp . ".657373330Z",
            "consumed_airtime" => "0.534528s",
            "network_ids" => array(
                "net_id" => "000013",
                "tenant_id" => "ttn",
                "cluster_id" => "eu1",
                "cluster_address" => "eu1.cloud.thethings.network"
            )
        )
    ));

    curl_setopt($cURL, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($cURL, CURLOPT_SSL_VERIFYHOST,  2);

    // Attach encoded JSON string to the POST fields
    curl_setopt($cURL, CURLOPT_POSTFIELDS, $payload);

    // Set the content type to application/json
    curl_setopt($cURL, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));

    // Return response instead of outputting
    curl_setopt($cURL, CURLOPT_RETURNTRANSFER, true);

    // Execute the POST request
    $result = curl_exec($cURL);

    // Check the return value of curl_exec(), too
    if ($result === false) {
        throw new Exception(curl_error($cURL), curl_errno($cURL));
    }

    // Check HTTP return code, too; might be something else than 200
    $httpReturnCode = curl_getinfo($cURL, CURLINFO_HTTP_CODE);
    if (!$httpReturnCode) {
        echo("Error: " . $httpReturnCode);
    } else {
        echo("Data send");
    }

} catch(Exception $e) {
    trigger_error(sprintf(
        'Curl failed with error #%d: %s',
        $e->getCode(), $e->getMessage()),
        E_USER_ERROR);
} finally {
    // Close curl handle unless it failed to initialize
    if (is_resource($cURL)) {
        // Close cURL resource
        curl_close($cURL);
    }
}