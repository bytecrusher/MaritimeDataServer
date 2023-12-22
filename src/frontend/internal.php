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
  require_once("func/writeToLogFunction.func.php");
  //writeToLogFunction::write_to_log("test", $_SERVER["SCRIPT_FILENAME"]);

  if (isset($_SESSION['userobj'])) {
    $currentUser = unserialize($_SESSION['userobj']);
  } else {
    $currentUser = false;
    header("Location: ./index.php");    // if user not loged in
    die();
  }

  include(__DIR__ . "/common/header.inc.php");
  include("func/get_data.php");

  $config = new configuration();
  $varDemoMode = $config::$demoMode;

?>
<!--link rel="stylesheet" href="../node_modules/jquery-ui/dist/themes/base/jquery-ui.css" -->
<link rel="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/jquery-ui.min.css">

<!--link href="../node_modules/fontawesome-free/css/fontawesome.css" rel="stylesheet"-->
<!--link href="../node_modules/fontawesome-free/css/fontawesome.min.css" rel="stylesheet"-->
<link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/fontawesome.min.css">

<!--link href="../node_modules/fontawesome-free/css/brands.css" rel="stylesheet"-->
<link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/brands.css" rel="stylesheet">

<!--link href="../node_modules/fontawesome-free/css/solid.css" rel="stylesheet"-->
<link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/solid.css" rel="stylesheet">

<!--script defer src="../node_modules/fontawesome-free/js/brands.js"></script-->
<script defer src="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/js/brands.js"></script>

<!--script defer src="../node_modules/fontawesome-free/js/solid.js"></script-->
<script defer src="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/js/solid.js"></script>
<!--script defer src="../node_modules/fontawesome-free/js/fontawesome.js"></script-->
<script defer src="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/js/fontawesome.min.js"></script>

<script src="../node_modules/chart.js/dist/chart.js"></script>
<!--script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.js"><script-->

<script src="./js/app.js"></script>
<script src="./js/gauge.js"></script>
<!--script src="../node_modules/jquery-ui/dist/jquery-ui.js"></script-->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>

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

  function mychecksession() { 
    //console.log("sessionCheck");
    $.ajax({
      method: "POST",
      url: "api/checkSession.php",
      data: { }
    })
    .done(function( response ) {
      text = response;
      if (text == "false") {
        //console.log("sessionCheck = false");
        window.location.href = "./index.php";
      }
    });
    //console.log("sessionCheck = true");
  }

  function updateGauges() { 
    mychecksession();
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
      varsensorId = varsensorId.slice(0, -2); 

      $.ajax({
        method: "POST",
        url: "api/getdata.php",
        data: { identifier: varIdent, securitytoken: varToken, data: vardata, sensorId: varsensorId,    NrOfValues: varNrOfValues }
      })
      .done(function( response ) {
        text = response;
        obj = JSON.parse(text);
        for (let i4 = 1; i4 < obj.length; i4++) {   
          try {
            //console.error("obj[0]+i4:" + obj[0]+"."+i4 + ", " + gaugesArrayHelper.includes(obj[0]+"."+i4));
            if (gaugesArrayHelper.includes(obj[0]+"."+i4)) {
              gaugesMap.get(obj[0]+"."+i4).setValueAnimated(obj[i4]);
            }
          } catch (error) {
            console.error("error accessing: " + obj[0]+"."+i4);
          }
        }
      });
    }
  }

var DashboardUpdateInterval = <?php echo($currentUser->getDashboardUpdateInterval()); ?> * 10000;
setInterval(function() { 
    // run every 30 seconds
    updateGauges();
}, DashboardUpdateInterval);
</script>

<div style="padding: 1rem 1rem; margin-bottom: 1rem; background: #acacac;">
    <div class="container">
      <h1>Welcome <?php echo htmlentities($currentUser->getFirstname()); ?></h1>
    </div>
  </div>

  <div class="main-container">
  <div class="container" style="padding: 0px">
    <div id="alert-container">
      <?php
        if(($currentUser->getUserGroupAdmin() == 1) ) {
          // test if install folder exist
          if (is_dir('./../install')) {
            echo "<div class='alert alert-danger alert-dismissible' role='alert'>Please remember to remove \"install\" dir. <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
          }
				}
        ?>
    </div>

    <!-- Nav tabs -->
    <ul class="nav nav-tabs" role="tablist">
      <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#dashboard" style="padding-right: 8px;padding-left: 8px;">Dashboard</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#temperatures" id='hreftemperatures' style="padding-right: 8px;padding-left: 8px;">Charts</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#boards" id='hrefboards' style="padding-right: 8px;padding-left: 8px;">Boards</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#mapContainer" id='hrefmap' style="padding-right: 8px;padding-left: 8px;">Map</a>
      </li>
      <?php
				if(($currentUser->getUserGroupAdmin() == 1) ) {
				?>
					<li class='nav-item' role='presentation'><a class='nav-link' href='#debug' role='tab' data-bs-toggle='tab'>Debug</a></li>
				<?php
				}
			?>
    </ul>

    <div class="tab-content" style="border-bottom-left-radius: 10px; border-bottom-right-radius: 10px; padding-bottom: 15px; background: white">

      <!-- Show dashboard -->
      <div class="container tab-pane active position-relative" id="dashboard" style="padding-left: 10px; padding-right: 10px;">
      <div class="position-absolute" id="click_lockUnlock" style="top: -40px; right: 0px;" data-bs-toggle="collapse" data-bs-target=".multi-collapse" aria-expanded="false">
        <i class="bi bi-lock-fill" style="font-size:20px; color: #007bff"></i>
      </div>

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
                        <legend>
                        <?php $deviceOnline = checkDeviceIsOnline($singleRowmyboard->getId());
                        if ($deviceOnline) {
                          ?>
                            <span class='badge bg-success mr-2'>Online</span>
                          <?php
                          } else {
                          ?>
                            <span class='badge bg-danger mr-2'>Offline</span>
                          <?php
                          }
                        ?>
                        <div style="float: right; margin-top: 3px; margin-left: 10px"><?php echo $singleRowmyboard->getName() ?></div>
                        </legend>
                          <ul class='card-block' id='gaugescontainer<?php echo $singleRowmyboard->getId() ?>' style="display: flex; justify-content: center; flex-wrap: wrap;">
                <?php
                $boardOnlineStatus = false;
                $mysensors2 = myFunctions::getAllSensorsOfBoardWithDashboardWithTypeName($singleRowmyboard->getId());
                if ($mysensors2 == null) {
                  ?>
                    <div class='col m-b-20'>no Sensors</div>
                  <?php
                }
                ?>
                
                <?php
                if ($mysensors2 != null) {
                  foreach($mysensors2 as $singleRowmysensors) {
                    $mysensors = myFunctions::getLatestSensorData($singleRowmysensors['id']);                    
                    foreach($mysensors as $singleRowmysensorsLastTimeSeen) {
                      $sensortype = myFunctions::getSensorType($singleRowmysensors['typid']);
                      $sensConfig = myFunctions::getSensorConfig($singleRowmysensors['id']);
                      for ($i = 1; $i <= $sensConfig['NrOfUsedSensors']; $i++) {
                        if (($mysensors != null) && ($singleRowmysensors['Value' . $i . 'onDashboard'])) {
                          //var_dump($singleRowmysensors['Value' . $i . 'onDashboard'])
                          //var_dump($deviceOnline);
                            ?>
                            <li id='gauge<?php echo $singleRowmysensors['id'] . "." . $i; ?>' data-id=<?php echo $singleRowmysensors['Value' . $i . 'DashboardOrdnerNr']; ?> class='ui-state-default gauge-container two bg-secondary rounded border border-dark text-light <?php if(!$deviceOnline) { echo "disabled"; } ?>'>
                              <div id='div_click_settings<?php echo $singleRowmysensors['id'] . "." . $i; ?>' class='multi-collapse' style='display:none; z-index: 100; float:right;'>
                                <i id='click_settings<?php echo $singleRowmysensors['id'] . "." . $i; ?>' class='bi bi-gear-fill' data-bs-toggle='modal' data-bs-target='#exampleModal' style='font-size:20px; color: #007bff'>
                                </i>
                              </div>
                              <div style='height:30px;'><?php echo $singleRowmysensors['nameValue' . $i]; ?> (<?php echo $sensortype['siUnitVal' . $i]; ?>)
                              </div>
                            </li>
                            <script>
                              if (<?php echo sizeof($mysensors); ?> != null) {

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
                            }
                            </script>
                            <?php
                        }
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
                }
                ?>

                <!--li id='gauge' data-id='10' class='ui-state-default justify-content-center gauge-container two bg-secondary rounded border border-dark text-light'>
                  <div id='div_click_settings' class='multi-collapse' style='display:none; z-index: 100; float:right;'>
                    <i id='click_settings' class='bi bi-gear-fill' data-bs-toggle='modal' data-bs-target='#exampleModal' style='font-size:20px; color: #007bff'>
                     </i>
                  </div>
                  <div style='height:30px;'>Bilgen Alarm</div>
                  <div class="text-center">
                    <i class="bi bi-water"></i>
                  </div>
                </li-->

                </ul>
              </fieldset>
            </div>
          </div>
        <?php
              }
            }
            ?>
        </div>
      </div>

      <!-- Show temperatures as chart -->
      <!-- TODO: for every board its own canvas. -->
      <div class="container tab-pane fade pl-0 pr-0" id="temperatures">
        <fieldset>
          <div id="chart-container">
            <canvas id="mycanvas"></canvas>
          </div>
        </fieldset>
      </div>

      <!-- Show Board overview -->
      <div class="container tab-pane fade pl-0 pr-0" id="boards">
        <fieldset>
            <?php          
            foreach($boardObjsArray as $singleBoardObj) {
              $transmissionpath = 0;
              $mysensors2 = myFunctions::getAllSensorsOfBoard($singleBoardObj->getId());
              $boardOnlineStatus = false;
              $mysensorIdlist = null;
              foreach($mysensors2 as $singleRowmysensors) {
                if ($mysensorIdlist == null) {
                  $mysensorIdlist = $singleRowmysensors['id'];
                } else {
                  $mysensorIdlist = $mysensorIdlist . ", " . $singleRowmysensors['id'];
                }
              }
              $mysensors = myFunctions::getLatestSensorData($mysensorIdlist);
              foreach($mysensors as $singleRowmysensorsLastTimeSeen) {
                $transmissionpath = $singleRowmysensorsLastTimeSeen['transmissionpath'];
                $dbtimestamp = strtotime($singleRowmysensorsLastTimeSeen['reading_time']);

                // Show Online / Offline
                // TODO if demo mode == true, then no limit.
                if ($varDemoMode) {
                  $maxtimeout = strtotime("-10 Years");
                } else {
                  $maxtimeout = strtotime("-" . $singleBoardObj->getOfflineDataTimer() . " Minutes"); // For show Online / Offline
                }

                if ($dbtimestamp > $maxtimeout) {
                  $deviceIsOnline[$singleBoardObj->getId()] = (bool)true;
                  $boardOnlineStatus = true;
                } else {
                  $deviceIsOnline[$singleBoardObj->getId()] = (bool)false;
                }
              }
            ?>
              <div class='container mt-2'>
                <?php
                if ($boardOnlineStatus) {
                ?>
                  <span class='badge bg-success mr-2' style='width: 55px;'>Online</span>
                <?php
                  if ($transmissionpath == 1) {
                    ?>
                      <span class='badge bg-success mr-2' style='width: 55px;'>WiFi</span>
                    <?php
                  } elseif ($transmissionpath == 2) {
                    ?>
                      <span class='badge bg-success mr-2' style='width: 55px;'>Lora</span>
                    <?php
                  } else {
                      ?>
                      <!--span class='badge bg-danger mr-2' style='width: 55px;'>Offline</span-->
                    <?php
                  }
                } else {
                ?>
                  <span class='badge bg-danger mr-2' style='width: 55px;'>Offline</span>
                <?php
                }
                ?>

                  <label class='control-label' style='padding-left: 5px'><?php echo($singleBoardObj->getName()) ?> (<?php echo($singleBoardObj->getMacaddress()) ?>)</label>
              </div>
            <?php
            }
            ?>
        </fieldset>
      </div>

      <!-- Show temperatures as table, only for admin -->
      <div class="container tab-pane fade pl-0 pr-0" id="debug">
        <div class="p-2" id="chart-container">
          All Sensor Values as a table from ttnDataLoraBoatMonitor:
        </div>
        <?php
            include("./../receiver/ttndata/index.php");
          ?>
      </div>

      <!-- Show map -->
      <div class="container tab-pane fade pl-0" id="mapContainer">
        <div class="row mt-2">
          <div class="container">
            <?php include("./openstreetmaps.php"); ?>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- Modal -->
  <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" role="dialog"  aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <!-- definition in "inputmask_sensors.php"-->
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

          wrapper = $('.card-block');
          onceSensorOrderFail = false;
          onceSensorOrderDone = false;
          for (let i=0; i<wrapper.length; i++) {
            $("#" + wrapper[i].id + " .gauge-container").each(function(index) {
              //console.log("new order nr: " + index + ", old ordner nr: " + $( this ).attr("data-id"));
              $( this ).attr("data-id", index);
              var tempnumber = $( this ).attr("id").replace('gauge', '');
              var SensorIdChannel = tempnumber.split('.');
              $.ajax({
                method: "POST",
                url: "api/updateData.php",
                data: { update: "sensorOrdnerNumber",
                    channel: SensorIdChannel[1],
                    ordnernumber: $( this ).attr("data-id"),
                    id: SensorIdChannel[0] }
              })
                .done(function( response ) {
                  if (!onceSensorOrderDone) {
                    g = document.createElement('div');
                    g.setAttribute("class", "alert alert-success alert-dismissible bg-opacity-70 bg-gray bg-opacity-20 shadow-risen");
                    g.setAttribute("role", "alert");
                    g.innerHTML = "Sensor Order saved.<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
                    const bsAlert = new bootstrap.Alert(g);
                    // Dismiss time out
                    setTimeout(() => {
                      bsAlert.close();
                    }, 5000);
                    $("#alert-container").append(g);
                    onceSensorOrderDone = true;
                  }
                })
                .fail(function( response ) {
                  if (!onceSensorOrderFail) {
                    g = document.createElement('div');
                    g.setAttribute("class", "alert alert-danger alert-dismissible bg-opacity-70 bg-gray bg-opacity-20 shadow-risen");
                    g.setAttribute("role", "alert");
                    g.innerHTML = "Sensor Order not saved.<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
                    const bsAlert = new bootstrap.Alert(g);
                    // Dismiss time out
                    setTimeout(() => {
                      bsAlert.close();
                    }, 5000);
                    $("#alert-container").append(g);
                    onceSensorOrderFail = true;
                  }
                });
            });
          }
        }

      });

      // TODO: Bug!!! on more than one board, the sensors will be added to everyone.
      $( document ).ready(function() {
        $('.gauge-container').css("cursor", "auto");
        var wrapper = $('.card-block');
        for (let i=0; i<wrapper.length; i++) {
          var wrapper2 = $('#' + wrapper[i].id);
          wrapper2.find('.gauge-container').sort(function (a, b) {
          return +a.dataset.id - +b.dataset.id;
          }).appendTo( wrapper2 );
        }
      });
      
    (function () {
        $('#exampleModal').on('show.bs.modal', function (e) {
        str = e.relatedTarget.id;
        let newStr = str.replace('click_settings', '');
        const myArray = newStr.split(".");
        $.ajax({
          url: 'inputmask_sensors.php?id=' + myArray[0] + '&channel=' + myArray[1] + '&modal=true'
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
