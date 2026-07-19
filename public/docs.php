<?php
require_once dirname(__DIR__) . '/bootstrap/app.php';
mds_set_request_language($_GET['lang'] ?? 'en');
mds_start_session_if_present();
mds_apply_public_cache_headers();
require_once dirname(__DIR__) . '/app/Application/myFunctions.func.php';
$mdsPageNeedsJquery = false;
$mdsPageNeedsBootstrapIcons = false;
$language = mds_current_language();
$isGerman = $language === 'de';
$topic = (string)($_GET['topic'] ?? 'features');
if (!in_array($topic, array('features', 'ttn', 'api', 'ota'), true)) {
    $topic = 'features';
}

$pages = array(
    'features' => array(
        'title' => $isGerman ? 'Telemetrie-Funktionen für Boot, Werkstatt und Feldtest' : 'Telemetry features for boats, workbenches and field tests',
        'lead' => $isGerman ? 'MDS bündelt Live-Werte, historische Verläufe, Positionsdaten und technische Ereignisse in einer responsiven Oberfläche.' : 'MDS combines live readings, historical trends, position data and technical events in one responsive interface.',
        'sections' => $isGerman ? array(
            array('Dashboard und Gauges', 'Boards und Sensoren erscheinen in einer klaren Live-Übersicht. Gauge-Stile, Einheiten, Farben und sichtbare Kanäle lassen sich je Sensor konfigurieren.'),
            array('Charts und ESP-Ereignisse', 'Zeitreihen vergleichen Sensoren pro Gerät. Wakeup-, Standby- und dauerhaft aktive Zustände werden als Zeitverlauf und Tagesdauer aufbereitet.'),
            array('Karten und Alarme', 'GPS-Sensoren erscheinen auf der Karte. Optional informiert MDS per E-Mail über Offline-Boards oder Werte außerhalb definierter Grenzbereiche.'),
            array('Rollen und Freigaben', 'Owner teilen Boards oder einzelne Sensoren. User dürfen Einstellungen bearbeiten; Observer sehen freigegebene Werte und können Ereignisnachrichten empfangen.'),
        ) : array(
            array('Dashboard and gauges', 'Boards and sensors appear in a focused live overview. Gauge style, unit, colors and visible channels can be configured per sensor.'),
            array('Charts and ESP events', 'Time series compare sensors by device. Wakeup, standby and always-online states are shown as timelines and daily durations.'),
            array('Maps and alerts', 'GPS sensors appear on a map. MDS can optionally send e-mail when boards go offline or readings cross configured thresholds.'),
            array('Roles and sharing', 'Owners share boards or individual sensors. Users can edit settings; observers can view shared readings and receive event notifications.'),
        ),
    ),
    'ttn' => array(
        'title' => $isGerman ? 'The Things Network mit MDS verbinden' : 'Connect The Things Network to MDS',
        'lead' => $isGerman ? 'Der TTN-Webhook nimmt dekodierte LoRaWAN-Uplinks an, prüft optional ein Shared Secret und leitet normalisierte Telemetrie an den Ingest weiter.' : 'The TTN webhook accepts decoded LoRaWAN uplinks, optionally verifies a shared secret and forwards normalized telemetry to ingest.',
        'sections' => $isGerman ? array(
            array('Webhook', 'Konfiguriere in TTN einen POST-Webhook auf /webhooks/ttn.php mit Content-Type application/json. Für produktive Installationen sollte X-MDS-Webhook-Secret gesetzt werden.'),
            array('Geräteerkennung', 'MDS verwendet decoded_payload.macAddress als primäre Identifikation. TTN application_id und device_id bleiben als Zuordnungshilfe verfügbar.'),
            array('Messwerte', 'Der Decoder kann unter anderem Spannung, Temperatur, Luftfeuchte, Druck, GPS, Zählerstände, Firmware-Version und den aktuellen ESP-Zustand liefern.'),
            array('Betrieb', 'Fehlende Boards und Sensor-Konfigurationen können automatisch angelegt werden. Jeder Verarbeitungsschritt wird ohne Klartext-Secrets protokolliert.'),
        ) : array(
            array('Webhook', 'Configure a TTN POST webhook to /webhooks/ttn.php with Content-Type application/json. Production installations should send X-MDS-Webhook-Secret.'),
            array('Device identity', 'MDS uses decoded_payload.macAddress as the primary identity. TTN application_id and device_id remain available as mapping hints.'),
            array('Readings', 'The decoder can provide voltage, temperature, humidity, pressure, GPS, counters, firmware version and the current ESP state.'),
            array('Operations', 'Missing boards and sensor configurations can be created automatically. Processing steps are logged without exposing secrets.'),
        ),
    ),
    'api' => array(
        'title' => $isGerman ? 'JSON-Ingest und Browser-API' : 'JSON ingest and browser API',
        'lead' => $isGerman ? 'Externe Geräte senden strukturierte Board- und Sensorinformationen an einen stabilen, versionierten Datenvertrag.' : 'External devices send structured board and sensor information through a stable, versioned data contract.',
        'sections' => $isGerman ? array(
            array('Ingest-Endpunkt', 'POST /ingest/receivejson.php erwartet board und sensors. apiKey, macAddress und protocolVersion schützen und identifizieren die Übertragung.'),
            array('Sensor-Zuordnung', 'sensorId ist optional. MDS kann einen Sensor anhand der Board-Konfiguration sowie sensorType und sensorName finden oder neu anlegen.'),
            array('Gerätestatus', 'firmwareVersion und standbyState ergänzen Messwerte um den Softwarestand sowie wakeup, standby oder always_online.'),
            array('Geschützte Browser-APIs', 'Dashboard-, Chart- und Kartenendpunkte verlangen eine gültige Session und prüfen die Ressourcenrechte des eingeloggten Users.'),
        ) : array(
            array('Ingest endpoint', 'POST /ingest/receivejson.php expects board and sensors. apiKey, macAddress and protocolVersion protect and identify the transmission.'),
            array('Sensor mapping', 'sensorId is optional. MDS can resolve a sensor from board configuration plus sensorType and sensorName, or create it.'),
            array('Device state', 'firmwareVersion and standbyState add software version and wakeup, standby or always_online state to telemetry.'),
            array('Protected browser APIs', 'Dashboard, chart and map endpoints require a valid session and verify the current user’s resource permissions.'),
        ),
    ),
    'ota' => array(
        'title' => $isGerman ? 'ESP32-Firmware sicher per OTA verteilen' : 'Deliver ESP32 firmware securely over OTA',
        'lead' => $isGerman ? 'Der MDS-OTA-Endpunkt trennt stabile und Beta-Kanäle, authentifiziert Geräteanfragen und macht den Update-Status sichtbar.' : 'The MDS OTA endpoint separates stable and beta channels, authenticates device requests and makes update status visible.',
        'sections' => $isGerman ? array(
            array('Endpunkt', 'GET /ota/getupdate.php verarbeitet die vom ESP32 gesendete MAC-Adresse, aktuelle Firmware-Version und den gewünschten Update-Kanal.'),
            array('Authentifizierung', 'Das Gerät sendet X-MDS-OTA-Secret. Der Server vergleicht den Wert mit dem in den Server-Einstellungen hinterlegten OTA-Secret.'),
            array('Auslieferung', 'Nur eine passende neuere Firmware wird ausgeliefert. Andernfalls antwortet der Endpunkt ohne unnötigen Binärtransfer.'),
            array('Nachvollziehbarkeit', 'OTA-Anfragen und Ergebnisse werden protokolliert und können vom Administrator in den Einstellungen eingesehen werden.'),
        ) : array(
            array('Endpoint', 'GET /ota/getupdate.php processes the MAC address, current firmware version and requested update channel sent by the ESP32.'),
            array('Authentication', 'The device sends X-MDS-OTA-Secret. The server compares it with the OTA secret stored in server settings.'),
            array('Delivery', 'Only a matching newer firmware is delivered. Otherwise the endpoint responds without an unnecessary binary transfer.'),
            array('Traceability', 'OTA requests and outcomes are logged and available to administrators in settings.'),
        ),
    ),
);
$page = $pages[$topic];
include_once dirname(__DIR__) . '/app/Presentation/Common/header.inc.php';
?>
<main class="mds-public-main">
  <article class="mds-topic-hero">
    <nav class="mds-breadcrumb" aria-label="Breadcrumb"><a href="<?php echo mds_h(mds_route_path(mds_seo_route('home', $language))); ?>">Maritime Data Server</a><span>/</span><span><?php echo mds_h($page['title']); ?></span></nav>
    <h1><?php echo mds_h($page['title']); ?></h1>
    <p><?php echo mds_h($page['lead']); ?></p>
  </article>
  <section class="mds-topic-grid">
    <?php foreach ($page['sections'] as $section) { ?>
      <article class="mds-topic-card"><h2><?php echo mds_h($section[0]); ?></h2><p><?php echo mds_h($section[1]); ?></p></article>
    <?php } ?>
  </section>
  <nav class="mds-related" aria-label="<?php echo mds_h($isGerman ? 'Weitere Themen' : 'Related topics'); ?>">
    <?php foreach (array('features', 'ttn', 'api', 'ota') as $related) { if ($related === $topic) { continue; } ?>
      <a href="<?php echo mds_h(mds_route_path(mds_seo_route($related, $language))); ?>"><?php echo mds_h(ucfirst($related === 'ttn' ? 'TTN' : $related)); ?></a>
    <?php } ?>
  </nav>
</main>
<?php include_once dirname(__DIR__) . '/app/Presentation/Common/footer.inc.php'; ?>
