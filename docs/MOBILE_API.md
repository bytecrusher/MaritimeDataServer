# Mobile API v1

Stand: 2026-06-25

Die Mobile API stellt JSON-Endpunkte fuer native Clients wie die iOS-App bereit.
Sie nutzt die bestehende MDS-Session-Authentifizierung. Clients muessen die Cookies
aus `POST /api/v1/login.php` speichern und bei weiteren Requests wieder mitsenden.

Basis-URL:

```text
https://<deine-mds-domain>/api/v1
```

## Authentifizierung

### `POST /api/v1/login.php`

Meldet einen bestehenden MDS-Benutzer an und startet eine PHP-Session.

Request als `application/json` oder `application/x-www-form-urlencoded`:

```json
{
  "email": "user@example.com",
  "password": "secret",
  "remember": true
}
```

Erfolg:

```json
{
  "ok": true,
  "user": {
    "id": 1,
    "email": "user@example.com",
    "firstName": "Ada",
    "lastName": "Lovelace",
    "timezone": "Europe/Berlin",
    "isAdmin": false
  }
}
```

Fehler:

- `400 missing_credentials`
- `401 invalid_credentials`
- `405 method_not_allowed`
- `503 not_installed`

### `GET /api/v1/session.php`

Prueft, ob die aktuelle Cookie-Session angemeldet ist.

```json
{
  "ok": true,
  "authenticated": true,
  "user": {
    "id": 1,
    "email": "user@example.com",
    "firstName": "Ada",
    "lastName": "Lovelace",
    "timezone": "Europe/Berlin",
    "isAdmin": false
  }
}
```

Nicht angemeldet:

```json
{
  "ok": true,
  "authenticated": false
}
```

## Boards, Sensoren und Kanaele

### `GET /api/v1/boards.php`

Liefert alle sichtbaren Dashboard-Boards des angemeldeten Benutzers inklusive Sensoren,
Channels, Dashboard-Metadaten, Online-Status und letztem Messwert je Channel.

Die Daten sind serverseitig auf die effektiven Rechte des aktuellen Users
eingeschraenkt:

- Admins sehen alle Boards.
- Owner sehen ihre Boards.
- User/Observer sehen Boards oder einzelne Sensoren, fuer die `canView` gesetzt ist.
- Ohne `scope=owned` werden zusaetzlich nur Dashboard-aktive Boards, Sensoren und Channels geliefert.

Optional:

- `GET /api/v1/boards.php?scope=owned`
  - liefert alle fuer den angemeldeten Benutzer sichtbaren Boards
  - `onDashboard` bleibt als Feld erhalten
  - Sensoren und Channels werden fuer sichtbare Sensoren vollstaendig geliefert
  - gedacht fuer native Apps, die Boards lokal ein- und ausblenden

Gekuerztes Beispiel:

```json
{
  "ok": true,
  "fetchedAt": "2026-06-25T13:00:00+00:00",
  "user": {
    "id": 1,
    "email": "user@example.com"
  },
  "boards": [
    {
      "id": 4,
      "macAddress": "24:6F:28:AA:BB:CC",
      "name": "Boat",
      "location": "Marina",
      "description": "Main board",
      "firmwareVersion": "1.0",
      "boardTypeId": 1,
      "boardTypeName": "ESP32",
      "onDashboard": true,
      "alarmOnUnavailable": false,
      "updateDataTimer": 10,
      "offlineDataTimer": 15,
      "isOnline": true,
      "latestReadingTime": "2026-06-25 14:55:00",
      "latestTransmissionPath": 1,
      "sensors": [
        {
          "id": 12,
          "name": "Cabin",
          "description": "Temperature sensor",
          "sensorAddress": "28ff...",
          "typeId": 2,
          "typeName": "DS18B20",
          "typeDescription": "Temperature",
          "locationOfMeasurement": "Cabin",
          "onDashboard": true,
          "nrOfUsedSensors": 1,
          "channels": [
            {
              "id": 21,
              "channelNr": 1,
              "name": "Temperature",
              "unit": "C",
              "onDashboard": true,
              "dashboardOrderNr": 1,
              "chartColor": "#20c997",
              "latestValue": 21.4,
              "latestReadingTime": "2026-06-25 14:55:00"
            }
          ]
        }
      ]
    }
  ]
}
```

Fehler:

- `401 not_authenticated`
- `405 method_not_allowed`

## Sensor-Historie

### `GET /api/v1/sensor-data.php?sensorId=12&limit=100`

Liefert historische Messwerte fuer einen Sensor, fuer den der angemeldete Benutzer `canView` besitzt.
`limit` wird auf `1...1000` begrenzt.

```json
{
  "ok": true,
  "sensorId": 12,
  "limit": 100,
  "values": [
    {
      "id": 999,
      "sensorId": 12,
      "value1": 21.4,
      "value2": null,
      "value3": null,
      "value4": null,
      "valDate": "2026-06-25",
      "valTime": "14:55:00",
      "readingTime": "2026-06-25 14:55:00",
      "transmissionPath": 1
    }
  ]
}
```

Fehler:

- `400 missing_sensor_id`
- `401 not_authenticated`
- `404 not_found`
- `405 method_not_allowed`

## Empfohlener iOS-Ablauf

1. `POST /api/v1/login.php`
2. Cookies speichern und wiederverwenden.
3. `GET /api/v1/boards.php` fuer Dashboard, Boards, Sensoren, Channels und aktuelle Werte.
4. Periodisch `GET /api/v1/boards.php` refreshen.
5. Fuer Detail-Charts `GET /api/v1/sensor-data.php?sensorId=...&limit=...` nutzen.

## Sicherheit

- Alle geschuetzten Endpunkte brauchen eine aktive MDS-Session.
- Board- und Sensorabfragen sind auf den angemeldeten Benutzer und Dashboard-
  aktivierte Eintraege eingeschraenkt.
- Fuer native Apps sollte die Installation per HTTPS erreichbar sein.
