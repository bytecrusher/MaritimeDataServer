# API And External Interfaces

Stand: 2026-04-22

Diese Datei dokumentiert die aktuell aktiven HTTP-Schnittstellen des Maritime Data Server (MDS) mit Fokus auf:

- externe Anbindungen wie The Things Network (TTN)
- Browser-APIs des Dashboards
- OTA-Firmware-Update fuer ESP32
- Simulator fuer Test-Uplinks
- automatische E-Mail-Benachrichtigungen

Maschinenlesbare API-Beschreibung:

- [docs/openapi.yaml](/Users/guntmar/Documents/Docker/MDS/mds_from_workdir/public_html/maritimedataserver/docs/openapi.yaml:1)

Basisannahme dieser Doku:

- `public/` ist der einzige Webroot
- alle unten genannten Pfade sind relativ zur Basis-URL der Installation
- Beispiel: `https://mds-git.derguntmar.de`


## Uebersicht

Die wichtigsten oeffentlichen Endpunkte sind:

- `POST /webhooks/ttn.php`
  - externer TTN-Webhook fuer Uplinks
- `POST /ingest/receivejson.php`
  - interner JSON-Ingest fuer Sensorwerte
- `POST /api/getdata.php`
  - Dashboard-Gauge-Werte
- `GET /api/getSensorDataSet.php`
  - Zeitreihen fuer Charts
- `POST /api/getBoardName.php`
  - Board-Namen fuer die Kartenansicht
- `POST /api/getGpsData.php`
  - GPS-Daten fuer die Kartenansicht
- `POST /api/checkSession.php`
  - Session-Check fuer das Frontend
- `POST /api/updateData.php`
  - Speichert z. B. Sensor-Reihenfolge
- `GET /ota/getupdate.php`
  - ESP32-OTA-Update-Endpunkt
- `GET|POST /tools/simulator/index.php`
  - TTN-Simulator-Oberflaeche

Die wichtigsten nicht-oeffentlichen Wartungspfade sind:

- `php tools/maintenance/sendmail.php`
  - verarbeitet Offline- und Sensor-Schwellwert-Benachrichtigungen
- `php tools/maintenance/checkBoardOnline.php`
  - einfacher CLI-Check fuer Board-Online-Status
- `var/status/notification_status.json`
  - letzter Status des Benachrichtigungsjobs mit Summen und Zeitstempeln


## Rollen und Zugriffsrechte

MDS verwendet ab der Migration `docs/db_design/migrations/2026-07-06_roles_and_permissions.sql` ein zweistufiges Rechtemodell:

- Globale Rollen:
  - `admin`: administrativer Vollzugriff
  - `user`: normaler eingeloggter Benutzer
- Ressourcenrollen:
  - `owner`: darf Board/Sensor sehen, bearbeiten, Benutzer verwalten und Benachrichtigungen erhalten
  - `user`: darf Board/Sensor sehen, Einstellungen bearbeiten und Benachrichtigungen erhalten
  - `observer`: darf freigegebene Sensoren sehen und Benachrichtigungen erhalten, aber keine Einstellungen bearbeiten

Die neuen Tabellen sind:

- `roles`
- `user_roles`
- `board_permissions`
- `sensor_permissions`
- `permission_audit_log`

Bestehende `boardConfig.ownerUserId`-Zuordnungen bleiben kompatibel. Die Migration uebernimmt sie automatisch als `owner`-Eintrag in `board_permissions`. Solange die neuen Tabellen auf einer Installation noch fehlen, faellt der Code auf das bisherige Owner-Verhalten zurueck.

Auswirkungen auf Schnittstellen:

- Lese-APIs liefern Boards/Sensoren, fuer die der eingeloggte User `canView` besitzt.
- Schreib-Endpunkte wie `POST /api/updateData.php` verlangen `canEdit`.
- Board-/Sensor-Freigaben werden in `settings.php` im Tab `Freigaben` verwaltet.
- Benachrichtigungen gehen an Owner und an freigegebene User/Observer mit `canReceiveAlerts = 1`, sofern deren persoenliche Benachrichtigungsschalter aktiv sind.


## TTN Integration

### Endpoint

- Methode: `POST`
- Pfad: `/webhooks/ttn.php`
- Content-Type: `application/json`

### Zweck

Dieser Endpunkt nimmt Uplink-Nachrichten von The Things Network entgegen, extrahiert Werte aus `uplink_message.decoded_payload`, legt bei Bedarf Board- und Sensor-Konfigurationen automatisch an und leitet die normalisierte Nutzlast intern an `/ingest/receivejson.php` weiter.

### Erwartete TTN-URL

Empfohlene Webhook-URL:

```text
https://<deine-domain>/webhooks/ttn.php
```

Beispiel:

```text
https://mds-git.derguntmar.de/webhooks/ttn.php
```

### Optionaler Secret-Header

TTN kann zusaetzliche Header an den Webhook senden. MDS unterstuetzt optional ein Shared Secret ueber die Konfiguration:

- `ttnWebhookSecret` in `config/config.json`

Wenn gesetzt, erwartet MDS einen dieser Header:

- `X-MDS-Webhook-Secret`
- `X-Webhook-Secret`
- `X-TTN-Webhook-Secret`

Empfehlung:

- in TTN einen festen Header `X-MDS-Webhook-Secret: <dein-secret>` konfigurieren
- denselben Wert in `config/config.json` unter `ttnWebhookSecret` hinterlegen

### Erwartete TTN-Felder

Der Code verarbeitet insbesondere:

- `end_device_ids.application_ids.application_id`
- `end_device_ids.device_id`
- `end_device_ids.dev_eui`
- `received_at`
- `uplink_message.frm_payload`
- `uplink_message.rx_metadata[0].gateway_ids.gateway_id`
- `uplink_message.rx_metadata[0].rssi`
- `uplink_message.rx_metadata[0].snr`
- `uplink_message.decoded_payload.*`

Aus `decoded_payload` werden aktuell u. a. diese Felder gelesen:

- `macAddress` als primaere Board-ID, wenn der TTN-Decoder sie aus dem ESP-Payload liefert
- `alarm1`
- `altitude`
- `counter`
- `dewpoint`
- `humidity`
- `latitude`
- `level1`
- `level2`
- `longitude`
- `position.context.lat`
- `position.context.lng`
- `pressure`
- `relay`
- `tempbattery`
- `temperature`
- `voltage`
- `voltage2`
- alternativ auch:
  - `Hum_SHT`
  - `TempC_SHT`
  - `BatV`

### Interner Ablauf

1. TTN sendet einen Uplink an `/webhooks/ttn.php`
2. MDS extrahiert Board-Identifier:
   - bevorzugt `decoded_payload.macAddress`
   - sonst als Legacy-Fallback `device_id`
   - sonst als Legacy-Fallback `dev_eui`
3. MDS sucht das Board ueber:
   - primaer `boardConfig.macAddress`
   - fallback `ttnAppId` plus `ttnDevId`
4. Falls das Board nicht existiert:
   - wird ein Board automatisch angelegt
   - wenn `macAddress` vorhanden ist, wird diese direkt als Board-MAC gespeichert
   - alte automatisch angelegte TTN-Boards mit `fakeMacAddress...` werden beim naechsten passenden Uplink auf die echte MAC migriert
5. Falls notwendige Sensoren fehlen:
   - werden `GPS`, `Lora`, `ADC`, `DS18B20`, `BME280`, `DS2438`, `Digital` automatisch angelegt
6. MDS baut eine interne JSON-Payload
7. MDS sendet diese intern an `/ingest/receivejson.php`
8. Dort werden die Werte in `sensorData` gespeichert

### Interne Forward-Payload

`/webhooks/ttn.php` sendet an `/ingest/receivejson.php` ein JSON in diesem Format:

```json
{
  "board": {
    "apiKey": "<configured-api-key>",
    "macAddress": "<board-mac>",
    "protocolVersion": "1",
    "firmwareVersion": "1.2.3",
    "standbyState": "wakeup"
  },
  "sensors": [
    {
      "sensorId": 12,
      "value1": 23.5,
      "value2": 56.1,
      "value3": 1012,
      "value4": 14.2,
      "date": "22.04.2026",
      "time": "09:30:15",
      "transmissionPath": "2"
    }
  ]
}
```

Hinweis:

- die interne TTN-Bridge sendet weiterhin `sensorId`
- fuer externe Geraete ist `sensorId` inzwischen optional
- wenn `decoded_payload` einen Zustandswert wie `standbyState`, `standby_state`, `powerState`, `deviceState` oder `sleepState` enthaelt, wird dieser in `board.standbyState` an `/ingest/receivejson.php` weitergereicht

### Wichtige Hinweise

- `transmissionPath = 2` steht fuer LoRa/TTN
- der Endpunkt erwartet ein valides TLS-Zertifikat bei HTTPS
- TTN lehnt den Webhook ab, wenn das Zertifikat ungueltig ist
- wenn `decoded_payload` fehlt, versucht MDS jetzt einen Fallback ueber `normalized_payload`


## JSON Ingest

### Endpoint

- Methode: `POST`
- Pfad: `/ingest/receivejson.php`
- Content-Type: `application/json`

### Zweck

Generischer JSON-Ingest fuer MDS-Sensordaten. Dieser Endpunkt wird typischerweise intern von `/webhooks/ttn.php` genutzt, kann aber auch von anderen Geraeten oder Bridges verwendet werden, solange das Datenformat passt.

### Request-Struktur

Pflichtstruktur:

```json
{
  "board": {
    "apiKey": "string",
    "macAddress": "string",
    "protocolVersion": "1",
    "firmwareVersion": "string",
    "standbyState": "wakeup"
  },
  "sensors": [
    {
      "sensorId": 12,
      "value1": 12.3,
      "value2": 45.6,
      "value3": 0,
      "value4": 0,
      "date": "22.04.2026",
      "time": "09:30:15",
      "transmissionPath": "1"
    }
  ]
}
```

Optionale Board-Felder:

- `firmwareVersion`, alternativ `firmware_version`, `fwVersion` oder `firmware`
  - wird in `boardConfig.firmwareVersion` gespeichert
  - wird im Dashboard pro Device angezeigt
  - maximale gespeicherte Laenge: 64 Zeichen
- `standbyState`, alternativ `standby_state`, `powerState`, `deviceState` oder `sleepState`
  - Zustandsfeld fuer den aktuellen Device-Zustand, abgeleitet vom Operation Mode Input des ESP
  - beim LoRa Boat Monitor bedeutet 12 V am Operation Mode Input `always_online`; ohne 12 V ist der Standby-/Wakeup-Modus aktiv
  - erlaubte Werte:
    - `always_online`
    - `wakeup`
    - `standby`
  - zusaetzlich akzeptiert MDS tolerante Schreibweisen wie `always online`, `always-online`, `wake`, `awake`, `sleep`
  - numerische oder boolesche Zustandswerte werden ebenfalls interpretiert:
    - `1`, `true`, `on`, `online` => `wakeup`
    - `0`, `false`, `off` => `standby`
  - wenn `always_online` uebertragen wird, erzeugt MDS bei Bedarf ein persistentes ESP-Ereignis `Always online`
  - `wakeup` und `standby` werden als aktueller Zustands-Hinweis akzeptiert, steuern aber nicht direkt die Dauerberechnung im Verlauf
  - Wakeup-/Standby-Dauern werden primaer aus Telemetrie-Aktivitaet und Payload-Luecken abgeleitet, damit am Ende eines Sendezyklus gemeldete Schlafhinweise die Zeiten nicht vertauschen
  - wenn kein `standbyState` uebertragen wird, versucht MDS weiterhin Wakeup-/Standby-Ereignisse vollstaendig aus Telemetrie-Luecken abzuleiten
  - dabei gilt: nach Ablauf von `offlineDataTimer` ohne neue Nutzdaten wird ein `Standby` angenommen; beim naechsten Datenempfang wird ein `Wakeup` erzeugt

### Sensor-Mapping

`sensorId` ist fuer `/ingest/receivejson.php` nicht mehr zwingend erforderlich.

Unterstuetzte Zuordnungen:

1. direkt ueber `sensorId`
2. ueber `sensorAddress`
3. automatisch ueber die Sensor-Konfiguration des Boards anhand von:
   - `board.macAddress`
   - optionalen Hinweisen wie `sensorType`, `type`, `sensorName`, `name`
   - Anzahl der uebergebenen Werte `value1..value4`
   - bevorzugt ueber die Kombination `sensorType + sensorName`
   - danach ueber eindeutige Einzelhinweise
4. wenn noch kein passender `sensorConfig`-Eintrag existiert:
   - kann MDS ihn automatisch anlegen
   - Voraussetzung ist ein eindeutiger Typ-Hinweis ueber `sensorType`, `type`, `sensorName` oder `name`, der einem bekannten `sensorTypes.name` entspricht
   - ohne solchen Typ-Hinweis legt MDS nichts blind an, sondern loggt eine Warnung und verwirft den Sensor

Empfohlene Payload fuer externe Geraete ohne bekannte `sensorId`:

```json
{
  "board": {
    "apiKey": "my_api_key",
    "macAddress": "24:6F:28:7B:A9:14",
    "protocolVersion": "1",
    "firmwareVersion": "1.2.3",
    "standbyState": "always_online"
  },
  "sensors": [
    {
      "sensorType": "BME280",
      "value1": 21.5,
      "value2": 61.2,
      "value3": 1013.8,
      "value4": 13.8,
      "date": "22.04.2026",
      "time": "11:40:00",
      "transmissionPath": "1"
    },
    {
      "sensorType": "ADC",
      "value1": 12.7,
      "value2": 0.0,
      "value3": 0.0,
      "value4": 0.0,
      "date": "22.04.2026",
      "time": "11:40:00",
      "transmissionPath": "1"
    }
  ]
}
```

Empfehlung fuer externe Devices:

- `sensorType` immer mitsenden, wenn `sensorId` nicht bekannt ist
- `sensorName` ebenfalls mitsenden, wenn mehrere Sensoren desselben Typs auf einem Board existieren koennen, z. B. `ADC/Battery` und `ADC/Tanks`
- gute Werte sind z. B. `BME280`, `ADC`, `GPS`, `DS18B20`, `Digital`, `DS2438`, `Lora`
- dann kann MDS fehlende `sensorConfig`-Eintraege bei Bedarf automatisch anlegen
- wenn am Operation Mode Input 12 V anliegen und das Device absichtlich dauerhaft online bleibt, `board.standbyState = "always_online"` mitsenden
- wenn das Device gerade aufgeweckt wurde, kann `board.standbyState = "wakeup"` als Zustands-Hinweis mitgesendet werden
- wenn das Device gerade in den Schlafzustand gegangen ist oder sich dort befindet, kann `board.standbyState = "standby"` als Zustands-Hinweis mitgesendet werden
- fuer die Verlaufsdauer ist jedoch die Payload-Aktivitaet die fuehrende Quelle; `always_online` bleibt der einzige Zustand, der den Verlauf direkt fest auf online setzt
- dann zeigt MDS unter `ESP-Ereignisse` den aktuellen Zustand und den Verlauf ohne zusaetzliche Speziallogik aus einer Konfiguration an

Verhalten bei neuen oder unvollstaendig provisionierten Boards:

- das Board selbst kann ueber `board.macAddress` automatisch angelegt werden
- ein fehlender `sensorConfig`-Eintrag wird nur dann automatisch erzeugt, wenn der Sensortyp eindeutig erkennbar ist
- nur `macAddress` plus rohe Werte ohne Typ-/Namenshinweis reicht fuer ein sicheres Auto-Provisioning nicht aus


## Benachrichtigungen

### Offline-Benachrichtigungen

Offline-Mails werden ueber diesen Wartungspfad verarbeitet:

```text
php tools/maintenance/sendmail.php
```

Voraussetzungen:

- `sendEmails = 1` in `config/config.json`
- `systemEmailAddress` oder `adminEmailAddress` gesetzt
- der Board-Besitzer hat `receive_notifications = 1`
- der Board-Besitzer hat `receive_offline_notifications = 1`
- das Board hat:
  - `alarmOnUnavailable = 1`
  - `offlineDataTimer > 0`

Ablauf:

1. Das Script prueft Boards mit Besitzer und aktivierter Benachrichtigung.
2. Wenn ein Board laenger offline ist als `offlineDataTimer`, wird eine Mail an den Besitzer verschickt.
3. `boardConfig.alreadyNotified` wird auf `1` gesetzt.
4. Sobald wieder Daten ueber `/ingest/receivejson.php` ankommen, setzt MDS den Zustand wieder zurueck.

### Kritische Sensorwerte

Sensor-Kanaele koennen ebenfalls automatische E-Mail-Warnungen ausloesen.

Die Konfiguration erfolgt pro Kanal in `formSensors.php`:

- `AlertEnabled`
- `AlertLowValue`
- `AlertHighValue`

Verhalten:

- unterschreitet der aktuelle Kanalwert `AlertLowValue`, wird ein `low`-Alert ausgeloest
- ueberschreitet der aktuelle Kanalwert `AlertHighValue`, wird ein `high`-Alert ausgeloest
- wiederholte Mails fuer denselben Zustand werden unterdrueckt, solange `AlertState` gleich bleibt
- wenn der Wert wieder in den Normalbereich faellt, wird `AlertState` zurueckgesetzt
- der Benutzer muss sowohl `receive_notifications = 1` als auch `receive_sensor_notifications = 1` aktiviert haben

Die neuen DB-Felder dazu liegen in `sensorChannelConfig`:

- `GaugeStyle`
- `AlertEnabled`
- `AlertLowValue`
- `AlertHighValue`
- `AlertState`
- `LastAlertSentAt`

Migration:

- [docs/db_design/migrations/2026-04-26_notification_and_gauge_style.sql](/Users/guntmar/Documents/Docker/MDS/mds_from_workdir/public_html/maritimedataserver/docs/db_design/migrations/2026-04-26_notification_and_gauge_style.sql:1)


## Dashboard-Gauges

### Gauge-Stile

Pro Sensor-Kanal kann in `formSensors.php` ein Gauge-Stil gewaehlt werden.

Aktuell unterstuetzte Stile:

- `classic`
- `minimal`
- `bold`
- `arc`
- `ring`
- `clock`
- `industrial`

Die Auswahl wird in `sensorChannelConfig.GaugeStyle` gespeichert und beim Dashboard-Rendering in `public/internal.php` und `public/assets/js/dashboard.js` ausgewertet.

### Kritische Bereiche vs. Benachrichtigungen

Die Gauge-Farbbereiche und die Mail-Schwellwerte sind bewusst getrennt:

- Gauge-Bereiche:
  - `GaugeRedAreaLowValue`
  - `GaugeRedAreaHighValue`
- Mail-Schwellwerte:
  - `AlertLowValue`
  - `AlertHighValue`

Damit kann ein Wert optisch frueh auffaellig markiert werden, ohne sofort eine E-Mail auszuloesen.

### Validierung

- `board.apiKey` muss dem konfigurierten API-Key entsprechen
- `board.protocolVersion` muss aktuell `"1"` sein
- `board.macAddress` wird auf `boardConfig` aufgeloest
- falls das Board noch nicht existiert, wird es automatisch angelegt
- falls `sensorId` fehlt, wird der Sensor automatisch aus `sensorConfig` des Boards aufgeloest

### Speicherung

Die Werte werden in `sensorData` gespeichert:

- `sensorId`
- `value1`
- `value2`
- `value3`
- `value4`
- `val_date`
- `val_time`
- `transmissionPath`

### transmissionPath

Aktuell verwendet:

- `1` = direkt / WiFi / generischer Device-Import
- `2` = LoRa / TTN


## Browser APIs

Diese Endpunkte werden durch das eingeloggte Frontend verwendet.

### `POST /api/checkSession.php`

Prueft, ob die aktuelle PHP-Session eingeloggt ist.

Response:

```json
true
```

oder

```json
false
```


### `POST /api/getdata.php`

Liefert die aktuellen Werte eines Sensors fuer die Gauge-Anzeige.

Authentifizierung:

- bevorzugt ueber `identifier` + `securityToken`
- Fallback auf normale eingeloggte PHP-Session

POST-Parameter:

- `data`
  - aktuell nur `"sensor"` unterstuetzt
- `sensorId`
  - numerische Sensor-ID
- `NrOfValues`
  - Anzahl Werte, typischerweise `1`
- optional:
  - `identifier`
  - `securityToken`

Beispiel:

```x-www-form-urlencoded
data=sensor&sensorId=17&NrOfValues=1
```

Beispiel-Response:

```json
[17, 12.3, 55.1, 1013, 7.2]
```

Bedeutung:

- Index `0` = `sensorId`
- danach `value1..value4` je nach Sensor-Konfiguration

Fehlerantworten:

- `400` bei fehlenden/ungueltigen Parametern
- `401` wenn keine Authentifizierung vorhanden ist
- `403` wenn kein Zugriff auf den Sensor erlaubt ist
- `404` wenn der Sensor nicht existiert


### `GET /api/getSensorDataSet.php`

Liefert historische Sensordaten fuer Chart.js.

Authentifizierung:

- nur ueber eingeloggte PHP-Session

Query-Parameter:

- `sensorId`
- `maxValues`

Beispiel:

```text
/api/getSensorDataSet.php?sensorId=17&maxValues=200
```

Response:

```json
[
  {
    "id": 101,
    "sensorId": 17,
    "value1": "12.3",
    "value2": "55.1",
    "value3": "1013",
    "value4": "7.2",
    "val_date": "22.04.2026",
    "val_time": "09:30:15",
    "reading_time": "2026-04-22 09:30:15"
  }
]
```


### `POST /api/getBoardName.php`

Liefert Board-Namen fuer die Kartenansicht des eingeloggten Nutzers.

POST-Parameter:

- `functionName=get`

Response-Beispiel:

```json
{
  "1": "Simulator",
  "2": "Boat 1"
}
```


### `POST /api/getGpsData.php`

Liefert GPS-Daten pro Board fuer die Kartenansicht des eingeloggten Nutzers.

POST-Parameter:

- `functionName=get`

Response:

- JSON-Objekt mit Board-ID als Key
- Werte kommen aus den gespeicherten GPS-Messdaten


### `POST /api/updateData.php`

Aktuell verwendet fuer die Speicherung von Sensor-Reihenfolgen im Dashboard.

Authentifizierung:

- eingeloggte PHP-Session

Aktuelle POST-Operation:

- `update=sensorOrderNumber`

Weitere Parameter:

- `id`
  - Sensor-ID
- `channel`
  - Kanalnummer
- `orderNumber`
  - neue Reihenfolge

Response:

```json
"done"
```


## OTA Firmware Update

### Endpoint

- Methode: `GET`
- Pfad: `/ota/getupdate.php`

### Zweck

ESP32-Firmware-Update per HTTP-Header-basierter Abfrage.

### Erwartete Request-Header

Pflicht-Header:

- `User-Agent: ESP32-http-Update`
- `X-MDS-OTA-Secret`
- `x-ESP32-STA-MAC`
- `x-ESP32-sketch-md5`
- `x-ESP32-sdk-version`
- `x-ESP32-version`

Weitere Header werden geloggt, aber nicht zwingend validiert.

### OTA Secret

Der Endpunkt ist durch ein Shared Secret geschuetzt. Der Server erwartet den
Header `X-MDS-OTA-Secret`; der Wert muss exakt dem Eintrag `otaUpdateSecret`
in `config/config.json` entsprechen.

Admins koennen den Wert in der Weboberflaeche unter
`Settings -> Server Setting -> OTA-Update-Secret` pflegen.

Beispiel-Konfiguration:

```json
{
  "otaUpdateSecret": "change_this_to_a_long_random_secret"
}
```

Wenn `otaUpdateSecret` nicht gesetzt ist oder der Header fehlt bzw. nicht
passt, wird die Anfrage mit `403 Forbidden` abgelehnt. ESP32-Geräte muessen
den Header beim OTA-Update mitsenden.

### Verhalten

- `403`
  - wenn Header, User-Agent oder `X-MDS-OTA-Secret` nicht passen
  - wenn serverseitig kein `otaUpdateSecret` konfiguriert ist
- `404`
  - wenn die MAC keinem Board in `boardConfig` zugeordnet werden kann
  - wenn die Firmware-Datei fehlt
- `200`
  - wenn `boardConfig.performUpdate = 1` ist und die lokale Firmware-Datei einen anderen MD5-Hash als `x-ESP32-sketch-md5` hat
- `304`
  - wenn keine neuere Firmware vorhanden ist
  - wenn das Board bekannt ist, aber `performUpdate` nicht aktiviert ist

### Dateipfade

- Firmware-Binaerdateien: `var/ota/bin/`
- OTA-Logs: `var/ota/logs/`

Aktuell wird als Standarddatei `var/ota/bin/firmware.bin` ausgeliefert.
Die Freigabe erfolgt pro Board ueber `boardConfig.performUpdate`.


## TTN Simulator

### UI

- Pfad: `/tools/simulator/index.php`

### Zweck

Erzeugt TTN-aehnliche Test-Uplinks und sendet sie an eine waehlbare Ziel-URL.

Standardziel:

- `<baseurl>/webhooks/ttn.php`

### Sender-Endpunkt

Die UI sendet an:

- `/tools/simulator/testttn.php`

Dieser Endpunkt:

- validiert die Ziel-URL
- baut ein TTN-aehnliches JSON
- sendet es per cURL an die Ziel-URL
- loggt Transportfehler

### Typische Testziele

- lokales MDS:
  - `https://<host>/webhooks/ttn.php`
- Produktionssystem:
  - `https://mds-git.derguntmar.de/webhooks/ttn.php`


## Authentifizierung

Im Projekt gibt es aktuell zwei relevante Auth-Mechanismen fuer Browser-Endpunkte:

### 1. PHP-Session

Wird bei normalen eingeloggten Seiten genutzt:

- Login erzeugt `$_SESSION['userId']`
- Session-basierte APIs:
  - `/api/checkSession.php`
  - `/api/getBoardName.php`
  - `/api/getGpsData.php`
  - `/api/getSensorDataSet.php`
  - `/api/updateData.php`

### 2. Remember-Me-Token

Beim Login mit aktivem Haken "remember login":

- `identifier`
- `securityToken`

werden als Cookies gesetzt und in `securityTokens` gespeichert.

`/api/getdata.php` akzeptiert:

- Token-Authentifizierung
- oder Session-Fallback


## Logging und Diagnose

Normale Projekt-Logs:

- `var/log/log_<Monat>_<Jahr>.log`

Wichtige TTN-Logeintraege:

- `TTN uplink received.`
- `Forwarding TTN payload to receivejson.php.`
- `sensorData row inserted.`
- `receivejson processing finished successfully.`

OTA-spezifische Logs:

- `var/ota/logs/log.csv`
- Anzeige in der Weboberflaeche: `Settings -> Log -> OTA-Update-Log`


## Bekannte Besonderheiten

### TTN-Zertifikat

TTN verlangt ein gueltiges HTTPS-Zertifikat fuer den Webhook. Bei Zertifikatsproblemen erscheint in TTN Live Data z. B.:

- `Fail to send webhook`
- `Request: Certificate invalid`

### Auto-Creation von Boards und Sensoren

TTN-Uplinks koennen neue Boards und Sensoren automatisch anlegen. Dadurch entstehen schnell Standardkanaele, auch wenn ein Board nur einen Teil davon wirklich befuellt.

### Dashboard-Werte

Das Dashboard rendert nur numerische Kanaele sinnvoll. Fuer den aktuellen Stand wurden bereits Fixes eingebaut, damit keine leeren Standardkanaele mehr als `NaN` erscheinen.


## Empfehlung fuer den Betrieb

- TTN immer auf `/webhooks/ttn.php` zeigen lassen
- externe Systeme nicht direkt auf interne Legacy-Pfade konfigurieren
- `var/log` und `var/ota/logs` beschreibbar halten
- bei Produktionsfehlern immer zuerst die Projekt-Logs und danach die Webserver-/Passenger-Logs pruefen
