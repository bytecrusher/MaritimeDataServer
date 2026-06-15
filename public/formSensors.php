<?php
  require_once dirname(__DIR__) . "/bootstrap/app.php";
  mds_start_session();
  require_once dirname(__DIR__) . "/app/Application/myFunctions.func.php";
  require_once dirname(__DIR__) . "/app/Application/SensorFormPageService.php";
  require_once dirname(__DIR__) . "/app/Domain/User/user.class.php";
  require_once dirname(__DIR__) . "/app/Application/dbUpdateData.php";

  $currentUser = SensorFormPageService::resolveCurrentUserFromSession();
  if (!$currentUser) {
    header("Location: ./index.php");    // if user not logged in
    die();
  }

  if (isset($_POST['submit_formSensors'])) {
    if (!mds_verify_csrf_token($_POST['csrf_token'] ?? '')) {
      $error_msg = mds_t('login.csrf');
      ?>
      <div class="alert alert-danger">
        <a href="#" class="close" data-bs-dismiss="alert" aria-label="close">&times;</a>
        <?php echo $error_msg; ?>
      </div>
      <?php
      die();
    }

    if (!myFunctions::canUserAccessSensor((int)$currentUser->getId(), (int)($_POST['id'] ?? 0))) {
      http_response_code(403);
      $error_msg = 'Access denied.';
      ?>
      <div class="alert alert-danger">
        <a href="#" class="close" data-bs-dismiss="alert" aria-label="close">&times;</a>
        <?php echo htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8'); ?>
      </div>
      <?php
      die();
    }

    if (!isset($_POST['modal'])) {
      try {
        $updateSensorReturn = dbUpdateData::updateSensor($_POST);
        $success_msg = mds_t('form.sensor.saved');
        $newURL = "formBoards.php?id=" . $_POST['macAddress'];
        header('Location: '.$newURL);
        // ToDo: send error or success mgs to header.
        $_GET = $_POST;
      } catch (Exception $e) {
				$error_msg = mds_t('form.sensor.save_error');
        ?>
        <div class="alert alert-danger">
          <a href="#" class="close" data-bs-dismiss="alert" aria-label="close">&times;</a>
          <?php echo $error_msg; ?>
        </div>
        <?php
        die();
			}
    } else {
      try {
        $updateSensorReturn = dbUpdateData::updateSensorChannelModal($_POST);
        $success_msg = mds_t('form.sensor.saved');
        header("Location: internal.php");
        $_GET = $_POST;
        // ToDo: send error or success mgs to header.
      } catch (Exception $e) {
				$error_msg = mds_t('form.sensor.save_error');
        ?>
        <div class="alert alert-danger">
			    <a href="#" class="close" data-bs-dismiss="alert" aria-label="close">&times;</a>
			    <?php echo $error_msg; ?>
		    </div>
        <?php
        die();
			}
    }

    if (!isset($_GET['modal'])) {
      ?>
        <div class='jumbotron' style='padding: 1rem 1rem; margin-bottom: 1rem;'>
          <div class='container'>
            <h1><?php echo htmlspecialchars(mds_t('form.sensor.edit'), ENT_QUOTES, 'UTF-8'); ?></h1>
          </div>
        </div>
      <?php
    } elseif (isset($_GET['channel'])) {
      echo "Channel: " . mds_h($_GET['channel']);
    }
  }

  if (!isset($_GET['modal'])) {
    include(dirname(__DIR__) . "/app/Presentation/Common/header.inc.php");
} else {
?>
  <div class='modal-header'>
  <h5 class='modal-title' id='exampleModalLabel'><?php echo htmlspecialchars(mds_t('form.sensor.edit'), ENT_QUOTES, 'UTF-8'); ?></h5>
  <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
<?php
}

  try {
    $sensorPageData = SensorFormPageService::buildPageData($currentUser, $_GET['id'] ?? 0, $_GET['channel'] ?? null, isset($_GET['modal']));
  } catch (Throwable $e) {
    http_response_code(403);
    echo "<div class='alert alert-danger'>Access denied.</div>";
    if (!isset($_GET['modal'])) {
      include(dirname(__DIR__) . "/app/Presentation/Common/footer.inc.php");
    }
    exit();
  }
  $SensorConfig = $sensorPageData['sensorConfig'];
  $SensorChannelConfig = $sensorPageData['sensorChannels'];
  $SensorType = $sensorPageData['sensorType'];
  $AllSensorTypes = $sensorPageData['allSensorTypes'];
  $mySingleSensorChannelConfig = $sensorPageData['singleChannelConfig'];
?>
</div>

<form method='post' action='formSensors.php#confSensors' class='form-horizontal mt-3'>
<?php echo mds_csrf_input(); ?>
<div class="container main-container">
<div class="modal-body">
  
    <?php ?>
          <div class="input-group mb-3">
            <span class="input-group-text" style="width: 50%">id</span>
            <input type='text' readonly class='col col-sm-4 form-control' style="background:#e9ecef" id='id' name='id' value='<?php echo mds_h($SensorConfig['id']); ?>'>
          </div>

          <?php
          if (isset($_GET['modal'])) {
          ?>
            <div class='input-group mb-3' style='display:none;'>
              <span class='input-group-text' style='width: 50%'>modal</span>
              <input type='text' readonly class='col col-sm-4 form-control' style='background:#e9ecef' id='modal' name='modal' value='<?php echo mds_h($SensorConfig['id']); ?>'>
            </div>
          <?php
          }
          ?>

          <div class="input-group mb-3">
            <span class="input-group-text" style="width: 50%"><?php echo htmlspecialchars(mds_t('settings.mac_address'), ENT_QUOTES, 'UTF-8'); ?></span>
            <input type='text' class='col col-sm-4 form-control' style="background:#e9ecef" id='macAddress' name='macAddress' value='<?php echo mds_h($SensorConfig['boardId']); ?>'>
          </div>

          <?php
            if ($SensorType['hasAddress'] == 1) {
              ?>
              <div class='input-group mb-3'>
                <span class='input-group-text' style='width: 50%'><?php echo htmlspecialchars(mds_t('form.sensor.sensor_address'), ENT_QUOTES, 'UTF-8'); ?> (I2C)</span>
                <input type='text' class='col col-sm-4 form-control' id='sensorId' name='sensorId' value='<?php echo mds_h($SensorConfig['sensorAddress'] ?? ''); ?>'>
              </div>

            <?php
            }
          ?>

          <div class="input-group mb-3">
            <span class="input-group-text" style="width: 50%"><?php echo htmlspecialchars(mds_t('common.name'), ENT_QUOTES, 'UTF-8'); ?></span>
            <input type='text' class='col col-sm-4 form-control' id='name' name='name' value='<?php echo mds_h($SensorConfig['name']); ?>'>
          </div>

          <div class="input-group mb-3">
            <span class="input-group-text" style="width: 50%"><?php echo htmlspecialchars(mds_t('common.description'), ENT_QUOTES, 'UTF-8'); ?></span>
            <input type='text' class='col col-sm-4 form-control' id='description' name='description' value='<?php echo mds_h($SensorConfig['description']); ?>'>
          </div>

          <div class="input-group mb-3">
            <span class="input-group-text" style="width: 50%"><?php echo htmlspecialchars(mds_t('form.sensor.sensor_type'), ENT_QUOTES, 'UTF-8'); ?></span>
            <select class='col col-sm-4 form-select' aria-label='Default select example' name='typId' <?php if (isset($_GET['modal'])) { echo("disabled"); } ?>>
          </div>

          <div class="input-group mb-3">
            <?php
            foreach ($AllSensorTypes as $singleRowSensorTyps) {
              if ($SensorConfig['typId'] == $singleRowSensorTyps['id']) {
                echo "<option selected value='" . (int)$singleRowSensorTyps['id'] . "'>" . mds_h($singleRowSensorTyps['name']) . "</option>";
              } else {
                echo "<option value='" . (int)$singleRowSensorTyps['id'] . "'>" . mds_h($singleRowSensorTyps['name']) . "</option>";
              }
            }
            ?>
            </select>
          </div>

          <div class="input-group mb-3">
            <span class="input-group-text" style="width: 50%"><?php echo htmlspecialchars(mds_t('common.location'), ENT_QUOTES, 'UTF-8'); ?></span>
            <input type='text' class='col col-sm-4 form-control' id='locationOfMeasurement' name='locationOfMeasurement' value='<?php echo mds_h($SensorConfig['locationOfMeasurement']); ?>'>
          </div>

          <?php
          if (!isset($_GET['modal'])) {
            foreach($SensorChannelConfig as $singleSensorChannelConfig) {
              //echo ($singleSensorChannelConfig['name']);
              ?>
              <fieldset class="border p-2 mb-3 mySensorsFieldset">
                <legend  class="float-none w-auto mySensorsFieldsetLegend"><?php echo htmlspecialchars(mds_t('form.sensor.value'), ENT_QUOTES, 'UTF-8'); ?> / <?php echo htmlspecialchars(mds_t('form.sensor.channel'), ENT_QUOTES, 'UTF-8'); ?> <?php echo $singleSensorChannelConfig['channelNr']; ?></legend>
                <div class='input-group mb-3'>
                  <span class='input-group-text' style='width: 50%'><?php echo htmlspecialchars(mds_t('common.name'), ENT_QUOTES, 'UTF-8'); ?></span>
                  <input type='text' class='col col-sm-4 form-control' id='nameValue<?php echo (int)$singleSensorChannelConfig['channelNr']; ?>' name='nameValue<?php echo (int)$singleSensorChannelConfig['channelNr']; ?>' value='<?php echo mds_h($singleSensorChannelConfig['name']); ?>'>
                </div>

                <div class='input-group mt-3'>
                  <span class='input-group-text' style='width: 50%'><?php echo htmlspecialchars(mds_t('form.sensor.show_dashboard'), ENT_QUOTES, 'UTF-8'); ?></span>
                  <label style="width: 50%;">
                    <div class="form-control">
                      <?php
                      if(isset($singleSensorChannelConfig['onDashboard']) && $singleSensorChannelConfig['onDashboard'] == '1') {
                      ?>
                        <input class='col col-sm-4 form-check-input' type='checkbox' id='Value<?php echo $singleSensorChannelConfig['channelNr'] ?>onDashboard' name='Value<?php echo $singleSensorChannelConfig['channelNr'] ?>onDashboard' value='1' checked='1'>
                      <?php
                      } else {
                      ?>
                        <input class='col col-sm-4 form-check-input' type='checkbox' id='Value<?php echo $singleSensorChannelConfig['channelNr'] ?>onDashboard' name='Value<?php echo $singleSensorChannelConfig['channelNr'] ?>onDashboard' value='1'>
                      <?php
                      }
                      ?>
                    </div>
                  </label>
                </div>
              </fieldset>
          <?php
            }
          } else {
          ?>

          <fieldset class="border p-2 mb-3 mySensorsFieldset" >
            <legend  class="float-none w-auto mySensorsFieldsetLegend"><?php echo htmlspecialchars(mds_t('form.sensor.value'), ENT_QUOTES, 'UTF-8'); ?> / <?php echo htmlspecialchars(mds_t('form.sensor.channel'), ENT_QUOTES, 'UTF-8'); ?> <?php
              if (isset($_GET['channel'])) {
                echo $_GET['channel'];
              } else {
                echo "1";
              }
              ?></legend >
            <div class='input-group mb-3'>
              <span class='input-group-text' style='width: 50%'><?php echo htmlspecialchars(mds_t('form.sensor.channel'), ENT_QUOTES, 'UTF-8'); ?></span>
              <input type='text' class='col col-sm-4 form-control' id='channel' name='channel' value='<?php echo mds_h($_GET['channel'] ?? ''); ?>'>
            </div>

            <div class='input-group mb-3'>
              <span class='input-group-text' style='width: 50%'><?php echo htmlspecialchars(mds_t('common.name'), ENT_QUOTES, 'UTF-8'); ?></span>
              <input type='text' class='col col-sm-4 form-control' id='nameValue' name='nameValue' value='<?php echo mds_h($mySingleSensorChannelConfig['name']); ?>'>
            </div>

            <fieldset class="border p-2 mySensorsFieldset">
              <legend  class="float-none w-auto mySensorsFieldsetLegend"><?php echo htmlspecialchars(mds_t('form.sensor.gauge'), ENT_QUOTES, 'UTF-8'); ?></legend>
              <div class='input-group mb-3'>
                <span class='input-group-text' style='width: 50%'><?php echo htmlspecialchars(mds_t('form.sensor.min_value'), ENT_QUOTES, 'UTF-8'); ?></span>
                <input type='number' class='col col-sm-4 form-control' id='GaugeMinValue' name='GaugeMinValue' size='7' step='0.1' value='<?php echo mds_h($mySingleSensorChannelConfig['GaugeMinValue']); ?>'>
              </div>

              <div class='input-group'>
                <span class='input-group-text' style='width: 50%'><?php echo htmlspecialchars(mds_t('form.sensor.max_value'), ENT_QUOTES, 'UTF-8'); ?></span>
                <input type='number' class='col col-sm-4 form-control' id='GaugeMaxValue' name='GaugeMaxValue' size='7' step='0.1' value='<?php echo mds_h($mySingleSensorChannelConfig['GaugeMaxValue']); ?>'>
              </div>

              <fieldset class="border p-2">
                <legend  class="float-none w-auto mySensorsFieldsetLegend"><?php echo htmlspecialchars(mds_t('form.sensor.red_area_low'), ENT_QUOTES, 'UTF-8'); ?></legend>
                <div class='input-group mb-3'>
                  <span class='input-group-text' style='width: 50%'><?php echo htmlspecialchars(mds_t('common.value'), ENT_QUOTES, 'UTF-8'); ?></span>
                  <input type='number' class='col col-sm-4 form-control' id='GaugeRedAreaLowValue' name='GaugeRedAreaLowValue' size='7' step='0.1' value='<?php echo mds_h($mySingleSensorChannelConfig['GaugeRedAreaLowValue']); ?>'>
                </div>

                <div class='input-group'>
                  <span class='input-group-text' for="GaugeRedAreaLowColor" style='width: 50%'><?php echo htmlspecialchars(mds_t('form.sensor.color'), ENT_QUOTES, 'UTF-8'); ?></span>
                  <input type="color" class="form-control form-control-color" id="GaugeRedAreaLowColor" name="GaugeRedAreaLowColor" value="<?php echo $mySingleSensorChannelConfig['GaugeRedAreaLowColor'] ?>" title="Choose your color">
                </div>
              </fieldset>

              <fieldset class="border p-2">
                <legend  class="float-none w-auto mySensorsFieldsetLegend"><?php echo htmlspecialchars(mds_t('form.sensor.red_area_high'), ENT_QUOTES, 'UTF-8'); ?></legend>
                <div class='input-group mb-3'>
                  <span class='input-group-text' style='width: 50%'><?php echo htmlspecialchars(mds_t('common.value'), ENT_QUOTES, 'UTF-8'); ?></span>
                  <input type='number' class='col col-sm-4 form-control' id='GaugeRedAreaHighValue' name='GaugeRedAreaHighValue' size='7' step='0.1' value='<?php echo mds_h($mySingleSensorChannelConfig['GaugeRedAreaHighValue']); ?>'>
                </div>

                <div class='input-group'>
                  <span class='input-group-text' for="GaugeRedAreaHighColor" style='width: 50%'><?php echo htmlspecialchars(mds_t('form.sensor.color'), ENT_QUOTES, 'UTF-8'); ?></span>
                  <input type="color" class="form-control form-control-color" id="GaugeRedAreaHighColor" name="GaugeRedAreaHighColor" value="<?php echo $mySingleSensorChannelConfig['GaugeRedAreaHighColor'] ?>" title="Choose your color">
                </div>
              </fieldset>

              <div class='input-group mt-3'>
                <span class='input-group-text' for="GaugeNormalAreaColor" style='width: 50%'><?php echo htmlspecialchars(mds_t('form.sensor.normal_area_color'), ENT_QUOTES, 'UTF-8'); ?></span>
                <input type="color" class="form-control form-control-color" id="GaugeNormalAreaColor" name="GaugeNormalAreaColor" value="<?php echo $mySingleSensorChannelConfig['GaugeNormalAreaColor'] ?>" title="Choose your color">
              </div>

              <div class='input-group mt-3'>
                <span class='input-group-text' style='width: 50%'><?php echo htmlspecialchars(mds_t('form.sensor.gauge_style'), ENT_QUOTES, 'UTF-8'); ?></span>
                <select class='form-select' id='GaugeStyle' name='GaugeStyle'>
                  <?php
                    $currentGaugeStyle = $mySingleSensorChannelConfig['GaugeStyle'] ?? 'classic';
                    $gaugeStyles = array(
                      'classic' => 'Classic',
                      'minimal' => 'Minimal',
                      'bold' => 'Bold',
                      'arc' => 'Arc',
                      'ring' => 'Ring',
                      'clock' => 'Clock',
                      'industrial' => 'Industrial'
                    );
                    foreach ($gaugeStyles as $gaugeStyleValue => $gaugeStyleLabel) {
                      $selected = $currentGaugeStyle === $gaugeStyleValue ? 'selected' : '';
                      echo "<option value='" . htmlspecialchars($gaugeStyleValue, ENT_QUOTES, 'UTF-8') . "' $selected>" . htmlspecialchars($gaugeStyleLabel, ENT_QUOTES, 'UTF-8') . "</option>";
                    }
                  ?>
                </select>
              </div>
            </fieldset>

            <fieldset class="border p-2 mySensorsFieldset">
              <legend class="float-none w-auto mySensorsFieldsetLegend"><?php echo htmlspecialchars(mds_t('form.sensor.critical_alert'), ENT_QUOTES, 'UTF-8'); ?></legend>
              <div class='input-group mt-3 mb-3'>
                <span class='input-group-text' style='width: 50%'><?php echo htmlspecialchars(mds_t('form.sensor.enable_email_alert'), ENT_QUOTES, 'UTF-8'); ?></span>
                <label style="width: 50%;">
                  <div class="form-control">
                    <?php $alertEnabled = isset($mySingleSensorChannelConfig['AlertEnabled']) && (int)$mySingleSensorChannelConfig['AlertEnabled'] === 1; ?>
                    <input class='col col-sm-4 form-check-input' type='checkbox' id='AlertEnabled' name='AlertEnabled' value='1' <?php if ($alertEnabled) { echo 'checked'; } ?>>
                  </div>
                </label>
              </div>

              <div class='input-group mb-3'>
                <span class='input-group-text' style='width: 50%'><?php echo htmlspecialchars(mds_t('form.sensor.alert_below'), ENT_QUOTES, 'UTF-8'); ?></span>
                <input type='number' class='col col-sm-4 form-control' id='AlertLowValue' name='AlertLowValue' size='7' step='0.1' value='<?php echo htmlspecialchars((string)($mySingleSensorChannelConfig['AlertLowValue'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>'>
              </div>

              <div class='input-group mb-3'>
                <span class='input-group-text' style='width: 50%'><?php echo htmlspecialchars(mds_t('form.sensor.alert_above'), ENT_QUOTES, 'UTF-8'); ?></span>
                <input type='number' class='col col-sm-4 form-control' id='AlertHighValue' name='AlertHighValue' size='7' step='0.1' value='<?php echo htmlspecialchars((string)($mySingleSensorChannelConfig['AlertHighValue'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>'>
              </div>
            </fieldset>

            <fieldset class="border p-2 mySensorsFieldset">
              <legend  class="float-none w-auto mySensorsFieldsetLegend"><?php echo htmlspecialchars(mds_t('internal.charts'), ENT_QUOTES, 'UTF-8'); ?></legend>
              <div class='input-group'>
                <span class='input-group-text' for="ChartColor" style='width: 50%'><?php echo htmlspecialchars(mds_t('form.sensor.chart_color'), ENT_QUOTES, 'UTF-8'); ?></span>
                <input type="color" class="form-control form-control-color" id="ChartColor" name="ChartColor" value="<?php echo $mySingleSensorChannelConfig['ChartColor'] ?>" title="Choose your color">
              </div>
            </fieldset>

            <div class='input-group mt-3 mb-3'>
              <span class='input-group-text' style='width: 50%'><?php echo htmlspecialchars(mds_t('form.sensor.show_dashboard'), ENT_QUOTES, 'UTF-8'); ?></span>
              <label style="width: 50%;">
                <div class="form-control">
                  <?php
                    if(isset($mySingleSensorChannelConfig['onDashboard']) && $mySingleSensorChannelConfig['onDashboard'] == '1') {
                    ?>
                      <input class='col col-sm-4 form-check-input' type='checkbox' id='onDashboard' name='onDashboard' value='1' checked='1'>
                    <?php
                    } else {
                    ?>
                      <input class='col col-sm-4 form-check-input' type='checkbox' id='onDashboard' name='onDashboard' value='1'>
                    <?php
                    }
                  ?>
                </div>
              </label>
            </div>

            <div class='input-group mt-3 mb-3'>
              <span class='input-group-text' style='width: 50%'><?php echo htmlspecialchars(mds_t('form.sensor.dashboard_order'), ENT_QUOTES, 'UTF-8'); ?></span>
              <input type='text' class='col col-sm-4 form-control' id='DashboardOrderNr' name='DashboardOrderNr' value='<?php echo mds_h($mySingleSensorChannelConfig['DashboardOrderNr']); ?>' <?php if (isset($_GET['modal'])) { echo("disabled"); } ?>>
            </div>
          </fieldset >

          <?php
          }
          
          if (!isset($_GET['modal'])) {
          ?>
          <div class="input-group mb-3">
            <span class="input-group-text" style="width: 50%"><?php echo htmlspecialchars(mds_t('form.sensor.used_sensors'), ENT_QUOTES, 'UTF-8'); ?></span>
            <label style="width: 50%;">
              <div class='form-control'>
                <input type='text' class='col col-sm-4 form-control' id='NrOfUsedSensors' name='NrOfUsedSensors' value='<?php echo mds_h($SensorConfig['NrOfUsedSensors']); ?>'>
              </div>
            </label>
          </div>

          <div class="input-group mb-3">
            <span class="input-group-text" style="width: 50%"><?php echo htmlspecialchars(mds_t('form.sensor.show_dashboard'), ENT_QUOTES, 'UTF-8'); ?></span>
            <label style="width: 50%;">
              <div class='form-control'>
              <?php
                if(isset($SensorConfig['onDashboard']) && $SensorConfig['onDashboard'] == '1')
                {
                ?>
                  <input type='checkbox' id='onDashboard' name='onDashboard' value=<?php echo $SensorConfig['onDashboard'] ?> checked=<?php echo $SensorConfig['onDashboard'] ?>>
                <?php
                }
                else
                {
                ?>
                  <input type='checkbox' id='onDashboard' name='onDashboard' value='1'>
                <?php
                }
              ?>
              </div>
            </label>
          </div>
          <?php
          }
          ?>
  </div>
  <div class="modal-footer">
  <?php
    $backBoardId = $_GET['boardId'] ?? ($SensorConfig['boardId'] ?? null);
    if (!isset($_GET['modal'])) {
    ?>
      <?php if ($backBoardId !== null) { ?>
        <a class='col col-sm-2 m-1 btn btn-primary' href='formBoards.php?id=<?php echo (int)$backBoardId ?>' role='button'><?php echo htmlspecialchars(mds_t('common.back'), ENT_QUOTES, 'UTF-8'); ?></a>
      <?php } ?>
    <?php
    } else {
    ?>
      <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'><?php echo htmlspecialchars(mds_t('common.close'), ENT_QUOTES, 'UTF-8'); ?></button>
    <?php
    }
  ?>
  <input type='submit' class='btn btn-primary' id='submit_formSensors' name='submit_formSensors' value='<?php echo htmlspecialchars(mds_t('common.save'), ENT_QUOTES, 'UTF-8'); ?>' >
</div>
</div>
</form>

<?php
  if (!isset($_GET['modal'])) {
    include(dirname(__DIR__) . "/app/Presentation/Common/footer.inc.php");
  }
?>
