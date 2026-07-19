<?php
require_once dirname(__DIR__) . '/bootstrap/app.php';
mds_set_request_language($_GET['lang'] ?? 'en');
mds_start_session_if_present();
mds_apply_public_cache_headers();
require_once dirname(__DIR__) . '/app/Infrastructure/Database/dbConfig.func.php';
require_once dirname(__DIR__) . '/app/Application/myFunctions.func.php';
require_once dirname(__DIR__) . '/app/Application/InternalPageService.php';
require_once dirname(__DIR__) . '/app/Domain/User/user.class.php';
$userObj = session_status() === PHP_SESSION_ACTIVE ? InternalPageService::resolveCurrentUserFromSession() : null;
$mdsPageNeedsJquery = false;
$mdsPageNeedsBootstrapIcons = false;
$language = mds_current_language();
$isGerman = $language === 'de';

$content = $isGerman ? array(
    'eyebrow' => 'Open-Source-Telemetrieplattform',
    'title' => 'Maritime Sensordaten werden zu klaren Entscheidungen.',
    'intro' => 'Maritime Data Server verbindet ESP32-, WLAN- und LoRaWAN-Geräte mit übersichtlichen Dashboards, Karten, Zeitreihen und Alarmen. Vom Batteriespannungswert bis zum Wakeup-Verlauf bleibt die gesamte Telemetrie an einem Ort nachvollziehbar.',
    'cta' => 'Funktionen entdecken',
    'secondary' => 'TTN anbinden',
    'section' => 'Vom Sensor bis zur Auswertung',
    'sectionText' => 'MDS übernimmt den kompletten Datenweg: Geräte eindeutig erkennen, Messwerte sicher annehmen und verständlich darstellen.',
    'cards' => array(
        array('Live-Dashboard', 'Aktuelle Gerätezustände, frei wählbare Gauges und relevante Sensorwerte auf einen Blick.', 'features'),
        array('The Things Network', 'LoRaWAN-Uplinks per Webhook empfangen und Boards bevorzugt anhand ihrer MAC-Adresse zuordnen.', 'ttn'),
        array('Offene Schnittstellen', 'JSON-Ingest für Sensorwerte, Firmware-Versionen, GPS-Positionen und ESP-Zustände.', 'api'),
        array('OTA-Firmware', 'ESP32-Firmware kontrolliert ausliefern und Update-Anfragen im Administrationsbereich nachvollziehen.', 'ota'),
    ),
    'proofTitle' => 'Für reale Geräteflotten entwickelt',
    'proofText' => 'Boards können mehrere Sensortypen kombinieren, zeitweise offline sein und Daten über unterschiedliche Übertragungswege senden. Rollen, Freigaben, Schwellwertalarme und Aufbewahrungsregeln unterstützen den dauerhaften Betrieb.',
) : array(
    'eyebrow' => 'Open-source telemetry platform',
    'title' => 'Turn maritime sensor data into clear decisions.',
    'intro' => 'Maritime Data Server connects ESP32, WiFi and LoRaWAN devices with focused dashboards, maps, time series and alerts. From battery voltage to wakeup timelines, your telemetry remains understandable in one place.',
    'cta' => 'Explore features',
    'secondary' => 'Connect TTN',
    'section' => 'From sensor to insight',
    'sectionText' => 'MDS covers the complete data path: identify devices, accept readings securely and present them in a useful form.',
    'cards' => array(
        array('Live dashboard', 'See current device states, configurable gauges and important sensor values at a glance.', 'features'),
        array('The Things Network', 'Receive LoRaWAN uplinks through a webhook and identify boards primarily by their MAC address.', 'ttn'),
        array('Open interfaces', 'Use JSON ingest for sensor readings, firmware versions, GPS positions and ESP states.', 'api'),
        array('OTA firmware', 'Deliver ESP32 firmware in a controlled way and inspect update requests in the admin area.', 'ota'),
    ),
    'proofTitle' => 'Built for real device fleets',
    'proofText' => 'Boards can combine several sensor types, spend time offline and use different transmission paths. Roles, sharing, threshold alerts and retention controls support reliable long-term operation.',
);

include_once dirname(__DIR__) . '/app/Presentation/Common/header.inc.php';
?>
<main class="mds-public-main">
  <section class="mds-public-hero">
    <div class="mds-public-hero-copy">
      <span class="mds-eyebrow"><?php echo mds_h($content['eyebrow']); ?></span>
      <h1><?php echo mds_h($content['title']); ?></h1>
      <p><?php echo mds_h($content['intro']); ?></p>
      <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-primary btn-lg" href="<?php echo mds_h(mds_route_path(mds_seo_route('features', $language))); ?>"><?php echo mds_h($content['cta']); ?></a>
        <a class="btn btn-outline-light btn-lg" href="<?php echo mds_h(mds_route_path(mds_seo_route('ttn', $language))); ?>"><?php echo mds_h($content['secondary']); ?></a>
      </div>
    </div>
    <img src="<?php echo mds_h(mds_asset_path('img/img_ESP32.png')); ?>" width="200" height="200" alt="<?php echo mds_h($isGerman ? 'ESP32-Sensormodul für maritime Telemetrie' : 'ESP32 sensor module for maritime telemetry'); ?>">
  </section>

  <section class="mds-public-section" aria-labelledby="data-path-heading">
    <div class="mds-section-heading">
      <h2 id="data-path-heading"><?php echo mds_h($content['section']); ?></h2>
      <p><?php echo mds_h($content['sectionText']); ?></p>
    </div>
    <div class="mds-feature-grid">
      <?php foreach ($content['cards'] as $card) { ?>
        <article class="mds-feature-card">
          <h3><?php echo mds_h($card[0]); ?></h3>
          <p><?php echo mds_h($card[1]); ?></p>
          <a href="<?php echo mds_h(mds_route_path(mds_seo_route($card[2], $language))); ?>"><?php echo mds_h($isGerman ? 'Mehr erfahren' : 'Learn more'); ?> <span aria-hidden="true">&rarr;</span></a>
        </article>
      <?php } ?>
    </div>
  </section>

  <section class="mds-public-proof">
    <div>
      <span class="mds-eyebrow"><?php echo mds_h($isGerman ? 'Praxisnah' : 'Operational by design'); ?></span>
      <h2><?php echo mds_h($content['proofTitle']); ?></h2>
    </div>
    <p><?php echo mds_h($content['proofText']); ?></p>
  </section>
</main>
<?php include_once dirname(__DIR__) . '/app/Presentation/Common/footer.inc.php'; ?>
