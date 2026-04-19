<?php
$mapBoardNames = array();
$mapGpsData = array();

if (isset($currentUser) && $currentUser) {
    $mapBoards = myFunctions::getMyBoards($currentUser->getId());
    foreach ($mapBoards as $mapBoard) {
        $gpsData = myFunctions::getAllGpsData($mapBoard['id']);
        if (!empty($gpsData) && $gpsData !== 0) {
            $boardId = (string) $mapBoard['id'];
            $mapBoardNames[$boardId] = $mapBoard['name'];
            $mapGpsData[$boardId] = $gpsData;
        }
    }
}
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.2/leaflet.css" crossorigin=""/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.2/leaflet.js" crossorigin=""></script>
<div id="map" style="height: 50vh; min-height: 420px; width: 100%;"></div>
<div id="mapFallback" class="alert alert-warning mt-3" style="display:none;"></div>

<script>
var map = null;
var internalMapData = {
  boardNames: <?php echo json_encode($mapBoardNames, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>,
  gpsData: <?php echo json_encode($mapGpsData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>
};

function renderMapFallback(message) {
  const fallback = document.getElementById('mapFallback');
  if (!fallback) {
    return;
  }

  fallback.style.display = 'block';
  fallback.textContent = message;
}

function hideMapFallback() {
  const fallback = document.getElementById('mapFallback');
  if (!fallback) {
    return;
  }

  fallback.style.display = 'none';
  fallback.textContent = '';
}

function initInternalMap() {
  const mapElement = document.getElementById('map');
  if (!mapElement) {
    return;
  }

  const boardIds = Object.keys(internalMapData.gpsData || {});
  if (boardIds.length === 0) {
    renderMapFallback('Keine GPS-Daten fuer die Karte vorhanden.');
    return;
  }

  if (typeof L === 'undefined') {
    renderMapFallback('Leaflet konnte nicht geladen werden. Bitte Seite neu laden.');
    return;
  }

  hideMapFallback();

  if (map != null) {
    map.off();
    map.remove();
    map = null;
  }

  try {
    map = L.map('map');

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '© OpenStreetMap'
    }).addTo(map);

    const icons = [
      new L.Icon({
        iconUrl: 'https://raw.githubusercontent.com/sheiun/leaflet-color-number-markers/main/dist/img/marker-icon-green.png',
        shadowUrl: 'https://raw.githubusercontent.com/sheiun/leaflet-color-number-markers/main/dist/img/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
      }),
      new L.Icon({
        iconUrl: 'https://raw.githubusercontent.com/sheiun/leaflet-color-number-markers/main/dist/img/marker-icon-blue.png',
        shadowUrl: 'https://raw.githubusercontent.com/sheiun/leaflet-color-number-markers/main/dist/img/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
      }),
      new L.Icon({
        iconUrl: 'https://raw.githubusercontent.com/sheiun/leaflet-color-number-markers/main/dist/img/marker-icon-orange.png',
        shadowUrl: 'https://raw.githubusercontent.com/sheiun/leaflet-color-number-markers/main/dist/img/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
      }),
      new L.Icon({
        iconUrl: 'https://raw.githubusercontent.com/sheiun/leaflet-color-number-markers/main/dist/img/marker-icon-red.png',
        shadowUrl: 'https://raw.githubusercontent.com/sheiun/leaflet-color-number-markers/main/dist/img/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
      })
    ];

    const bounds = [];
    let iconCounter = 0;

    boardIds.forEach(function(boardId) {
      const points = internalMapData.gpsData[boardId] || [];
      const boardName = internalMapData.boardNames[boardId] || ('Board ' + boardId);
      const layerGroup = new L.LayerGroup().addTo(map);

      points.forEach(function(point) {
        const lat = parseFloat(point.value1);
        const lng = parseFloat(point.value2);

        if (Number.isNaN(lat) || Number.isNaN(lng)) {
          return;
        }

        bounds.push([lat, lng]);
        layerGroup.addLayer(
          L.marker([lat, lng], {icon: icons[iconCounter % icons.length]})
            .bindPopup('<b>' + boardName + '</b><br>Timestamp: ' + point.reading_time)
        );
      });

      iconCounter++;
    });

    if (bounds.length > 0) {
      map.fitBounds(bounds, {padding: [20, 20]});
    } else {
      map.setView([53.017585, 8.885182], 13);
      renderMapFallback('GPS-Daten sind vorhanden, konnten aber nicht gezeichnet werden.');
    }

    window.setTimeout(function() {
      map.invalidateSize();
    }, 250);
  } catch (error) {
    console.error(error);
    renderMapFallback('Fehler beim Aufbau der Karte: ' + error.message);
  }
}

$(document).ready(function() {
  if (window.location.hash === '#mapContainer') {
    window.setTimeout(function() {
      initInternalMap();
    }, 50);
  }
});
</script>
