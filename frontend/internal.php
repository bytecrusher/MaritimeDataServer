<?php
/*
* File for Display Data for the user
*
*/
// TODO add the Option to define Virtual Sensor group, to group them visualy.

  session_start();
  require_once("func/dbConfig.func.php");
  require_once("func/myFunctions.func.php");
  require_once("func/user.class.php");
  require_once("func/board.class.php");
  include("common/header.inc.php");

  if (isset($_SESSION['userobj'])) {
    $currentUser = unserialize($_SESSION['userobj']);
  } else {
    $currentUser = false;
    header("Location: ./index.php");    // if user not loged in
    die();
  }

  include("func/get_data.php");
?>
<link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.js"></script>
<script src="js/app.js"></script>
<script src="js/gauge.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.js"></script>

<?php
  if ($currentUser != false) {
    $myboardsIdList = $currentUser->getMyBoardsId();
  }
  $boardObjsArray = array();
  foreach ($myboardsIdList as $key => $value) {
    $boardobj = new board($value['id']);
    array_push($boardObjsArray, $boardobj);
  }
?>

<script>
  $(document).ready(function() {
    updateGauges();
  });
  let gaugesArrayHelper = new Array();
  var SensorArrayHelper = new Array();
  var gaugesMap = new Map();
  var gaugesArrayHelperbig = new Array();

  function updateGauges() { 
    var varIdent = getCookie("identifier");
    var varToken = getCookie("securitytoken");
    var varboardId = null;
    var varsensorId = null;
    var vardata = "sensor";
    var varNrOfValues = "1";
    let text;
    var obj;

    for (let i in gaugesArrayHelper) {
      varsensorId = gaugesArrayHelper[i];
      if (varsensorId.endsWith(".1") ) {
        varsensorId = varsensorId.slice(0, -2); 

        $.ajax({
        method: "POST",
        url: "api/getdata.php",
        data: { identifier: varIdent, securitytoken: varToken, data: vardata, sensorId: varsensorId, NrOfValues: varNrOfValues }
        })
        .done(function( response ) {
          text = response;
          obj = JSON.parse(text);
          for (let i4 = 1; i4 < obj.length; i4++) { 
            gaugesMap.get(obj[0]+"."+i4).setValueAnimated(obj[i4])
          }
      });
      }
    }
  }

setInterval(function() { 
    // run every 30 seconds
    updateGauges();
}, 30000);

var gaugesSortableCounter = 0;
</script>

  <div style="padding: 1rem 1rem; margin-bottom: 1rem; background: #acacac;">
    <div class="container">
      <h1>Welcome <?php echo htmlentities($currentUser->getFirstname()); ?></h1>
    </div>
  </div>
  <div class="main-container">

  <div class="container" style="padding: 0px">
    <!-- Nav tabs -->
    <ul class="nav nav-tabs" role="tablist">
      <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#dashboard" style="padding-right: 8px;padding-left: 8px;">Dashboard</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#temperatures" id='hreftemperatures' style="padding-right: 8px;padding-left: 8px;">Charts</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#allSensorsTable" id='hrefallSensorsTable' style="padding-right: 8px;padding-left: 8px;">Table</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#boards" id='hrefboards' style="padding-right: 8px;padding-left: 8px;">Boards</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#mapContainer" id='hrefmap' style="padding-right: 8px;padding-left: 8px;">Map</a>
      </li>
    </ul>

    <div class="tab-content" style="border-bottom-left-radius: 10px; border-bottom-right-radius: 10px; padding-bottom: 15px;">

      <!-- Show dashboard -->
      <div class="container tab-pane active position-relative" id="dashboard" style="padding-left: 10px; padding-right: 10px;">
      <div class="position-absolute" id="click_lockUnlock" style="top: -40px; right: 0px;" data-bs-toggle="collapse" data-bs-target=".multi-collapse" aria-expanded="false"><i class="bi bi-lock-fill" style="font-size:20px; color: #007bff"></i></div>

        <div class="page-content page-container" id="page-content" style="--bs-gutter-x: 0rem; ">
        </div>
        <div class="container" style="--bs-gutter-x: 0; padding-right: 0px; padding-left: 0px;">
            <?php
            foreach($boardObjsArray as $singleRowmyboard) {
              if($singleRowmyboard->isOnDashboard() == 1) {
                ?>
                  <div class='row d-flex justify-content-center'>
                  <div class='col-lg-12 col-xl-12' style='padding-right: 0px; padding-left: 0px;'>
                  <fieldset>
                  <legend><?php echo $singleRowmyboard->getName() ?></legend>
                  <ul class='card-block' id='gaugescontainer<?php echo $singleRowmyboard->getId() ?>'>
                <?php
                $boardOnlineStatus = false;
                $mysensors2 = myFunctions::getAllSensorsOfBoardWithDashboardWithTypeName($singleRowmyboard->getId());
                if ($mysensors2 == null) {
                  ?>
                    <div class='col m-b-20'>no Sensors</div>
                  <?php
                }
                ?>
                </ul></fieldset></div></div>
                <?php
                foreach($mysensors2 as $singleRowmysensors) {
                  $mysensors = myFunctions::getLatestSensorData($singleRowmysensors['id']);                    
                  foreach($mysensors as $singleRowmysensorsLastTimeSeen) {
                    $sensortype = myFunctions::getSensorType($singleRowmysensors['typid']);
                    $sensConfig = myFunctions::getSensorConfig($singleRowmysensors['id']);
                    for ($i = 1; $i <= $sensConfig['NrOfUsedSensors']; $i++) {
              ?>
                    <script>
                      var $newdiv1 = $( "" + 
                        "<li id='gauge" + "<?php echo $singleRowmysensors['id'] . "." . $i; ?>" + "' data-id=<?php echo $singleRowmysensors['Value' . $i . 'DashboardOrdnerNr']; ?> class='ui-state-default gauge-container two bg-secondary rounded border border-dark text-light'>" + 
                          "<div id='div_click_settings<?php echo $singleRowmysensors['id'] . "." . $i; ?>' class='multi-collapse' style='display:none; z-index: 100; float:right;'>" + 
                            "<i id='click_settings<?php echo $singleRowmysensors['id'] . "." . $i; ?>' class='bi bi-gear-fill' data-bs-toggle='modal' data-bs-target='#exampleModal' style='font-size:20px; color: #007bff'>" +
                            "<\/i>" + 
                          "<\/div>" + 
                          "<div style='height:30px;'>" + "<?php echo $singleRowmysensors['nameValue' . $i]; ?>" + " (" + "<?php echo $sensortype['siUnitVal' . $i]; ?>" + ")" + 
                          "<\/div>" +
                        "<\/li>" ); 
                        gaugesSortableCounter++;
                      $( "#gaugescontainer" + "<?php echo $singleRowmyboard->getId(); ?>" ).append( $newdiv1 );

                    var gauge_temp = Gauge(document.getElementById("gauge" + "<?php echo $singleRowmysensors['id'] . "." . $i; ?>"),
                      {
                        min: <?php echo $sensConfig['Value' . $i . 'GaugeMinValue'] ?>,
                        max: <?php echo $sensConfig['Value' . $i . 'GaugeMaxValue'] ?>,
                        dialStartAngle: 180,
                        dialEndAngle: 0,
                        value: '.', // so that "NaN" is displayed as the default value
                        viewBox: "0 0 100 57",
                        id: "<?php echo $singleRowmysensors['id'] . "." . $i; ?>",
                        color: function(value) {
                          if(value < <?php echo $sensConfig['Value' . $i . 'GaugeRedAreaLowValue'] ?>) {
                            return '<?php echo $sensConfig['Value' . $i . 'GaugeRedAreaLowColor'] ?>';
                          }else if(value < <?php echo $sensConfig['Value' . $i . 'GaugeRedAreaHighValue'] ?>) {
                            return '<?php echo $sensConfig['Value' . $i . 'GaugeNormalAreaColor'] ?>';
                          }else {
                            return '<?php echo $sensConfig['Value' . $i . 'GaugeRedAreaHighColor'] ?>';
                          }
                        },
                      }
                    );
                    gaugesArrayHelper.push("<?php echo $singleRowmysensors['id'] . "." . $i; ?>");
                    gaugesMap.set("<?php echo $singleRowmysensors['id'] . "." . $i; ?>", gauge_temp);                    
                    </script>
                    <?php
                  }
                  ?>
                  <script>
                    SensorArrayHelper.push(<?php echo $singleRowmysensors['id']; ?>);
                    var valuetopush = {};
                    valuetopush["sensorId"] = "<?php echo $singleRowmysensors['id']; ?>";
                    valuetopush["typid"] = "<?php echo $singleRowmysensors['typid']; ?>";
                    valuetopush["NrOfSensors"] = "<?php echo $singleRowmysensors['typid']; ?>";
                    valuetopush["NameOfSensors"] = "<?php echo $singleRowmysensors['nameValue1']; ?>";
                    gaugesArrayHelperbig.push(valuetopush);
                  </script>
                    <?php
                  }
                }
                ?>
                <?php
              }
            }
            ?>
          </div>
      </div>

      <!-- Show temperatures as chart -->
      <!-- TODO: for every board its own canvas. -->
      <div class="container tab-pane fade pl-0 pr-0" id="temperatures">
        <div id="chart-container">
          <canvas id="mycanvas"></canvas>
        </div>
      </div>

      <!-- Show Board overview -->
      <div class="container tab-pane fade pl-0 pr-0" id="boards">
      <div class="row mt-2">
          <?php
          // Show Online / Offline
          $maxtimeout = strtotime("-15 Minutes"); // For show Online / Offline
            foreach($boardObjsArray as $singleRowmyboard) {
            $mysensors2 = myFunctions::getAllSensorsOfBoardold($singleRowmyboard->getId());
            $boardOnlineStatus = false;
            foreach($mysensors2 as $singleRowmysensors) {
              $mysensors = myFunctions::getLatestSensorData($singleRowmysensors['id']);
              foreach($mysensors as $singleRowmysensorsLastTimeSeen) {
                $dbtimestamp = strtotime($singleRowmysensorsLastTimeSeen['reading_time']);
                if ($dbtimestamp > $maxtimeout) {
                  $deviceIsOnline[$singleRowmyboard->getId()] = (bool)true;
                  $boardOnlineStatus = true;
                } else {
                  $deviceIsOnline[$singleRowmyboard->getId()] = (bool)false;
                }
              }
            }
          ?>
            <div class='container'>
              <?php
              if ($boardOnlineStatus) {
                echo "<span class='badge bg-success mr-2' style='width: 55px;'>Online</span>";
              } else {
                echo "<span class='badge bg-danger mr-2' style='width: 55px;'>Offline</span>";
              }
              echo "<label class='control-label' style='padding-left: 5px'>" . $singleRowmyboard->getName() . " (" . $singleRowmyboard->getMacaddress() . ")</label>";
              ?>
            </div>
          <?php
          }
          ?>
        </div>
      </div>

      <!-- Show Sensors as table -->
      <div class="container tab-pane fade pl-0" id="allSensorsTable">
        <div class="row mt-2">
        <div class="container table-responsive">
            All Sensor Values as a table
          <?php
            include("./../receiver/ttndata/index.php");
          ?>
          </div>
        </div>
      </div>

      <!-- Show map -->
      <div class="container tab-pane fade pl-0" id="mapContainer">
      <div class="row mt-2">
      <div class="container">
          <?php
            include("./openstreetmaps.php");
          ?>
          </div>
      </div>
      </div>
    </div>
  </div>

<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" role="dialog"  aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <!-- definition in "inputmaske_sensors.php"-->
    </div>
  </div>
</div>
</div>
    <script>
      $('#click_lockUnlock').click(function() {
        $("i", this).toggleClass("bi bi-lock-fill bi bi-unlock-fill");
        if (($("i", this).attr('class') ) == "bi bi-unlock-fill") {
          $('.gauge-container').css("cursor", "move");
          $(".card-block").sortable();
          $(".card-block").sortable( "option", "disabled", false );
          $(".card-block").disableSelection();
          $('.multi-collapse').toggle();
        } else {
          $('.multi-collapse').toggle();
          $('.gauge-container').css("cursor", "auto");
          $(".card-block").sortable("disable");
          console.log("locked");
          $(".gauge-container").each(function(index) {
            $( this ).attr("data-id", index);
            // TODO: save data-id into db DashboardOrdnerNr
            console.log("new order nr: " + index + ", old ordner nr: " + $( this ).attr("data-id"));
          });
        }

      });

      $( document ).ready(function() {
        $('.gauge-container').css("cursor", "auto");

        // sort Gauges by data-id (DashboardOrdnerNr)
        var $wrapper = $('.card-block');
        $wrapper.find('.gauge-container').sort(function (a, b) {
            return +a.dataset.id - +b.dataset.id;
        })
        .appendTo( $wrapper );

      });
      
    (function () {
        $('#exampleModal').on('show.bs.modal', function (e) {
        str = e.relatedTarget.id;
        let newStr = str.replace('click_settings', '');
        const myArray = newStr.split(".");
        $.ajax({
          url: 'inputmaske_sensors.php?id=' + myArray[0] + '&channel=' + myArray[1] + '&modal=true'
        }).done(function(response) {
          $('.modal-content').html(response);
        });
      });
    })();

    function getCookie(cname) {
      let name = cname + "=";
      let decodedCookie = decodeURIComponent(document.cookie);
      let ca = decodedCookie.split(';');
      for(let i = 0; i <ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) == ' ') {
          c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
          return c.substring(name.length, c.length);
        }
      }
      return "";
    }
    </script>
  <?php
    include("common/footer.inc.php");
  ?>
