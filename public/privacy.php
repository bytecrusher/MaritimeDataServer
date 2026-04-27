<?php

require_once dirname(__DIR__) . "/bootstrap/app.php";
mds_start_session();

$config = new configuration();
$privacyContact = trim((string)($config::$privacyContactEmail ?: $config::$adminEmailAddress ?: $config::$systemEmailAddress));
$logRetentionDays = (int)($config::$logRetentionDays ?: 90);
$passwordResetRetentionDays = (int)($config::$passwordResetRetentionDays ?: 30);
$telemetryRetentionDays = (int)($config::$telemetryRetentionDays ?: 365);
$gpsRetentionDays = (int)($config::$gpsRetentionDays ?: 90);
$ttnDebugRetentionDays = (int)($config::$ttnDebugRetentionDays ?: 30);
$securityTokenRetentionDays = (int)($config::$securityTokenRetentionDays ?: 45);

include_once dirname(__DIR__) . "/app/Presentation/Common/header.inc.php";
?>

<div class="container-xl main-container">
  <section class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4 p-lg-5">
      <h1 class="mb-3">Datenschutz</h1>
      <p class="text-muted mb-4">
        Diese Seite beschreibt in komprimierter Form, welche personenbezogenen Daten im Maritime Data Server verarbeitet werden.
        Sie ersetzt keine rechtliche Einzelpruefung, hilft aber dabei, den Betrieb transparenter zu gestalten.
      </p>

      <div class="row g-4">
        <div class="col-lg-6">
          <h3 class="h5">Verantwortliche Stelle</h3>
          <p class="mb-0">
            <?php echo htmlspecialchars($config::$applicationName ?: 'Maritime Data Server', ENT_QUOTES, 'UTF-8'); ?><br>
            Kontakt fuer Datenschutzanfragen:
            <?php if ($privacyContact !== '') { ?>
              <a href="mailto:<?php echo htmlspecialchars($privacyContact, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($privacyContact, ENT_QUOTES, 'UTF-8'); ?></a>
            <?php } else { ?>
              bitte im Betrieb hinterlegen
            <?php } ?>
          </p>
        </div>
        <div class="col-lg-6">
          <h3 class="h5">Wesentliche Zwecke</h3>
          <ul class="mb-0">
            <li>Benutzerkonten verwalten und Zugriff absichern</li>
            <li>Board-, Sensor- und Telemetriedaten speichern und visualisieren</li>
            <li>GPS- und Zustandsdaten fuer Karten und Verlaeufe anzeigen</li>
            <li>Benachrichtigungen bei Offline-Boards oder kritischen Sensorwerten versenden</li>
            <li>technische Protokolle zur Fehleranalyse fuehren</li>
          </ul>
        </div>
      </div>

      <hr class="my-4">

      <div class="row g-4">
        <div class="col-lg-6">
          <h3 class="h5">Typische Rechtsgrundlagen</h3>
          <ul class="mb-0">
            <li>Vertrag / vorvertragliche Kommunikation fuer Benutzerkonto und Board-Verwaltung</li>
            <li>berechtigtes Interesse fuer Betriebssicherheit, Fehleranalyse und Missbrauchsschutz</li>
            <li>Einwilligung oder gesonderte Aktivierung fuer optionale Benachrichtigungen, soweit im Einzelfall erforderlich</li>
          </ul>
        </div>
        <div class="col-lg-6">
          <h3 class="h5">Externe Empfaenger</h3>
          <p class="mb-0 text-muted">
            Bitte konkret fuer jede produktive Installation dokumentieren, welche Hosting-, Mail- und IoT-/LoRa-Dienstleister eingesetzt werden und auf welcher vertraglichen Grundlage.
          </p>
        </div>
      </div>

      <hr class="my-4">

      <div class="row g-4">
        <div class="col-lg-6">
          <h3 class="h5">Verarbeitete Datenkategorien</h3>
          <ul class="mb-0">
            <li>Kontodaten: E-Mail-Adresse, Vorname, Nachname, Zeitzone</li>
            <li>Authentifizierungsdaten: Session-Daten und optionale Remember-Login-Tokens</li>
            <li>Geraete- und Board-Daten: MAC-Adresse, TTN-App-/Device-IDs, Board-Name</li>
            <li>Telemetrie: Sensorwerte, Zeitstempel, Statuswechsel, GPS-Koordinaten</li>
            <li>technische Protokolldaten: anonymisierte IP-Adresse, Request-Pfad, Fehlermeldungen</li>
          </ul>
        </div>
        <div class="col-lg-6">
          <h3 class="h5">Empfaenger / Dienstleister</h3>
          <ul class="mb-0">
            <li>Hosting-Provider des Systems</li>
            <li>Mail-Infrastruktur fuer Registrierungs-, Reset- und Alarm-E-Mails</li>
            <li>The Things Network / TTN fuer LoRa-Uplink-Weiterleitung, sofern genutzt</li>
          </ul>
          <p class="small text-muted mt-3 mb-0">
            Fuer den produktiven Betrieb sollten passende AVV/DPA-Vertraege und eine konkrete Liste externer Dienstleister gepflegt werden.
          </p>
        </div>
      </div>

      <hr class="my-4">

      <h3 class="h5">Aufbewahrung und Loeschung</h3>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead>
            <tr>
              <th>Datenart</th>
              <th>Aktueller technischer Stand</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Logdateien</td>
              <td>Cleanup-Skript vorgesehen, Standard-Retention derzeit <?php echo (int)$logRetentionDays; ?> Tage.</td>
            </tr>
            <tr>
              <td>Allgemeine Telemetriedaten</td>
              <td>Cleanup-Skript vorgesehen, Standard-Retention derzeit <?php echo (int)$telemetryRetentionDays; ?> Tage.</td>
            </tr>
            <tr>
              <td>GPS-Daten</td>
              <td>Cleanup-Skript vorgesehen, Standard-Retention derzeit <?php echo (int)$gpsRetentionDays; ?> Tage.</td>
            </tr>
            <tr>
              <td>TTN-Debug-Rohdaten</td>
              <td>Cleanup-Skript vorgesehen, Standard-Retention derzeit <?php echo (int)$ttnDebugRetentionDays; ?> Tage.</td>
            </tr>
            <tr>
              <td>Password-Reset-Codes</td>
              <td>Reset-Codes werden zeitlich begrenzt genutzt; Cleanup-Skript setzt abgelaufene Daten nach <?php echo (int)$passwordResetRetentionDays; ?> Tagen zurueck.</td>
            </tr>
            <tr>
              <td>Remember-Login-Tokens</td>
              <td>Remember-Login ist optional, Cookies laufen nach 30 Tagen ab, serverseitige Token werden standardmaessig nach <?php echo (int)$securityTokenRetentionDays; ?> Tagen bereinigt.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <hr class="my-4">

      <div class="row g-4">
        <div class="col-lg-6">
          <h3 class="h5">Betroffenenrechte</h3>
          <ul class="mb-0">
            <li>Auskunft / Datenkopie</li>
            <li>Berichtigung</li>
            <li>Loeschung bzw. Einschraenkung der Verarbeitung</li>
            <li>Widerspruch gegen bestimmte Verarbeitungen</li>
          </ul>
        </div>
        <div class="col-lg-6">
          <h3 class="h5">Technische Unterstuetzung im System</h3>
          <ul class="mb-0">
            <li>Profil- und Benachrichtigungseinstellungen in den Settings</li>
            <li>JSON-Datenexport fuer eingeloggte Nutzer</li>
            <li>anonymisierte IP-Adresse in den Projektlogs</li>
            <li>gehaertete Session- und Remember-Login-Cookies</li>
            <li>Cleanup-Job fuer Logs, Reset-Codes, Telemetrie, GPS und TTN-Debug-Daten</li>
          </ul>
        </div>
      </div>
    </div>
  </section>
</div>

<?php
include_once dirname(__DIR__) . "/app/Presentation/Common/footer.inc.php";
?>
