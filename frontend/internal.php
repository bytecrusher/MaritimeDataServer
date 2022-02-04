<?php
  session_start();
  require_once("func/dbConfig.func.php");
  require_once("func/myFunctions.func.php");
  require_once("func/user.class.php");
  include("common/header.inc.php");

  if (isset($_SESSION['userobj'])) {
    $user = unserialize($_SESSION['userobj']);
  } else {
    $user = false;
  }

  include("func/get_data.php");
  $deviceOnline = checkDeviceIsOnline2();
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
<script src="js/app.js"></script>
<script src="js/gauge.js"></script>


<?php
  // Show Online / Offline
  $maxtimeout = strtotime("-15 Minutes");
  $myboards = myFunctions::getMyBoards($user->getId());
?>

<script>
  $(document).ready(function() {
    updateGauges();
  });
  let gaugesarray = new Array();
  let gaugesArrayHelper = new Array();

  function updateGauges() { 
    var varIdent = getCookie("identifier");
    var varToken = getCookie("securitytoken");
    var varboardId = 14;
    var varsensorId = 7;
    var vardata = "sensor";
    var varNrOfValues = "1";

    let text;
    var obj;

    for (let i in gaugesArrayHelper) {
      varsensorId = gaugesArrayHelper[i];
      $.ajax({
        method: "POST",
        url: "api/getdata.php",
        data: { identifier: varIdent, securitytoken: varToken, data: vardata, sensorId: varsensorId, NrOfValues: varNrOfValues }
      })
      .done(function( response ) {
          $("p.broken").html(response);
          text = response;
          obj = JSON.parse(text);
          gaugesarray[i].setValueAnimated(obj[0]);
      });
    }
  }

setInterval(function() { 
    // run every 30 seconds
    updateGauges();
}, 30000);
</script>

  <div class="jumbotron" style="padding: 1rem 1rem; margin-bottom: 1rem;">
    <div class="container">
      <h1>Welcome <?php echo htmlentities($user->getFirstname()); ?></h1>
    </div>
  </div>
  <div class="container main-container">

  <div class="container pl-0">
    <!-- Nav tabs -->
    <ul class="nav nav-tabs" role="tablist">
      <li class="nav-item">
        <a class="nav-link active" data-toggle="tab" href="#dashboard">Dashboard</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#temperatures" id='hreftemperatures'>Charts</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-toggle="tab" href="#boards" id='hrefboards'>Boards</a>
      </li>
    </ul>

    <div class="tab-content">
      <div class="container tab-pane active pl-0" id="dashboard">
        <div class="container">
          <div class="row" id="gaugescontainer">
            <?php
            foreach($myboards as $singleRowmyboard) {
              $boardOnlineStatus = false;
              $mysensors2 = myFunctions::getAllSensorsOfBoardWithDashboard($singleRowmyboard['id']);
              foreach($mysensors2 as $singleRowmysensors) {
                $mysensors = myFunctions::getLatestSensorData($singleRowmysensors['id']);
                foreach($mysensors as $singleRowmysensorsLastTimeSeen) {
            ?>
                  <script type="text/javascript">
                    var typid = "<?php echo $singleRowmysensors['typid']; ?>";
                    var sensorId = "<?php echo $singleRowmysensors['id']; ?>";
                    // if typid = 1 (Temp) -> temp 0 - 140°
                    // if typid = 2 (voltage) -> Voltage 8 - 18V
                    if (typid == 1) {
                      var id = "<?php echo $singleRowmysensors['id']; ?>";
                      nameOfSensor = "<?php echo $singleRowmysensors['name']; ?>";
                      einheit = " (°C)";
                      minval = 0;
                      maxval = 140;
                    } else if (typid == 2) {
                      var id = "<?php echo $singleRowmysensors['nameValue1']; ?>";
                      nameOfSensor = "<?php echo $singleRowmysensors['name']; ?>";
                      einheit = " (V)";
                      minval = 8;
                      maxval = 18;
                    } 

                    var $newdiv1 = $( "<div id='gauge" + id + "' class='gauge-container two bg-secondary rounded border border-dark mt-3 mb-3 mr-3 text-light'>" + nameOfSensor + einheit + "</div>" ); 
                    $( "#gaugescontainer" ).append( $newdiv1 );

                  var gauge_temp = Gauge(document.getElementById("gauge" + id),
                    {
                      min: minval,
                      max: maxval,
                      dialStartAngle: 180,
                      dialEndAngle: 0,
                      value: '.', // so that "NaN" is displayed as the default value
                      viewBox: "0 0 100 57",
                      color: function(value) {    // if temp: 0 - 69 blue, 70 - 99 green, 100 - 140 red
                        // if typid = 1 (Temp) -> 0 - 69 blue, 70 - 99 green, 100 - 140 red
                        // if typid = 2 (voltage) -> 0 - 10 red, 11 - 15 lightgreed, > 16 red
                        if ( typid = 1 ) {
                          if(value < 70) {
                              return 'blue';
                            }else if(value < 100) {
                              return 'lightgreen';
                            }else {
                              return 'red';
                            }
                        } else if (typid = 2) {
                          if(value < 11) {
                              return 'red';
                            }else if(value < 16) {
                              return 'lightgreen';
                            }else {
                              return 'red';
                            }
                        }
                      }
                    }
                  );
                  gaugesarray.push(gauge_temp);
                  gaugesArrayHelper.push(sensorId);
                  </script>
                  <?php
                }
              }
            }
            ?>
          </div>
        </div>
      </div>

      <div class="container tab-pane fade pl-0" id="temperatures">
        <div id="chart-container">
          <canvas id="mycanvas"></canvas>
        </div>
      </div>

      <div class="container tab-pane fade pl-0" id="boards">
      <div class="row mt-2">
          <?php
          // Show Online / Offline
          $maxtimeout = strtotime("-15 Minutes");
          foreach($myboards as $singleRowmyboard) {
            $mysensors2 = myFunctions::getAllSensorsOfBoard($singleRowmyboard['id']);
            $boardOnlineStatus = false;
            foreach($mysensors2 as $singleRowmysensors) {
              $mysensors = myFunctions::getLatestSensorData($singleRowmysensors['id']);
              foreach($mysensors as $singleRowmysensorsLastTimeSeen) {
                $dbtimestamp = strtotime($singleRowmysensorsLastTimeSeen['reading_time']);
                if ($dbtimestamp > $maxtimeout) {
                  $deviceIsOnline[$singleRowmyboard['id']] = (bool)true;
                  $boardOnlineStatus = true;
                } else {
                  $deviceIsOnline[$singleRowmyboard['id']] = (bool)false;
                }
              }
            }
          ?>
            <div class='container'>
              <?php
              if ($boardOnlineStatus) {
                echo "<span class='badge badge-success mr-2' style='width: 55px;'>Online</span>";
              } else {
                echo "<span class='badge badge-danger mr-2' style='width: 55px;'>Offline</span>";
              }
              echo "<label class='control-label'>" . $singleRowmyboard['name'] . " (" . $singleRowmyboard['macaddress'] . ")</label>";
              ?>
            </div>
          <?php
          }
          ?>
        </div>
      </div>
    </div>
  </div>

  <?php
  function write_to_log($text)
  {
    $format = "csv"; // Possibilities: csv and txt
    $datum_zeit = date("d.m.Y H:i:s");
    $ip = $_SERVER["REMOTE_ADDR"];
    $site = $_SERVER['REQUEST_URI'];
    $browser = $_SERVER["HTTP_USER_AGENT"];
    $monate = array(1 => "Januar", 2 => "Februar", 3 => "Maerz", 4 => "April", 5 => "Mai", 6 => "Juni", 7 => "Juli", 8 => "August", 9 => "September", 10 => "Oktober", 11 => "November", 12 => "Dezember");
    $monat = date("n");
    $jahr = date("y");
    $dateiname = "logs/log_" . $monate[$monat] . "_$jahr.$format";
    $header = array("Datum", "IP", "Seite", "Browser");
    $infos = array($datum_zeit, $ip, $site, $browser, $text);
    if ($format == "csv") {
      $eintrag2 = '"' . implode('", "', $infos) . '"';
    } else {
      $eintrag2 = implode("\t", $infos);
    }
    $write_header = !file_exists($dateiname);
    $datei = fopen($dateiname, "a");
    if ($write_header) {
      if ($format == "csv") {
        $header_line = '"' . implode('", "', $header) . '"';
      } else {
        $header_line = implode("\t", $header);
      }
      fputs($datei, $header_line . "\n");
    }
    fputs($datei, $eintrag2 . "\n");
    fclose($datei);
  }
  ?>
  <?php
  include("common/footer.inc.php");
  ?>
