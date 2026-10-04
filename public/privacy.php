<?php

require_once dirname(__DIR__) . "/bootstrap/app.php";
mds_set_request_language($_GET['lang'] ?? 'en');
mds_start_session_if_present();
mds_apply_public_cache_headers();
$mdsPageNeedsJquery = false;
$mdsPageNeedsBootstrapIcons = false;
$mdsBodyClass = 'mds-public-page';

$config = new configuration();
$privacyContact = trim((string)($config::$privacyContactEmail ?: $config::$adminEmailAddress ?: $config::$systemEmailAddress));
$logRetentionDays = (int)($config::$logRetentionDays ?: 90);
$passwordResetRetentionDays = (int)($config::$passwordResetRetentionDays ?: 30);
$telemetryRetentionDays = (int)($config::$telemetryRetentionDays ?: 365);
$gpsRetentionDays = (int)($config::$gpsRetentionDays ?: 90);
$ttnDebugRetentionDays = (int)($config::$ttnDebugRetentionDays ?: 30);
$securityTokenRetentionDays = (int)($config::$securityTokenRetentionDays ?: 45);
$isGerman = mds_current_language() === 'de';

include_once dirname(__DIR__) . "/app/Presentation/Common/header.inc.php";
?>

<div class="container-xl main-container">
  <section class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4 p-lg-5">
      <h1 class="mb-3"><?php echo htmlspecialchars(mds_t('privacy.title'), ENT_QUOTES, 'UTF-8'); ?></h1>
      <p class="text-muted mb-4">
        <?php echo htmlspecialchars($isGerman
          ? 'Diese Seite beschreibt in komprimierter Form, welche personenbezogenen Daten im Maritime Data Server verarbeitet werden. Sie ersetzt keine rechtliche Einzelpruefung, hilft aber dabei, den Betrieb transparenter zu gestalten.'
          : 'This page summarizes which personal data is processed by Maritime Data Server. It does not replace a legal review, but it helps make the installation more transparent.', ENT_QUOTES, 'UTF-8'); ?>
      </p>

      <div class="row g-4">
        <div class="col-lg-6">
          <h3 class="h5"><?php echo htmlspecialchars($isGerman ? 'Verantwortliche Stelle' : 'Controller', ENT_QUOTES, 'UTF-8'); ?></h3>
          <p class="mb-0">
            <?php echo htmlspecialchars($config::$applicationName ?: 'Maritime Data Server', ENT_QUOTES, 'UTF-8'); ?><br>
            <?php echo htmlspecialchars($isGerman ? 'Kontakt fuer Datenschutzanfragen:' : 'Contact for privacy requests:', ENT_QUOTES, 'UTF-8'); ?>
            <?php if ($privacyContact !== '') { ?>
              <a href="mailto:<?php echo htmlspecialchars($privacyContact, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($privacyContact, ENT_QUOTES, 'UTF-8'); ?></a>
            <?php } else { ?>
              <?php echo htmlspecialchars($isGerman ? 'bitte im Betrieb hinterlegen' : 'please configure for production use', ENT_QUOTES, 'UTF-8'); ?>
            <?php } ?>
          </p>
        </div>
        <div class="col-lg-6">
          <h3 class="h5"><?php echo htmlspecialchars($isGerman ? 'Wesentliche Zwecke' : 'Main purposes', ENT_QUOTES, 'UTF-8'); ?></h3>
          <ul class="mb-0">
            <li><?php echo htmlspecialchars($isGerman ? 'Benutzerkonten verwalten und Zugriff absichern' : 'Manage user accounts and secure access', ENT_QUOTES, 'UTF-8'); ?></li>
            <li><?php echo htmlspecialchars($isGerman ? 'Board-, Sensor- und Telemetriedaten speichern und visualisieren' : 'Store and visualize board, sensor and telemetry data', ENT_QUOTES, 'UTF-8'); ?></li>
            <li><?php echo htmlspecialchars($isGerman ? 'GPS- und Zustandsdaten fuer Karten und Verlaeufe anzeigen' : 'Display GPS and status data for maps and timelines', ENT_QUOTES, 'UTF-8'); ?></li>
            <li><?php echo htmlspecialchars($isGerman ? 'Benachrichtigungen bei Offline-Boards oder kritischen Sensorwerten versenden' : 'Send notifications for offline boards or critical sensor values', ENT_QUOTES, 'UTF-8'); ?></li>
            <li><?php echo htmlspecialchars($isGerman ? 'technische Protokolle zur Fehleranalyse fuehren' : 'Keep technical logs for troubleshooting', ENT_QUOTES, 'UTF-8'); ?></li>
          </ul>
        </div>
      </div>

      <hr class="my-4">

      <div class="row g-4">
        <div class="col-lg-6">
          <h3 class="h5"><?php echo htmlspecialchars($isGerman ? 'Typische Rechtsgrundlagen' : 'Typical legal bases', ENT_QUOTES, 'UTF-8'); ?></h3>
          <ul class="mb-0">
            <li><?php echo htmlspecialchars($isGerman ? 'Vertrag / vorvertragliche Kommunikation fuer Benutzerkonto und Board-Verwaltung' : 'Contract or pre-contractual communication for user accounts and board management', ENT_QUOTES, 'UTF-8'); ?></li>
            <li><?php echo htmlspecialchars($isGerman ? 'berechtigtes Interesse fuer Betriebssicherheit, Fehleranalyse und Missbrauchsschutz' : 'Legitimate interest for operational security, troubleshooting and abuse prevention', ENT_QUOTES, 'UTF-8'); ?></li>
            <li><?php echo htmlspecialchars($isGerman ? 'Einwilligung oder gesonderte Aktivierung fuer optionale Benachrichtigungen, soweit im Einzelfall erforderlich' : 'Consent or explicit activation for optional notifications where required', ENT_QUOTES, 'UTF-8'); ?></li>
          </ul>
        </div>
        <div class="col-lg-6">
          <h3 class="h5"><?php echo htmlspecialchars($isGerman ? 'Externe Empfaenger' : 'External recipients', ENT_QUOTES, 'UTF-8'); ?></h3>
          <p class="mb-0 text-muted">
            <?php echo htmlspecialchars($isGerman
              ? 'Bitte konkret fuer jede produktive Installation dokumentieren, welche Hosting-, Mail- und IoT-/LoRa-Dienstleister eingesetzt werden und auf welcher vertraglichen Grundlage.'
              : 'For each production installation, document which hosting, mail and IoT/LoRa service providers are used and on which contractual basis.', ENT_QUOTES, 'UTF-8'); ?>
          </p>
        </div>
      </div>

      <hr class="my-4">

      <div class="row g-4">
        <div class="col-lg-6">
          <h3 class="h5"><?php echo htmlspecialchars($isGerman ? 'Verarbeitete Datenkategorien' : 'Processed data categories', ENT_QUOTES, 'UTF-8'); ?></h3>
          <ul class="mb-0">
            <li><?php echo htmlspecialchars($isGerman ? 'Kontodaten: E-Mail-Adresse, Vorname, Nachname, Zeitzone' : 'Account data: e-mail address, first name, last name, timezone', ENT_QUOTES, 'UTF-8'); ?></li>
            <li><?php echo htmlspecialchars($isGerman ? 'Authentifizierungsdaten: Session-Daten und optionale Remember-Login-Tokens' : 'Authentication data: session data and optional remember-login tokens', ENT_QUOTES, 'UTF-8'); ?></li>
            <li><?php echo htmlspecialchars($isGerman ? 'Geraete- und Board-Daten: MAC-Adresse, TTN-App-/Device-IDs, Board-Name' : 'Device and board data: MAC address, TTN app/device IDs, board name', ENT_QUOTES, 'UTF-8'); ?></li>
            <li><?php echo htmlspecialchars($isGerman ? 'Telemetrie: Sensorwerte, Zeitstempel, Statuswechsel, GPS-Koordinaten' : 'Telemetry: sensor values, timestamps, state changes, GPS coordinates', ENT_QUOTES, 'UTF-8'); ?></li>
            <li><?php echo htmlspecialchars($isGerman ? 'technische Protokolldaten: anonymisierte IP-Adresse, Request-Pfad, Fehlermeldungen' : 'Technical logs: anonymized IP address, request path, error messages', ENT_QUOTES, 'UTF-8'); ?></li>
          </ul>
        </div>
        <div class="col-lg-6">
          <h3 class="h5"><?php echo htmlspecialchars($isGerman ? 'Empfaenger / Dienstleister' : 'Recipients / service providers', ENT_QUOTES, 'UTF-8'); ?></h3>
          <ul class="mb-0">
            <li><?php echo htmlspecialchars($isGerman ? 'Hosting-Provider des Systems' : 'Hosting provider of the system', ENT_QUOTES, 'UTF-8'); ?></li>
            <li><?php echo htmlspecialchars($isGerman ? 'Mail-Infrastruktur fuer Registrierungs-, Reset- und Alarm-E-Mails' : 'Mail infrastructure for registration, reset and alert e-mails', ENT_QUOTES, 'UTF-8'); ?></li>
            <li><?php echo htmlspecialchars($isGerman ? 'The Things Network / TTN fuer LoRa-Uplink-Weiterleitung, sofern genutzt' : 'The Things Network / TTN for LoRa uplink forwarding, if used', ENT_QUOTES, 'UTF-8'); ?></li>
          </ul>
          <p class="small text-muted mt-3 mb-0">
            <?php echo htmlspecialchars($isGerman
              ? 'Fuer den produktiven Betrieb sollten passende AVV/DPA-Vertraege und eine konkrete Liste externer Dienstleister gepflegt werden.'
              : 'For production use, keep suitable DPA agreements and a concrete list of external service providers.', ENT_QUOTES, 'UTF-8'); ?>
          </p>
        </div>
      </div>

      <hr class="my-4">

      <h3 class="h5"><?php echo htmlspecialchars($isGerman ? 'Aufbewahrung und Loeschung' : 'Retention and deletion', ENT_QUOTES, 'UTF-8'); ?></h3>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th><?php echo htmlspecialchars($isGerman ? 'Datenart' : 'Data type', ENT_QUOTES, 'UTF-8'); ?></th>
              <th><?php echo htmlspecialchars($isGerman ? 'Aktueller technischer Stand' : 'Current technical status', ENT_QUOTES, 'UTF-8'); ?></th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><?php echo htmlspecialchars($isGerman ? 'Logdateien' : 'Log files', ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars($isGerman ? 'Cleanup-Skript vorgesehen, Standard-Retention derzeit' : 'Cleanup script available, current default retention', ENT_QUOTES, 'UTF-8'); ?> <?php echo (int)$logRetentionDays; ?> <?php echo htmlspecialchars($isGerman ? 'Tage.' : 'days.', ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
            <tr>
              <td><?php echo htmlspecialchars($isGerman ? 'Allgemeine Telemetriedaten' : 'General telemetry data', ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars($isGerman ? 'Cleanup-Skript vorgesehen, Standard-Retention derzeit' : 'Cleanup script available, current default retention', ENT_QUOTES, 'UTF-8'); ?> <?php echo (int)$telemetryRetentionDays; ?> <?php echo htmlspecialchars($isGerman ? 'Tage.' : 'days.', ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
            <tr>
              <td><?php echo htmlspecialchars($isGerman ? 'GPS-Daten' : 'GPS data', ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars($isGerman ? 'Cleanup-Skript vorgesehen, Standard-Retention derzeit' : 'Cleanup script available, current default retention', ENT_QUOTES, 'UTF-8'); ?> <?php echo (int)$gpsRetentionDays; ?> <?php echo htmlspecialchars($isGerman ? 'Tage.' : 'days.', ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
            <tr>
              <td><?php echo htmlspecialchars($isGerman ? 'TTN-Debug-Rohdaten' : 'TTN debug raw data', ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars($isGerman ? 'Cleanup-Skript vorgesehen, Standard-Retention derzeit' : 'Cleanup script available, current default retention', ENT_QUOTES, 'UTF-8'); ?> <?php echo (int)$ttnDebugRetentionDays; ?> <?php echo htmlspecialchars($isGerman ? 'Tage.' : 'days.', ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
            <tr>
              <td><?php echo htmlspecialchars($isGerman ? 'Password-Reset-Codes' : 'Password reset codes', ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars($isGerman ? 'Reset-Codes werden zeitlich begrenzt genutzt; Cleanup-Skript setzt abgelaufene Daten nach' : 'Reset codes are time-limited; the cleanup script clears expired data after', ENT_QUOTES, 'UTF-8'); ?> <?php echo (int)$passwordResetRetentionDays; ?> <?php echo htmlspecialchars($isGerman ? 'Tagen zurueck.' : 'days.', ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
            <tr>
              <td><?php echo htmlspecialchars($isGerman ? 'Remember-Login-Tokens' : 'Remember-login tokens', ENT_QUOTES, 'UTF-8'); ?></td>
              <td><?php echo htmlspecialchars($isGerman ? 'Remember-Login ist optional, Cookies laufen nach 30 Tagen ab, serverseitige Token werden standardmaessig nach' : 'Remember-login is optional, cookies expire after 30 days, server-side tokens are cleaned up by default after', ENT_QUOTES, 'UTF-8'); ?> <?php echo (int)$securityTokenRetentionDays; ?> <?php echo htmlspecialchars($isGerman ? 'Tagen bereinigt.' : 'days.', ENT_QUOTES, 'UTF-8'); ?></td>
            </tr>
          </tbody>
        </table>
      </div>

      <hr class="my-4">

      <div class="row g-4">
        <div class="col-lg-6">
          <h3 class="h5"><?php echo htmlspecialchars($isGerman ? 'Betroffenenrechte' : 'Data subject rights', ENT_QUOTES, 'UTF-8'); ?></h3>
          <ul class="mb-0">
            <li><?php echo htmlspecialchars($isGerman ? 'Auskunft / Datenkopie' : 'Access / data copy', ENT_QUOTES, 'UTF-8'); ?></li>
            <li><?php echo htmlspecialchars($isGerman ? 'Berichtigung' : 'Correction', ENT_QUOTES, 'UTF-8'); ?></li>
            <li><?php echo htmlspecialchars($isGerman ? 'Loeschung bzw. Einschraenkung der Verarbeitung' : 'Deletion or restriction of processing', ENT_QUOTES, 'UTF-8'); ?></li>
            <li><?php echo htmlspecialchars($isGerman ? 'Widerspruch gegen bestimmte Verarbeitungen' : 'Objection to certain processing activities', ENT_QUOTES, 'UTF-8'); ?></li>
          </ul>
        </div>
        <div class="col-lg-6">
          <h3 class="h5"><?php echo htmlspecialchars($isGerman ? 'Technische Unterstuetzung im System' : 'Technical support in the system', ENT_QUOTES, 'UTF-8'); ?></h3>
          <ul class="mb-0">
            <li><?php echo htmlspecialchars($isGerman ? 'Profil- und Benachrichtigungseinstellungen in den Settings' : 'Profile and notification settings', ENT_QUOTES, 'UTF-8'); ?></li>
            <li><?php echo htmlspecialchars($isGerman ? 'JSON-Datenexport fuer eingeloggte Nutzer' : 'JSON data export for logged-in users', ENT_QUOTES, 'UTF-8'); ?></li>
            <li><?php echo htmlspecialchars($isGerman ? 'anonymisierte IP-Adresse in den Projektlogs' : 'Anonymized IP address in project logs', ENT_QUOTES, 'UTF-8'); ?></li>
            <li><?php echo htmlspecialchars($isGerman ? 'gehaertete Session- und Remember-Login-Cookies' : 'Hardened session and remember-login cookies', ENT_QUOTES, 'UTF-8'); ?></li>
            <li><?php echo htmlspecialchars($isGerman ? 'Cleanup-Job fuer Logs, Reset-Codes, Telemetrie, GPS und TTN-Debug-Daten' : 'Cleanup job for logs, reset codes, telemetry, GPS and TTN debug data', ENT_QUOTES, 'UTF-8'); ?></li>
          </ul>
        </div>
      </div>
    </div>
  </section>
</div>

<?php
include_once dirname(__DIR__) . "/app/Presentation/Common/footer.inc.php";
?>
