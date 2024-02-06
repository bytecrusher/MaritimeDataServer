<?php
  session_start();
  require_once("func/myFunctions.func.php");
  require_once("func/user.class.php");
  require_once("func/dbUpdateData.php");

  if (isset($_SESSION['userObj'])) {
    $currentUser = unserialize($_SESSION['userObj']);
  } else {
    $currentUser = false;
    header("Location: ./index.php");    // if user not logged in
    die();
  }

  if (!isset($_GET['modal'])) {
      include("common/header.inc.php");
  } else {
  ?>
    <div class='modal-header'>
    <h5 class='modal-title' id='exampleModalLabel'>Edit Sensor</h5>
    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
  <?php
  }

  if (isset($_POST['submit_formSensors'])) {
    if (!isset($_POST['modal'])) {
      try {
        $updateSensorReturn = dbUpdateData::updateSensor($_POST);
        $success_msg = "Board changes saved.";
        $newURL = "formBoards.php?id=" . $_POST['macAddress'];
        header('Location: '.$newURL);
        // ToDo: send error or success mgs to header.
        $_GET = $_POST;
      } catch (Exception $e) {
				$error_msg = "Error while saving changes to sensors.";
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
        $success_msg = "Board changes saved.";
        header("Location: internal.php");
        $_GET = $_POST;
        // ToDo: send error or success mgs to header.
      } catch (Exception $e) {
				$error_msg = "Error while saving changes to sensors.";
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
            <h1>Edit sensor</h1>
          </div>
        </div>
      <?php
    } elseif (isset($_GET['channel'])) {
      echo "Channel: " . $_GET['channel'];
    }
  }

  if (isset($_GET['modal'])) {
    $mySingleSensorChannelConfig=(myFunctions::getSensorChannelConfig($_GET['id'], $_GET['channel']));
  }
?>
</div>

<form method='post' action='formSensors.php#confSensors' class='form-horizontal mt-3'>
<div class="container main-container">
<div class="modal-body">
  
    <?php
      $SensorConfig=(myFunctions::getSensorConfig($_GET['id']));
      $SensorChannelConfig=(myFunctions::getSensorChannelsConfig($_GET['id']));
      $SensorType = myFunctions::getSensorType($SensorConfig['typId']);
      // Get sensor type data
      $AllSensorTypes =(myFunctions::getAllSensorType());
    ?>
          <div class="input-group mb-3">
            <span class="input-group-text" style="width: 50%">id</span>
            <input type='text' readonly class='col col-sm-4 form-control' style="background:#e9ecef" id='id' name='id' value='<?=$SensorConfig['id'];?>'>
          </div>

          <?php
          if (isset($_GET['modal'])) {
          ?>
            <div class='input-group mb-3' style='display:none;'>
              <span class='input-group-text' style='width: 50%'>modal</span>
              <input type='text' readonly class='col col-sm-4 form-control' style='background:#e9ecef' id='modal' name='modal' value='<?php echo($SensorConfig['id']) ?>'>
            </div>
          <?php
          }
          ?>

          <div class="input-group mb-3">
            <span class="input-group-text" style="width: 50%">ID Mac Address</span>
            <input type='text' class='col col-sm-4 form-control' style="background:#e9ecef" id='macAddress' name='macAddress' value='<?=$SensorConfig['boardId'];?>'>
          </div>

          <?php
            if ($SensorType['hasAddress'] == 1) {
              ?>
              <div class='input-group mb-3'>
                <span class='input-group-text' style='width: 50%'>Sensor Address (I2C)</span>
                <input type='text' class='col col-sm-4 form-control' id='sensorId' name='sensorId' value='<?php echo( $SensorConfig['sensorAddress']) ?>'>
              </div>

            <?php
            }
          ?>

          <div class="input-group mb-3">
            <span class="input-group-text" style="width: 50%">Name</span>
            <input type='text' class='col col-sm-4 form-control' id='name' name='name' value='<?=$SensorConfig['name'];?>'>
          </div>

          <div class="input-group mb-3">
            <span class="input-group-text" style="width: 50%">Description</span>
            <input type='text' class='col col-sm-4 form-control' id='description' name='description' value='<?=$SensorConfig['description'];?>'>
          </div>

          <div class="input-group mb-3">
            <span class="input-group-text" style="width: 50%">Typ</span>
            <select class='col col-sm-4 form-select' aria-label='Default select example' name='typId' <?php if (isset($_GET['modal'])) { echo("disabled"); } ?>>
          </div>

          <div class="input-group mb-3">
            <?php
            foreach ($AllSensorTypes as $singleRowSensorTyps) {
              if ($SensorConfig['typId'] == $singleRowSensorTyps['id']) {
                echo "<option selected value='" . $singleRowSensorTyps['id'] . "'>" . $singleRowSensorTyps['name'] . "</option>";
              } else {
                echo "<option value='" . $singleRowSensorTyps['id'] . "'>" . $singleRowSensorTyps['name'] . "</option>";
              }
            }
            ?>
            </select>
          </div>

          <div class="input-group mb-3">
            <span class="input-group-text" style="width: 50%">Location</span>
            <input type='text' class='col col-sm-4 form-control' id='locationOfMeasurement' name='locationOfMeasurement' value='<?=$SensorConfig['locationOfMeasurement'];?>'>
          </div>

          <?php
          if (!isset($_GET['modal'])) {
            foreach($SensorChannelConfig as $singleSensorChannelConfig) {
              //echo ($singleSensorChannelConfig['name']);
              ?>
              <fieldset class="border p-2 mb-3 mySensorsFieldset">
                <legend  class="float-none w-auto mySensorsFieldsetLegend">Value / Channel <?php echo $singleSensorChannelConfig['channelNr']; ?></legend>
                <div class='input-group mb-3'>
                  <span class='input-group-text' style='width: 50%'>Name</span>
                  <input type='text' class='col col-sm-4 form-control' id='nameValue<?php echo $singleSensorChannelConfig['channelNr'] ?>' name='nameValue<?php echo $singleSensorChannelConfig['channelNr'] ?>' value='<?php echo $singleSensorChannelConfig['name'] ?>'>
                </div>

                <div class='input-group mt-3'>
                  <span class='input-group-text' style='width: 50%'>on Dashboard</span>
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
            <legend  class="float-none w-auto mySensorsFieldsetLegend">Value / Channel <?php 
              if (isset($_GET['channel'])) {
                echo $_GET['channel'];
              } else {
                echo "1";
              }
              ?></legend >
            <div class='input-group mb-3'>
              <span class='input-group-text' style='width: 50%'>Channel</span>
              <input type='text' class='col col-sm-4 form-control' id='channel' name='channel' value='<?php echo $_GET['channel'] ?>'>
            </div>

            <div class='input-group mb-3'>
              <span class='input-group-text' style='width: 50%'>Name</span>
              <input type='text' class='col col-sm-4 form-control' id='nameValue' name='nameValue' value='<?php echo $mySingleSensorChannelConfig['name'] ?>'>
            </div>

            <fieldset class="border p-2 mySensorsFieldset">
              <legend  class="float-none w-auto mySensorsFieldsetLegend">Gauge</legend>
              <div class='input-group mb-3'>
                <span class='input-group-text' style='width: 50%'>MinValue</span>
                <input type='text' class='col col-sm-4 form-control' id='GaugeMinValue' name='GaugeMinValue' value='<?php echo $mySingleSensorChannelConfig['GaugeMinValue'] ?>'>
              </div>

              <div class='input-group'>
                <span class='input-group-text' style='width: 50%'>MaxValue</span>
                <input type='text' class='col col-sm-4 form-control' id='GaugeMaxValue' name='GaugeMaxValue' value='<?php echo $mySingleSensorChannelConfig['GaugeMaxValue'] ?>'>
              </div>

              <fieldset class="border p-2">
                <legend  class="float-none w-auto mySensorsFieldsetLegend">Red Area Low</legend>
                <div class='input-group mb-3'>
                  <span class='input-group-text' style='width: 50%'>Value</span>
                  <input type='text' class='col col-sm-4 form-control' id='GaugeRedAreaLowValue' name='GaugeRedAreaLowValue' value='<?php echo $mySingleSensorChannelConfig['GaugeRedAreaLowValue'] ?>'>
                </div>

                <div class='input-group'>
                  <span class='input-group-text' for="GaugeRedAreaLowColor" style='width: 50%'>Color</span>
                  <input type="color" class="form-control form-control-color" id="GaugeRedAreaLowColor" name="GaugeRedAreaLowColor" value="<?php echo $mySingleSensorChannelConfig['GaugeRedAreaLowColor'] ?>" title="Choose your color">
                </div>
              </fieldset>

              <fieldset class="border p-2">
                <legend  class="float-none w-auto mySensorsFieldsetLegend">Red Area High</legend>
                <div class='input-group mb-3'>
                  <span class='input-group-text' style='width: 50%'>Value</span>
                  <input type='text' class='col col-sm-4 form-control' id='GaugeRedAreaHighValue' name='GaugeRedAreaHighValue' value='<?php echo $mySingleSensorChannelConfig['GaugeRedAreaHighValue'] ?>'>
                </div>

                <div class='input-group'>
                  <span class='input-group-text' for="GaugeRedAreaHighColor" style='width: 50%'>Color</span>
                  <input type="color" class="form-control form-control-color" id="GaugeRedAreaHighColor" name="GaugeRedAreaHighColor" value="<?php echo $mySingleSensorChannelConfig['GaugeRedAreaHighColor'] ?>" title="Choose your color">
                </div>
              </fieldset>

              <div class='input-group mt-3'>
                <span class='input-group-text' for="GaugeNormalAreaColor" style='width: 50%'>Normal Area Color</span>
                <input type="color" class="form-control form-control-color" id="GaugeNormalAreaColor" name="GaugeNormalAreaColor" value="<?php echo $mySingleSensorChannelConfig['GaugeNormalAreaColor'] ?>" title="Choose your color">
              </div>
            </fieldset>

            <fieldset class="border p-2 mySensorsFieldset">
              <legend  class="float-none w-auto mySensorsFieldsetLegend">Charts</legend>
              <div class='input-group'>
                <span class='input-group-text' for="ChartColor" style='width: 50%'>Chart Color</span>
                <input type="color" class="form-control form-control-color" id="ChartColor" name="ChartColor" value="<?php echo $mySingleSensorChannelConfig['ChartColor'] ?>" title="Choose your color">
              </div>
            </fieldset>

            <div class='input-group mt-3 mb-3'>
              <span class='input-group-text' style='width: 50%'>show on Dashboard</span>
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
              <span class='input-group-text' style='width: 50%'>DashboardOrderNr</span>
              <input type='text' class='col col-sm-4 form-control' id='DashboardOrderNr' name='DashboardOrderNr' value='<?php echo $mySingleSensorChannelConfig['DashboardOrderNr'] ?>' <?php if (isset($_GET['modal'])) { echo("disabled"); } ?>>
            </div>
          </fieldset >

          <?php
          }
          
          if (!isset($_GET['modal'])) {
          ?>
          <div class="input-group mb-3">
            <span class="input-group-text" style="width: 50%">Nr of used Sensors</span>
            <label style="width: 50%;">
              <div class='form-control'>
                <input type='text' class='col col-sm-4 form-control' id='NrOfUsedSensors' name='NrOfUsedSensors' value='<?php echo $SensorConfig['NrOfUsedSensors'] ?>'>
              </div>
            </label>
          </div>

          <div class="input-group mb-3">
            <span class="input-group-text" style="width: 50%">on Dashboard</span>
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
    if (!isset($_GET['modal'])) {
    ?>
      <a class='col col-sm-2 m-1 btn btn-primary' href='formBoards.php?id=<?php echo $_GET['boardId'] ?>' role='button'>Back</a>
    <?php
    } else {
    ?>
      <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
    <?php
    }
  ?>
  <input type='submit' class='btn btn-primary' id='submit_formSensors' name='submit_formSensors' value='Save' >
</div>
</div>
</form>

<?php
  if (!isset($_GET['modal'])) {
    include("common/footer.inc.php");
  }
?>
