<!--link rel="stylesheet" href="../node_modules/leaflet/dist/leaflet.css" crossorigin=""/-->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.2/leaflet.css" crossorigin=""/>

<!--script src="../node_modules/leaflet/dist/leaflet.js" crossorigin=""></script-->
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.2/leaflet.js" crossorigin=""></script>

<div id="map"></div>

<script>
var map = null;
var myboardidMarkerGroup = null;

$('#hrefmap').click( function (e) {
  $( document ).ready( function() {
    if (map != null) {
      map.off();
      map.remove();
      map = null;
    }
    var layerControl = false;

    map = L.map('map').setView([53.017585, 8.885182], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19, // was 19
      attribution: '© OpenStreetMap'
    }).addTo(map);

    //myboardidMarkerGroup[4] = new L.LayerGroup().addTo(map);
    myboardidMarkerGroup = new Array ();
          
    var greenIcon = new L.Icon({
      //iconUrl: '../node_modules/leaflet-color-number-markers/dist/img/marker-icon-green.png',
      iconUrl: 'https://raw.githubusercontent.com/sheiun/leaflet-color-number-markers/main/dist/img/marker-icon-green.png',
      shadowUrl: 'https://raw.githubusercontent.com/sheiun/leaflet-color-number-markers/main/dist/img/marker-shadow.png',
      iconSize: [25, 41],
      iconAnchor: [12, 41],
      popupAnchor: [1, -34],
      shadowSize: [41, 41]
    });

    var blueIcon = new L.Icon({
      //iconUrl: '../node_modules/leaflet-color-number-markers/dist/img/marker-icon-blue.png',
      iconUrl: 'https://raw.githubusercontent.com/sheiun/leaflet-color-number-markers/main/dist/img/marker-icon-blue.png',

      shadowUrl: 'https://raw.githubusercontent.com/sheiun/leaflet-color-number-markers/main/dist/img/marker-shadow.png',
      iconSize: [25, 41],
      iconAnchor: [12, 41],
      popupAnchor: [1, -34],
      shadowSize: [41, 41]
    });

    var orangeIcon = new L.Icon({
      iconUrl: 'https://raw.githubusercontent.com/sheiun/leaflet-color-number-markers/main/dist/img/marker-icon-orange.png',
      shadowUrl: 'https://raw.githubusercontent.com/sheiun/leaflet-color-number-markers/main/dist/img/marker-shadow.png',
      iconSize: [25, 41],
      iconAnchor: [12, 41],
      popupAnchor: [1, -34],
      shadowSize: [41, 41]
    });

    var redIcon = new L.Icon({
      iconUrl: 'https://raw.githubusercontent.com/sheiun/leaflet-color-number-markers/main/dist/img/marker-icon-red.png',
      shadowUrl: 'https://raw.githubusercontent.com/sheiun/leaflet-color-number-markers/main/dist/img/marker-shadow.png',
      iconSize: [25, 41],
      iconAnchor: [12, 41],
      popupAnchor: [1, -34],
      shadowSize: [41, 41]
    });

    var myIcons = new Array (greenIcon, blueIcon, orangeIcon, redIcon)

    let myobjnames = null;

    // function for getting all Boardnames.
    jQuery.ajax({
      type: "POST",
      url: 'api/getBoardname.php',
      dataType: 'json',
      data: {functionname: 'get', userid: <?php echo($currentUser->getId()); ?>},
      async: false,
      success: function (obj, textstatus) {
        if( !('error' in obj) ) {
          var iconcounter = 0;
          myobjnames = obj;
        }
        else {
          console.log(obj.error);
        }
      }
    });

    // function for getting all GPS for a given Board and create marker for each.
    jQuery.ajax({
      type: "POST",
      url: 'api/getGpsData.php',
      dataType: 'json',
      data: {functionname: 'get', userid: <?php echo($currentUser->getId()); ?>},

      success: function (obj, textstatus) {
        if( !('error' in obj) ) {
          var iconcounter = 0;
          Object.keys(obj).forEach(key => {
            myboardidMarkerGroup[key] = new L.LayerGroup().addTo(map);
            yourVariable = obj[key];
            yourVariable.forEach(
              function(element) { 
                // TODO extend to loop for boards of the user, and adapt to the color for each group / board
                myboardidMarkerGroup[key].addLayer(L.marker([element["value1"], element["value2"]], {icon: myIcons[iconcounter]}).bindPopup("<b>" + myobjnames[key] + "</b><br>Timestamp: " + element["reading_time"])).addTo(map);
              }
            );

            if(layerControl === false) {  // var layerControl set to false in init phase; 
              layerControl = L.control.layers().addTo(map);
            }
            layerControl.addOverlay(myboardidMarkerGroup[key] , "Board: " + myobjnames[key]);
            iconcounter++;
          });
        }
        else {
          console.log(obj.error);
        }
      }
    });

    map.whenReady(() => {
      setTimeout(() => {
        map.invalidateSize();
      }, 1000);
    });
  });
});
</script>


   