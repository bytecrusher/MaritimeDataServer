<?php
$guides = $isGerman ? array(
    'features' => array(
        'heading' => 'In drei Schritten zum Dashboard',
        'steps' => array('Konto registrieren und den Aktivierungslink in der E-Mail bestaetigen.', 'Ein Board mit seiner MAC-Adresse verbinden. API-Key und Webhook-Secret nur auf vertrauenswuerdigen Geraeten hinterlegen.', 'Unter Meine Sensoren die Namen und Einheiten pruefen. Unter Charts den gewuenschten Zeitraum in den Dashboard-Einstellungen auswaehlen.'),
        'check' => 'Pruefe nach einer Uebertragung den Messwert und seinen Zeitpunkt. Ein Online-Status allein bestaetigt nicht, dass alle Sensoren aktuelle Daten liefern.',
    ),
    'ttn' => array(
        'heading' => 'TTN Schritt fuer Schritt einrichten',
        'steps' => array('In MDS als Administrator ein TTN-Webhook-Secret konfigurieren. Dieses Secret ist nicht der Ingest-API-Key.', 'In der TTN-Anwendung unter Integrations > Webhooks einen Custom Webhook mit JSON-Format erstellen. Base URL: ' . mds_absolute_url('') . ' und Uplink message path: /webhooks/ttn.php. Den Header X-MDS-Webhook-Secret mit dem gleichen Secret setzen.', 'Unter Payload formatters > Uplink den zur Firmware passenden Decoder hinterlegen. Auf jedem Endgeraet pruefen, ob ein eigener Formatter den Application-Formatter ueberschreibt.', 'Einen echten Uplink senden. In TTN Live data f_port, frm_payload und decoded_payload pruefen. In MDS kontrollieren, ob MAC-Adresse, Messwert und Zeitpunkt dem richtigen Board zugeordnet sind.'),
        'check' => 'HTTP 200: Weiterleitung verarbeitet. HTTP 202: kein Anwendungs-Payload. HTTP 403: Secret pruefen. HTTP 422: Payload nicht verarbeitbar. HTTP 502: interner Ingest fehlgeschlagen. Bei einer Schema-Warnung den Decoder mit der Firmware abgleichen; nicht nur die Warnung ausblenden.',
    ),
    'api' => array(
        'heading' => 'Einen Testmesswert senden',
        'steps' => array('API-Key beim Administrator erhalten und MAC-Adresse sowie Sensor-ID aus der eigenen Board-Konfiguration verwenden. Keine fremde Sensor-ID einsetzen.', 'Im Beispiel unten alle Platzhalter und den Messzeitpunkt ersetzen. date und time erwarten die lokale Zeit Europe/Berlin; diese alte Schnittstelle hat noch kein separates Zeitzonenfeld.', 'Die JSON-Datei als measurement.json ablegen und mit dem gezeigten POST-Aufruf senden. Das legt einen echten Messwert an: nur mit einem eigenen Testboard ausfuehren.', 'HTTP 200 und insertedSensorRows: 1 sowie skippedSensorRows: 0 pruefen. Anschliessend den Messwert im Dashboard und im Chart kontrollieren.'),
        'check' => 'HTTP 400: JSON und protocolVersion pruefen. HTTP 401: API-Key falsch. HTTP 405: POST verwenden. HTTP 500: Administrator soll das Serverlog pruefen. Auch bei HTTP 200 die Zaehler fuer eingefuegte und uebersprungene Sensoren beachten.',
    ),
    'ota' => array(
        'heading' => 'OTA-Verbindung pruefen',
        'steps' => array('In den Server-Einstellungen das OTA-Update-Secret setzen und identisch im Device hinterlegen. Secret nicht in die URL schreiben.', 'Das Board mit der korrekten MAC-Adresse anlegen, den gewuenschten Kanal waehlen und die Firmware bereitstellen. Ein Download erfordert die Update-Freigabe des Boards.', 'Die Firmware muss GET /ota/getupdate.php?channel=stable mit User-Agent ESP32-http-Update und den unten genannten Headern senden.', 'HTTP-Status und X-MDS-OTA-Status auswerten. Nach der Installation die neue Firmware-Version vom Device uebertragen lassen und im Dashboard kontrollieren.'),
        'check' => 'HTTP 403: Secret, User-Agent und Pflicht-Header pruefen. HTTP 404: Board-MAC oder Firmware-Datei fehlt. HTTP 304: Firmware identisch oder Update deaktiviert; X-MDS-OTA-Status unterscheidet diese Faelle. HTTP 200: Firmware-Binaerdatei, noch keine Bestaetigung einer erfolgreichen Installation.',
    ),
) : array(
    'features' => array(
        'heading' => 'Three steps to your dashboard',
        'steps' => array('Register an account and confirm the activation link in your email.', 'Connect a board using its MAC address. Store API keys and webhook secrets only on trusted devices.', 'Check names and units under My sensors. Select the desired chart period in your dashboard settings.'),
        'check' => 'After an upload, verify the value and its timestamp. An online status alone does not prove that every sensor has fresh readings.',
    ),
    'ttn' => array(
        'heading' => 'Connect TTN step by step',
        'steps' => array('As an MDS administrator, configure a TTN webhook secret. This is separate from the ingest API key.', 'In the TTN application, create a Custom Webhook under Integrations > Webhooks with JSON format. Base URL: ' . mds_absolute_url('') . ' and Uplink message path: /webhooks/ttn.php. Set X-MDS-Webhook-Secret to the same secret.', 'Under Payload formatters > Uplink, install the decoder matching your firmware. Check each end device for a formatter overriding the application default.', 'Send a real uplink. Inspect f_port, frm_payload and decoded_payload in TTN Live data. Verify the MAC address, reading and timestamp on the correct MDS board.'),
        'check' => 'HTTP 200: forwarding processed. HTTP 202: no application payload. HTTP 403: check the secret. HTTP 422: payload cannot be processed. HTTP 502: internal ingest failed. A schema warning calls for matching the decoder to the firmware, not hiding the warning.',
    ),
    'api' => array(
        'heading' => 'Send a test reading',
        'steps' => array('Obtain the API key from your administrator and use the MAC address and sensor ID from your own board configuration.', 'Replace all placeholders and the measurement time below. date and time expect Europe/Berlin wall time; this older interface has no separate timezone field.', 'Store the JSON as measurement.json and send the POST request shown below. This creates a real reading: use your own test board only.', 'Check HTTP 200, insertedSensorRows: 1 and skippedSensorRows: 0. Then verify the reading in both the dashboard and chart.'),
        'check' => 'HTTP 400: check JSON and protocolVersion. HTTP 401: incorrect API key. HTTP 405: use POST. HTTP 500: ask an administrator to inspect the server log. Even with HTTP 200, check the inserted and skipped sensor counters.',
    ),
    'ota' => array(
        'heading' => 'Verify OTA connectivity',
        'steps' => array('Configure the same OTA update secret in server settings and on the device. Do not put secrets in URLs.', 'Register the correct board MAC, select a channel and provide the firmware. Downloads require the board update permission to be enabled.', 'The firmware must send GET /ota/getupdate.php?channel=stable with User-Agent ESP32-http-Update and the headers below.', 'Inspect the HTTP status and X-MDS-OTA-Status. After installation, have the device report its new firmware version and verify it on the dashboard.'),
        'check' => 'HTTP 403: check the secret, User-Agent and required headers. HTTP 404: missing board MAC or firmware file. HTTP 304: identical firmware or updates disabled; X-MDS-OTA-Status distinguishes these cases. HTTP 200: firmware binary, not confirmation of a successful installation.',
    ),
);
$guide = $guides[$topic];
$sample = array('board' => array('apiKey' => 'REPLACE_WITH_API_KEY', 'macAddress' => 'REPLACE_WITH_BOARD_MAC', 'protocolVersion' => '1'), 'sensors' => array(array('sensorId' => 'REPLACE_WITH_SENSOR_ID', 'value1' => 12.6, 'date' => 'REPLACE_WITH_DD.MM.YYYY', 'time' => 'REPLACE_WITH_HH:MM:SS', 'transmissionPath' => '1')));
?>
<section class="mds-guide" aria-labelledby="guide-heading">
  <h2 id="guide-heading"><?php echo mds_h($guide['heading']); ?></h2>
  <ol><?php foreach ($guide['steps'] as $step) { ?><li><?php echo mds_h($step); ?></li><?php } ?></ol>
  <?php if ($topic === 'api') { ?>
    <pre tabindex="0" aria-label="JSON measurement example"><code><?php echo mds_h(json_encode($sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></code></pre>
    <pre tabindex="0" aria-label="HTTP POST example"><code><?php echo mds_h('curl --request POST ' . escapeshellarg(mds_absolute_url('ingest/receivejson.php')) . " \\\n  --header 'Content-Type: application/json' \\\n  --data-binary @measurement.json"); ?></code></pre>
    <p><?php echo $isGerman ? 'Beispiel einer erfolgreichen Antwort (IDs und Zaehler variieren):' : 'Example success response (IDs and counters vary):'; ?></p>
    <pre tabindex="0" aria-label="JSON response example"><code>{"status":"ok","boardId":10,"insertedSensorRows":1,"skippedSensorRows":0,"autoResolvedSensorRows":0,"autoCreatedSensorConfigs":0}</code></pre>
  <?php } elseif ($topic === 'ota') { ?>
    <pre tabindex="0" aria-label="Required OTA request headers"><code>User-Agent: ESP32-http-Update
X-MDS-OTA-Secret: REPLACE_WITH_OTA_SECRET
x-ESP32-STA-MAC: REPLACE_WITH_BOARD_MAC
x-ESP32-sketch-md5: REPLACE_WITH_INSTALLED_SKETCH_MD5
x-ESP32-sdk-version: REPLACE_WITH_SDK_VERSION
x-ESP32-version: REPLACE_WITH_INSTALLED_FIRMWARE_VERSION</code></pre>
  <?php } ?>
  <h3><?php echo $isGerman ? 'Ergebnis und Fehlerhilfe' : 'Results and troubleshooting'; ?></h3>
  <p><?php echo mds_h($guide['check']); ?></p>
</section>
