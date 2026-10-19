# Datenschutz-Checkliste fuer Maritime Data Server

Diese Checkliste ist projektspezifisch und soll helfen, den Betrieb des Systems datenschutzfreundlicher aufzusetzen. Sie ist keine Rechtsberatung.

## Bereits technisch umgesetzt

- Privacy-Seite unter `/privacy.php`
- Impressum unter `/imprint.php`
- JSON-Datenexport fuer eingeloggte Nutzer unter `/privacy_export.php`
- Session-Cookies mit `HttpOnly`, `SameSite=Lax` und `Secure` bei HTTPS
- Remember-Login nicht mehr standardmaessig aktiviert
- Remember-Login-Cookies mit begrenzter Laufzeit von 30 Tagen
- anonymisierte IP-Adresse in Projektlogs
- Cleanup-Skript fuer alte Logs und alte Password-Reset-Codes:
  - `php tools/maintenance/privacy_cleanup.php`
- konfigurierbare Retention fuer:
  - allgemeine Telemetriedaten
  - GPS-Daten
  - TTN-Debug-Daten
  - Remember-Login-Tokens

## Vor produktivem Einsatz pruefen

- Datenschutzerklaerung fachlich/rechtlich finalisieren
- Impressum rechtlich finalisieren
- konkrete Rechtsgrundlagen je Verarbeitung dokumentieren:
  - Benutzerkonto
  - Telemetrie
  - GPS / Kartenansicht
  - E-Mail-Benachrichtigungen
  - technische Logs
- AVV/DPA mit Hosting-, Mail- und externen Plattformanbietern abschliessen
- Empfaenger / Dienstleister in der Datenschutzerklaerung benennen
- Verfahren fuer Betroffenenanfragen festlegen:
  - Auskunft
  - Berichtigung
  - Loeschung
  - Widerspruch

## Noch offen / empfehlenswert

- Retention-Regel fuer Telemetrie- und GPS-Daten definieren
- pruefen, ob die neuen Standard-Retention-Werte zum realen Einsatz passen
- optionalen Consent-/Hinweistext fuer Remember-Login klarer erklaeren
- Mailversand auf dedizierten SMTP-/Mailer-Transport mit besserem Fehlerhandling umstellen
- Privacy- und Impressum-Links auf allen oeffentlichen Seiten pruefen
- serverseitige Backups auf Datenschutz- und Aufbewahrungsfristen abstimmen

## Empfohlene Cronjobs

```bash
php tools/maintenance/sendmail.php
php tools/maintenance/privacy_cleanup.php
php tools/maintenance/log_cleanup.php
```

## Wichtige Konfigurationswerte

In `config/config.json` bzw. `config/config_template.json`:

- `privacyContactEmail`
- `logRetentionDays`
- `logMaxFileSizeMb`
- `passwordResetRetentionDays`
- `sendEmails`
- `systemEmailAddress`
