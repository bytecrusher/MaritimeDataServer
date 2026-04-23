<?php
  /*
  * File for Display Data for the user
  *
  */
  // Note: add the option to define virtual sensor groups for visual grouping.

  session_start();
  require_once dirname(__DIR__) . "/bootstrap/app.php";
  require_once dirname(__DIR__) . "/app/Infrastructure/Database/dbConfig.func.php";
  require_once dirname(__DIR__) . "/app/Application/myFunctions.func.php";
  require_once dirname(__DIR__) . "/app/Application/InternalPageService.php";
  require_once dirname(__DIR__) . "/app/Domain/User/user.class.php";
  require_once dirname(__DIR__) . "/app/Domain/Board/board.class.php";
  require_once dirname(__DIR__) . "/app/Infrastructure/Logging/writeToLogFunction.func.php";

  $currentUser = InternalPageService::resolveCurrentUserFromSession();

  if (!$currentUser) {
    header("Location: ./index.php");    // if user not logged in
    die();
  }

  require_once dirname(__DIR__) . "/app/Domain/Board/get_data.php";
  $config = new configuration();
  $pageData = InternalPageService::buildPageData($currentUser, $config);
  $myBoardsIdList = $pageData['myBoardsIdList'];
  $boardObjsArray = $pageData['boardObjsArray'];
  $mapBoardNames = $pageData['mapPayload']['boardNames'];
  $mapGpsData = $pageData['mapPayload']['gpsData'];
  $dashboardUpdateIntervalMs = $pageData['dashboardUpdateIntervalMs'];
  $varDemoMode = $pageData['demoMode'];
  $showInstallAlert = $pageData['showInstallAlert'];
  $hasBoards = $pageData['hasBoards'];

  include_once dirname(__DIR__) . "/app/Presentation/Common/header.inc.php"; // NOSONAR - Legacy Template-Einbindung
?>
<link rel="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/jquery-ui.min.css">
<link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/fontawesome.min.css">
<link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/brands.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/solid.css" rel="stylesheet">
<script defer src="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/js/brands.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/js/solid.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/js/fontawesome.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script src="<?php echo htmlspecialchars(mds_asset_path('js/gauge.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
<script src="<?php echo htmlspecialchars(mds_asset_path('js/dashboard.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
<script src="<?php echo htmlspecialchars(mds_asset_path('js/app.js'), ENT_QUOTES, 'UTF-8'); ?>"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"></script>

<style>
  .dashboard-shell {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: 1rem 0;
  }
  .dashboard-toolbar {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    align-items: center;
    justify-content: space-between;
    padding: 0.9rem 1rem;
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 1rem;
    background: linear-gradient(135deg, #f8fbff 0%, #eef4f9 100%);
  }
  .dashboard-toolbar-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    align-items: center;
  }
  .dashboard-stat-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.45rem 0.7rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.85);
    border: 1px solid rgba(15, 23, 42, 0.08);
    font-size: 0.92rem;
  }
  .dashboard-board-card {
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 1rem;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.08);
  }
  .dashboard-board-card.is-offline {
    opacity: 0.86;
  }
  .dashboard-board-header {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.1rem;
    border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    background: linear-gradient(180deg, rgba(248, 250, 252, 0.95) 0%, rgba(255, 255, 255, 1) 100%);
  }
  .dashboard-board-title {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
  }
  .dashboard-board-title h3 {
    margin: 0;
    font-size: 1.2rem;
  }
  .dashboard-board-subtitle {
    color: #64748b;
    font-size: 0.92rem;
  }
  .dashboard-board-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
  }
  .dashboard-board-summary {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    color: #475569;
    font-size: 0.9rem;
  }
  .dashboard-board-gauges {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 1rem;
    padding: 1rem;
  }
  .dashboard-gauge-card {
    position: relative;
    min-height: 235px;
    padding: 0.9rem;
    border-radius: 1rem;
    border: 1px solid rgba(148, 163, 184, 0.22);
    background: radial-gradient(circle at top left, rgba(241, 245, 249, 0.94), rgba(226, 232, 240, 0.9));
    color: #0f172a;
    transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
  }
  .dashboard-gauge-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 30px rgba(15, 23, 42, 0.10);
    border-color: rgba(37, 99, 235, 0.20);
  }
  .dashboard-gauge-card.disabled {
    opacity: 0.72;
  }
  .dashboard-gauge-top {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 0.75rem;
    margin-bottom: 0.75rem;
  }
  .dashboard-gauge-headline {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
    min-width: 0;
  }
  .dashboard-gauge-headline strong {
    font-size: 1rem;
    line-height: 1.2;
    overflow-wrap: anywhere;
  }
  .dashboard-gauge-headline span {
    color: #475569;
    font-size: 0.84rem;
    line-height: 1.2;
    overflow-wrap: anywhere;
  }
  .dashboard-gauge-value {
    text-align: left;
    font-weight: 700;
    font-size: 1rem;
    line-height: 1.15;
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    align-items: baseline;
  }
  .dashboard-gauge-value-number {
    font-size: 1.35rem;
    letter-spacing: -0.02em;
  }
  .dashboard-gauge-value-unit {
    font-size: 0.95rem;
    color: #334155;
    overflow-wrap: anywhere;
  }
  .dashboard-gauge-visual {
    height: 122px;
  }
  .dashboard-gauge-meta {
    display: flex;
    justify-content: space-between;
    gap: 0.5rem;
    margin-top: 0.45rem;
    color: #475569;
    font-size: 0.82rem;
  }
  .dashboard-gauge-meta span {
    overflow-wrap: anywhere;
  }
  .dashboard-empty-state {
    padding: 1rem;
    border-radius: 0.9rem;
    background: rgba(241, 245, 249, 0.7);
    color: #64748b;
  }
  .dashboard-board-hidden {
    display: none !important;
  }
  @media (max-width: 767.98px) {
    .dashboard-board-gauges {
      grid-template-columns: 1fr;
    }
    .dashboard-toolbar {
      align-items: flex-start;
    }
  }
</style>

<script>
  $(document).ready(function() {
    updateGauges();
  });
  let gaugesArrayHelper = new Array();
  var SensorArrayHelper = new Array();
  var gaugesMap = new Map();
  var gaugesArrayHelperBig = new Array();

  function checkSession() {
    $.ajax({
      method: "POST",
      url: "api/checkSession.php",
      data: { }
    })
    .done(function( response ) {
      text = response;
      if (text == "false") {
        window.location.href = "./index.php";
      }
    });
  }

  function updateGauges() {
    checkSession();
    var varIdent = getCookie("identifier");
    var varToken = getCookie("securityToken");
    //var varBoardId = null;
    var varSensorId = null;
    var varData = "sensor";
    var varNrOfValues = "1";
    let text;
    var obj;

    for (let i in gaugesArrayHelper) {
      varSensorId = gaugesArrayHelper[i];
      varSensorId = varSensorId.slice(0, -2);

      $.ajax({
        method: "POST",
        url: "api/getdata.php",
        data: { identifier: varIdent, securityToken: varToken, data: varData, sensorId: varSensorId, NrOfValues: varNrOfValues }
      })
      .done(function( response ) {
        text = response;
        try {
          obj = JSON.parse(text);
        } catch (error) {
          console.error("invalid gauge response for sensor " + varSensorId, error, text);
          return;
        }

        if (!Array.isArray(obj) || obj.length < 2) {
          console.error("unexpected gauge payload for sensor " + varSensorId, obj);
          return;
        }

        for (let i4 = 1; i4 < obj.length; i4++) {
          try {
            //console.error("obj[0]+i4:" + obj[0]+"."+i4 + ", " + gaugesArrayHelper.includes(obj[0]+"."+i4));
            if (gaugesArrayHelper.includes(obj[0]+"."+i4)) {
              const numericValue = Number.parseFloat(obj[i4]);
              if (Number.isFinite(numericValue)) {
                gaugesMap.get(obj[0]+"."+i4).setValueAnimated(numericValue);
                const gaugeCard = document.getElementById("gauge" + obj[0] + "." + i4);
                if (gaugeCard) {
                  const valueNode = gaugeCard.querySelector('.dashboard-gauge-value-number');
                  if (valueNode) {
                    valueNode.textContent = numericValue.toFixed(2);
                  }
                }
              } else {
                console.warn("non-numeric gauge value for sensor " + obj[0] + "." + i4, obj[i4]);
              }
            }
          } catch (error) {
            console.error("error accessing: " + obj[0]+"."+i4);
          }
        }
      })
      .fail(function(jqxhr, settings, ex) {
        console.error('failed (updateGauges), ' + varSensorId + ", " + ex);
      });
    }
  }

  var DashboardUpdateInterval = <?php echo $dashboardUpdateIntervalMs; ?>;
  setInterval(function() {
    // run every 30 seconds
    updateGauges();
  }, DashboardUpdateInterval);
</script>

<div style="padding: 1rem 1rem; margin-bottom: 1rem; background: #acacac;">
    <div class="container">
      <h1>Welcome <?php echo htmlentities($currentUser->getFirstName()); ?>
      <?php
      if (configuration::$demoMode) {
        echo htmlentities("  (Demo mode)");
      }
      ?></h1>
    </div>
  </div>

  <div class="main-container">
  <div class="container" style="padding: 0px">
    <div id="alert-container">
      <?php
        if($showInstallAlert) {
          echo "<div class='alert alert-danger alert-dismissible' role='alert'>Please remember to remove \"install\" dir. <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button></div>";
        }

        if (!$hasBoards) {
          echo "<div class='alert alert-danger' role='alert'>No Board added. Please add a board first.</div>";
        }
        ?>
    </div>

    <!-- Nav tabs -->
    <ul class="nav nav-tabs" id="internalTabs" role="tablist">
      <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#dashboard" role="tab">Dashboard</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#charts" role="tab">Charts</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#boards" role="tab">Boards</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="hrefmap" data-bs-toggle="tab" href="#mapContainer" role="tab">Map</a>
      </li>
      <?php
        if($currentUser->getUserGroupAdmin() == 1 ) {
      ?>
        <li class='nav-item'><a class='nav-link' data-bs-toggle='tab' href='#debug' role='tab'>Debug</a></li>
        <?php
        }
        ?>
    </ul>

    <div class="tab-content" style="border-bottom-left-radius: 10px; border-bottom-right-radius: 10px; padding-bottom: 15px; background: white">

      <!-- Show dashboard -->
      <div class="container tab-pane fade show active position-relative" id="dashboard">
        <div class="position-absolute" id="click_lockUnlock" style="top: -40px; right: 0px;" data-bs-toggle="collapse" data-bs-target=".multi-collapse" aria-expanded="false">
          <i class="bi bi-lock-fill" style="font-size:20px; color: #007bff"></i>
        </div>

        <div class="dashboard-shell">
          <div class="dashboard-toolbar">
            <div class="dashboard-toolbar-meta">
              <span class="dashboard-stat-pill"><strong><?php echo count($boardObjsArray); ?></strong> Devices</span>
              <span class="dashboard-stat-pill"><strong><?php echo (int)$dashboardUpdateIntervalMs / 1000; ?>s</strong> Refresh</span>
            </div>
            <div class="dashboard-toolbar-meta">
              <div class="form-check form-switch m-0">
                <input class="form-check-input" type="checkbox" id="dashboard-online-only-toggle">
                <label class="form-check-label" for="dashboard-online-only-toggle">Nur Online-Devices</label>
              </div>
            </div>
          </div>
          <div class="page-content page-container" id="page-content" style="--bs-gutter-x: 0rem; "></div>
          <div class="container" style="--bs-gutter-x: 0; padding-right: 0px; padding-left: 0px;">
            <?php
            foreach($boardObjsArray as $singleRowmyboard) {
              if($singleRowmyboard->isOnDashboard() == 1) {
                $deviceOnline = checkDeviceIsOnline($singleRowmyboard->getId());
                $mySensors2 = myFunctions::getAllSensorsOfBoardWithDashboardWithTypeName($singleRowmyboard->getId());
                $boardGaugeCount = 0;
                ?>
                  <section class="dashboard-board-card <?php if(!$deviceOnline) { echo 'is-offline'; } ?>" data-dashboard-board-id="<?php echo $singleRowmyboard->getId(); ?>" data-dashboard-online="<?php echo $deviceOnline ? '1' : '0'; ?>">
                    <div class="dashboard-board-header">
                      <div class="dashboard-board-title">
                        <h3><?php echo htmlentities($singleRowmyboard->getName()); ?></h3>
                        <div class="dashboard-board-subtitle"><?php echo htmlentities($singleRowmyboard->getMacAddress()); ?></div>
                        <div class="dashboard-board-summary">
                          <span>Update alle <?php echo (int)$dashboardUpdateIntervalMs / 1000; ?>s</span>
                          <span>Offline-Timer: <?php echo (int)$singleRowmyboard->getOfflineDataTimer(); ?> min</span>
                        </div>
                      </div>
                      <div class="dashboard-board-badges">
                        <span class="badge <?php echo $deviceOnline ? 'bg-success' : 'bg-danger'; ?>"><?php echo $deviceOnline ? 'Online' : 'Offline'; ?></span>
                        <span class="badge text-bg-light"><?php echo is_array($mySensors2) ? count($mySensors2) : 0; ?> Sensoren</span>
                      </div>
                    </div>
                    <div class="card-block dashboard-board-gauges" id="gaugescontainer<?php echo $singleRowmyboard->getId() ?>">
                <?php
                if ($mySensors2 == null) {
                  ?>
                    <div class='dashboard-empty-state'>Dieses Device hat noch keine Dashboard-Sensoren.</div>
                  <?php
                }
                if ($mySensors2 != null) {
                  foreach($mySensors2 as $singleRowMySensors) {
                    $mySensors = myFunctions::getLatestSensorData($singleRowMySensors['id']);
                    foreach($mySensors as $singleRowMySensorsLastTimeSeen) {
                      $sensortype = myFunctions::getSensorType($singleRowMySensors['typId']);
                      $sensConfig = myFunctions::getSensorConfig($singleRowMySensors['id']);
                      for ($i = 1; $i <= $sensConfig['NrOfUsedSensors']; $i++) {
                        $SensorChannelConfigSingle = myFunctions::getSensorChannelConfig($singleRowMySensors['id'], $i);
                        $currentChannelValue = $singleRowMySensorsLastTimeSeen['value' . $i] ?? null;
                        $numericCurrentChannelValue = is_numeric($currentChannelValue) ? (float)$currentChannelValue : null;
                        $unitValue = html_entity_decode((string)($sensortype['siUnitVal' . $i] ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        if (($mySensors != null) && (is_array($SensorChannelConfigSingle)) && ($SensorChannelConfigSingle['onDashboard'] == 1) && ($numericCurrentChannelValue !== null)) {
                          $boardGaugeCount++;
                          ?>
                          <div
                            id='gauge<?php echo $singleRowMySensors['id'] . "." . $i; ?>'
                            data-id="<?php echo $SensorChannelConfigSingle['DashboardOrderNr']; ?>"
                            class='ui-state-default dashboard-gauge-card gauge-container <?php if(!$deviceOnline) { echo "disabled"; } ?>'
                            data-gauge-key="<?php echo $singleRowMySensors['id'] . "." . $i; ?>"
                            data-sensor-id="<?php echo $singleRowMySensors['id']; ?>"
                            data-typ-id="<?php echo $singleRowMySensors['typId']; ?>"
                            data-typename="<?php echo htmlspecialchars($singleRowMySensors['typename'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-nr-of-sensors="<?php echo $singleRowMySensors['NrOfUsedSensors']; ?>"
                            data-channel-nr="<?php echo $SensorChannelConfigSingle['channelNr']; ?>"
                            data-sensor-display-name="<?php echo htmlspecialchars($singleRowMySensors['name'] . "." . $SensorChannelConfigSingle['name'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-chart-color="<?php echo htmlspecialchars($SensorChannelConfigSingle['ChartColor'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-board-id="<?php echo $singleRowmyboard->getId(); ?>"
                            data-board-name="<?php echo htmlspecialchars($singleRowmyboard->getName(), ENT_QUOTES, 'UTF-8'); ?>"
                            data-min="<?php echo $SensorChannelConfigSingle['GaugeMinValue']; ?>"
                            data-max="<?php echo $SensorChannelConfigSingle['GaugeMaxValue']; ?>"
                            data-value="<?php echo htmlspecialchars((string)$numericCurrentChannelValue, ENT_QUOTES, 'UTF-8'); ?>"
                            data-low-threshold="<?php echo $SensorChannelConfigSingle['GaugeRedAreaLowValue']; ?>"
                            data-low-color="<?php echo htmlspecialchars($SensorChannelConfigSingle['GaugeRedAreaLowColor'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-high-threshold="<?php echo $SensorChannelConfigSingle['GaugeRedAreaHighValue']; ?>"
                            data-high-color="<?php echo htmlspecialchars($SensorChannelConfigSingle['GaugeRedAreaHighColor'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-normal-color="<?php echo htmlspecialchars($SensorChannelConfigSingle['GaugeNormalAreaColor'], ENT_QUOTES, 'UTF-8'); ?>"
                          >
                            <div class="dashboard-gauge-top">
                              <div class="dashboard-gauge-headline">
                                <strong><?php echo htmlentities($SensorChannelConfigSingle['name']); ?></strong>
                                <span><?php echo htmlentities($singleRowMySensors['name']); ?></span>
                              </div>
                              <div class="dashboard-gauge-value">
                                <span class="dashboard-gauge-value-number"><?php echo htmlentities(number_format($numericCurrentChannelValue, 2, '.', '')); ?></span>
                                <span class="dashboard-gauge-value-unit"><?php echo htmlspecialchars($unitValue, ENT_QUOTES, 'UTF-8'); ?></span>
                              </div>
                            </div>
                            <div id='div_click_settings<?php echo $singleRowMySensors['id'] . "." . $i; ?>' class='multi-collapse' style='display:none; z-index: 100; position:absolute; top:12px; right:12px;'>
                              <i id='click_settings<?php echo $singleRowMySensors['id'] . "." . $i; ?>' class='bi bi-gear-fill' data-bs-toggle='modal' data-bs-target='#exampleModal' style='font-size:20px; color: #007bff'></i>
                            </div>
                            <div class="dashboard-gauge-visual"></div>
                            <div class="dashboard-gauge-meta">
                              <span><?php echo htmlentities($singleRowmyboard->getName()); ?></span>
                              <span><?php echo htmlspecialchars($unitValue, ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                          </div>
                          <?php
                        }
                      }
                    }
                  }
                }
                if ($boardGaugeCount === 0) {
                  ?>
                    <div class='dashboard-empty-state'>Aktuell gibt es fuer dieses Device keine numerischen Dashboard-Werte.</div>
                  <?php
                }
                ?>
                    </div>
                  </section>
        <?php
              }
            }
            ?>
          </div>
        </div>
      </div>

      <!-- Show temperatures as chart -->
      <!-- Note: for every board its own canvas. -->
      <div class="container tab-pane fade pl-0 pr-0" id="charts">
        <fieldset>
          <div id="chart-container">
            <div class="mb-4">
              <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                <strong>Temperaturen</strong>
                <button type="button" class="btn btn-sm btn-outline-secondary chart-show-all-devices" data-chart-key="temperature">Alle anzeigen</button>
                <button type="button" class="btn btn-sm btn-outline-secondary chart-hide-all-devices" data-chart-key="temperature">Alle ausblenden</button>
              </div>
              <div id="chart-device-filter-temperature" class="d-flex flex-wrap gap-3 mb-2"></div>
              <canvas id="mycanvas"></canvas>
            </div>
            <div class="mb-4">
              <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                <strong>ADC / Spannungen</strong>
                <button type="button" class="btn btn-sm btn-outline-secondary chart-show-all-devices" data-chart-key="adc">Alle anzeigen</button>
                <button type="button" class="btn btn-sm btn-outline-secondary chart-hide-all-devices" data-chart-key="adc">Alle ausblenden</button>
              </div>
              <div id="chart-device-filter-adc" class="d-flex flex-wrap gap-3 mb-2"></div>
              <canvas id="mycanvas2"></canvas>
            </div>
            <div class="mb-4">
              <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                <strong>Weitere Sensoren</strong>
                <button type="button" class="btn btn-sm btn-outline-secondary chart-show-all-devices" data-chart-key="other">Alle anzeigen</button>
                <button type="button" class="btn btn-sm btn-outline-secondary chart-hide-all-devices" data-chart-key="other">Alle ausblenden</button>
              </div>
              <div id="chart-device-filter-other" class="d-flex flex-wrap gap-3 mb-2"></div>
              <canvas id="mycanvas3"></canvas>
            </div>
          </div>
        </fieldset>
      </div>

      <!-- Show Board overview -->
      <div class="container tab-pane fade pl-0 pr-0" id="boards">
        <fieldset>
            <?php
            foreach($boardObjsArray as $singleBoardObj) {
              $transmissionPath = 0;
              $mySensors2 = myFunctions::getAllSensorsOfBoard($singleBoardObj->getId());
              $boardOnlineStatus = false;
              $mySensorIdList = null;
              if ($mySensors2 == null) {
                ?>
                  <div class='container mt-2'>
                    <span class='badge bg-danger mr-2' style='width: 55px;'>Offline</span>
                    <span class='control-label' style='padding-left: 5px'><?php echo $singleBoardObj->getName(); ?> (<?php echo $singleBoardObj->getMacAddress(); ?>)</span>
                  </div>
                <?php
                continue;
              }
              foreach($mySensors2 as $singleRowMySensors) {
                if ($mySensorIdList == null) {
                  $mySensorIdList = $singleRowMySensors['id'];
                } else {
                  $mySensorIdList = $mySensorIdList . ", " . $singleRowMySensors['id'];
                }
              }
              $mySensors = myFunctions::getLatestSensorData($mySensorIdList);
              foreach($mySensors as $singleRowMySensorsLastTimeSeen) {
                $transmissionPath = $singleRowMySensorsLastTimeSeen['transmissionPath'];
                $dbTimestamp = strtotime($singleRowMySensorsLastTimeSeen['reading_time']);

                // Show Online / Offline
                // if demoMode == true, then no limit.
                if ($varDemoMode) {
                  $maxTimeout = strtotime("-10 Years");
                } else {
                  $maxTimeout = strtotime("-" . $singleBoardObj->getOfflineDataTimer() . " Minutes"); // For show Online / Offline
                }

                if ($dbTimestamp > $maxTimeout) {
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
                  if ($transmissionPath == 1) {
                    ?>
                      <span class='badge bg-success mr-2' style='width: 55px;'>WiFi</span>
                    <?php
                  } elseif ($transmissionPath == 2) {
                    ?>
                      <span class='badge bg-success mr-2' style='width: 55px;'>Lora</span>
                    <?php
                  } else {
                    // no specific transmission path badge
                  }
                } else {
                ?>
                  <span class='badge bg-danger mr-2' style='width: 55px;'>Offline</span>
                <?php
                }
                ?>

                  <span class='control-label' style='padding-left: 5px'><?php echo $singleBoardObj->getName(); ?> (<?php echo $singleBoardObj->getMacAddress(); ?>)</span>
              </div>
            <?php
            }
            ?>
        </fieldset>
      </div>

      <!-- Show temperatures as table, only for admin -->
      <div class="container tab-pane fade pl-0 pr-0" id="debug">
        <div class="p-2" id="chart-container-debug">
          All Sensor Values as a table from ttnDataLoraBoatMonitor:
        </div>
        <?php
            include_once dirname(__DIR__) . "/app/Http/Webhooks/TTN/index.php"; // NOSONAR - Legacy Bootstrap, Autoload nicht verfügbar
          ?>
      </div>

      <!-- Show map -->
      <div class="container tab-pane fade pl-0" id="mapContainer">
        <div class="row mt-2">
          <div class="container">
            <?php include_once __DIR__ . "/openstreetmaps.php"; // NOSONAR - Legacy Template-Einbindung ?>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- Modal -->
  <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <!-- definition in "formSensors.php"-->
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
              //console.log("new order nr: " + index + ", old order nr: " + $( this ).attr("data-id"));
              $( this ).attr("data-id", index);
              var tempNumber = $( this ).attr("id").replace('gauge', '');
              var SensorIdChannel = tempNumber.split('.');
              $.ajax({
                method: "POST",
                url: "api/updateData.php",
                data: { update: "sensorOrderNumber",
                    channel: SensorIdChannel[1],
                    orderNumber: $( this ).attr("data-id"),
                    id: SensorIdChannel[0] }
              })
                .done(function( response ) {
                  if (!onceSensorOrderDone) {
                    g = document.createElement('div');
                    g.setAttribute("class", "alert alert-success alert-dismissible bg-opacity-70 bg-gray bg-opacity-20 shadow-risen");
                    g.setAttribute("role", "alert");
                    g.innerHTML = "Sensor order saved.<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
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
                    g.innerHTML = "Sensor order not saved.<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
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
        function showInternalTab(targetSelector) {
          if (!targetSelector || targetSelector.charAt(0) !== '#') {
            return;
          }

          $('#internalTabs .nav-link').removeClass('active').attr('aria-selected', 'false');
          $('.tab-content .tab-pane').removeClass('active show');

          $('#internalTabs a[href="' + targetSelector + '"]').addClass('active').attr('aria-selected', 'true');
          $(targetSelector).addClass('active show');

          if (targetSelector === '#mapContainer' && typeof initInternalMap === 'function') {
            window.setTimeout(function() {
              initInternalMap();
              if (typeof map !== 'undefined' && map && typeof map.invalidateSize === 'function') {
                window.setTimeout(function() {
                  map.invalidateSize();
                }, 150);
              }
            }, 50);
          }

          if (window.history && window.history.replaceState) {
            window.history.replaceState(null, '', targetSelector);
          }
        }

        $('#internalTabs').on('click', 'a[data-bs-toggle="tab"]', function(event) {
          event.preventDefault();
          showInternalTab($(this).attr('href'));
        });

        const currentHash = window.location.hash;
        if (currentHash && $('#internalTabs a[href="' + currentHash + '"]').length) {
          showInternalTab(currentHash);
        }

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
          url: 'formSensors.php?id=' + myArray[0] + '&channel=' + myArray[1] + '&modal=true'
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
    include_once dirname(__DIR__) . "/app/Presentation/Common/footer.inc.php"; // NOSONAR - Legacy Template-Einbindung
  ?>
