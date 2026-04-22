# API And External Interfaces

Stand: 2026-04-22

Diese Datei dokumentiert die aktuell aktiven HTTP-Schnittstellen des Maritime Data Server (MDS) mit Fokus auf:

- externe Anbindungen wie The Things Network (TTN)
- Browser-APIs des Dashboards
- OTA-Firmware-Update fuer ESP32
- Simulator fuer Test-Uplinks

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
   - bevorzugt `device_id`
   - sonst `dev_eui`
3. MDS sucht das Board ueber:
   - `ttnAppId`
   - `ttnDevId`
4. Falls das Board nicht existiert:
   - wird ein Board automatisch angelegt
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
    "macAddress": "<board-mac-or-fake-mac>",
    "protocolVersion": "1"
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
    "protocolVersion": "1"
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

### Sensor-Mapping

`sensorId` ist fuer `/ingest/receivejson.php` nicht mehr zwingend erforderlich.

Unterstuetzte Zuordnungen:

1. direkt ueber `sensorId`
2. ueber `sensorAddress`
3. automatisch ueber die Sensor-Konfiguration des Boards anhand von:
   - `board.macAddress`
   - optionalen Hinweisen wie `sensorType`, `type`, `sensorName`, `name`
   - Anzahl der uebergebenen Werte `value1..value4`
   - notfalls der Reihenfolge in `sensorConfig`
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
    "protocolVersion": "1"
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
- gute Werte sind z. B. `BME280`, `ADC`, `GPS`, `DS18B20`, `Digital`, `DS2438`, `Lora`
- dann kann MDS fehlende `sensorConfig`-Eintraege bei Bedarf automatisch anlegen

Verhalten bei neuen oder unvollstaendig provisionierten Boards:

- das Board selbst kann ueber `board.macAddress` automatisch angelegt werden
- ein fehlender `sensorConfig`-Eintrag wird nur dann automatisch erzeugt, wenn der Sensortyp eindeutig erkennbar ist
- nur `macAddress` plus rohe Werte ohne Typ-/Namenshinweis reicht fuer ein sicheres Auto-Provisioning nicht aus

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
- `x-ESP32-STA-MAC`
- `x-ESP32-sketch-md5`
- `x-ESP32-sdk-version`
- `x-ESP32-version`

Weitere Header werden geloggt, aber nicht zwingend validiert.

### Verhalten

- `403`
  - wenn Header oder User-Agent nicht passen
- `500`
  - wenn die MAC nicht fuer Updates konfiguriert ist
- `200`
  - wenn eine neue Firmware geliefert wird
- `304`
  - wenn keine neuere Firmware vorhanden ist

### Dateipfade

- Firmware-Binaerdateien: `var/ota/bin/`
- OTA-Logs: `var/ota/logs/`


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
