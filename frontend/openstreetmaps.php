<div class="container">
</div>
<!--link rel="stylesheet" href="https://unpkg.com/leaflet@1.8.0/dist/leaflet.css" integrity="sha512-hoalWLoI8r4UszCkZ5kL8vayOGVae1oxXe/2A4AO6J9+580uKHDO3JdHb7NzwwzK5xr/Fs0W40kiNHxM9vyTtQ==" crossorigin=""/-->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.8.0/dist/leaflet.css" crossorigin=""/>

<!-- Make sure you put this AFTER Leaflet's CSS -->
<!--script src="https://unpkg.com/leaflet@1.8.0/dist/leaflet.js" integrity="sha512-BB3hKbKWOc9Ez/TAwyWxNXeoV9c1v6FIeYiBieIWkpLjauysF18NzgR1MBNBXf8/KABdlkX68nAhlwcDFLGPCQ==" crossorigin=""></script-->
<script src="https://unpkg.com/leaflet@1.8.0/dist/leaflet.js" crossorigin=""></script>
<div class="container">
</div>

<div id="map"></div>

<!--style>
  #map { height: 400px; }
</style-->

<script>
var map = null;
var myboardidMarkerGroup = null;

//var myIcons = null;

$('#hrefmap').click( function (e) {
  $( document ).ready( function() {
    if (map != null) {
      map.off();
      map.remove();
      map = null;
    }
    var layerControl = false;

    map = L.map('map').setView([52.996477, 8.790839], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19, // was 19
      attribution: '© OpenStreetMap'
    }).addTo(map);

    //myboardidMarkerGroup[4] = new L.LayerGroup().addTo(map);
    myboardidMarkerGroup = new Array ();
          
    var greenIcon = new L.Icon({
      iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-green.png',
      shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
      iconSize: [25, 41],
      iconAnchor: [12, 41],
      popupAnchor: [1, -34],
      shadowSize: [41, 41]
    });

    var blueIcon = new L.Icon({
      iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-blue.png',
      shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
      iconSize: [25, 41],
      iconAnchor: [12, 41],
      popupAnchor: [1, -34],
      shadowSize: [41, 41]
    });

    var orangeIcon = new L.Icon({
      iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-orange.png',
      shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
      iconSize: [25, 41],
      iconAnchor: [12, 41],
      popupAnchor: [1, -34],
      shadowSize: [41, 41]
    });

    var redIcon = new L.Icon({
      iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
      shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
      iconSize: [25, 41],
      iconAnchor: [12, 41],
      popupAnchor: [1, -34],
      shadowSize: [41, 41]
    });

    var myIcons = new Array (greenIcon, blueIcon, orangeIcon, redIcon)

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
            //console.log(key, obj[key]);
            myboardidMarkerGroup[key] = new L.LayerGroup().addTo(map);
            yourVariable = obj[key];
            yourVariable.forEach(
              function(element) { 
                // TODO extend to loop for boards of the user, and adapt to the color for each group / board
                myboardidMarkerGroup[key].addLayer(L.marker([element["value1"], element["value2"]], {icon: myIcons[iconcounter]}).bindPopup("<b>Hello world!</b><br>I am a new popup.")).addTo(map);
              }
            );

            if(layerControl === false) {  // var layerControl set to false in init phase; 
              layerControl = L.control.layers().addTo(map);
            }
            //layerControl.addOverlay(myboardidMarkerGroup , "Board id: " + myboardid);
            layerControl.addOverlay(myboardidMarkerGroup[key] , "Board id: " + key);
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


   