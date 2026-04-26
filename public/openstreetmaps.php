<?php
$mapBoardNames = isset($mapBoardNames) && is_array($mapBoardNames) ? $mapBoardNames : array();
$mapGpsData = isset($mapGpsData) && is_array($mapGpsData) ? $mapGpsData : array();
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.2/leaflet.css" crossorigin=""/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.2/leaflet.js" crossorigin=""></script>
<div id="map" style="height: 50vh; min-height: 420px; width: 100%;"></div>
<div id="mapFallback" class="alert alert-warning mt-3" style="display:none;"></div>

<script>
var map = null;
var mapMarkersLayer = null;
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

  try {
    if (map == null) {
      map = L.map('map', {
        preferCanvas: true
      });

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap'
      }).addTo(map);
    }

    if (mapMarkersLayer != null) {
      mapMarkersLayer.clearLayers();
    } else {
      mapMarkersLayer = L.layerGroup().addTo(map);
    }

    const markerColors = [
      '#16a34a',
      '#2563eb',
      '#f59e0b',
      '#dc2626'
    ];

    const bounds = [];
    let iconCounter = 0;

    boardIds.forEach(function(boardId) {
      const points = internalMapData.gpsData[boardId] || [];
      const boardName = internalMapData.boardNames[boardId] || ('Board ' + boardId);
      const markerColor = markerColors[iconCounter % markerColors.length];

      points.forEach(function(point) {
        const lat = parseFloat(point.value1);
        const lng = parseFloat(point.value2);

        if (Number.isNaN(lat) || Number.isNaN(lng)) {
          return;
        }

        bounds.push([lat, lng]);
        mapMarkersLayer.addLayer(
          L.circleMarker([lat, lng], {
            radius: 7,
            weight: 2,
            color: '#ffffff',
            fillColor: markerColor,
            fillOpacity: 0.95
          }).bindPopup('<b>' + boardName + '</b><br>Timestamp: ' + point.reading_time)
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
