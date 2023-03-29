<?php
  session_start();
  require_once("func/myFunctions.func.php");
  require_once("func/user.class.php");
  require_once("func/dbUpdateData.php");

  if (isset($_SESSION['userobj'])) {
    $currentUser = unserialize($_SESSION['userobj']);
  } else {
    $currentUser = false;
    header("Location: ./index.php");    // if user not loged in
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

  $user = user::check_user();

  if (isset($_POST['submit_inputmask_sensors'])) {
    if (!isset($_POST['modal'])) {
      try {
        $updateBoardReturn = dbUpdateData::updateSensor($_POST);
        $success_msg = "Board changes saved.";
        $newURL = "inputmask_boards.php?id=" . $_POST['macaddress'];
        header('Location: '.$newURL);
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
      
      //die();
    } else {
      try {
        $updateBoardReturn = dbUpdateData::updateSensorModal($_POST);
        $success_msg = "Board changes saved.";
        header("Location: internal.php");
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
      
      //die();
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

    /*if (!$updateBoardReturn) {
      $error_msg = "Error while saving changes to sensors.";
    } else {
      $success_msg = $updateBoardReturn;
    }*/
  }
?>
</div>

<form method='post' action='inputmask_sensors.php#confSensors' class='form-horizontal mt-3'>
<div class="container main-container">
<div class="modal-body">
  
    <?php
      $SensorConfig=(myFunctions::getSensorConfig($_GET['id']));
      $SensorType = myFunctions::getSensorType($SensorConfig['typid']);

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
            <span class="input-group-text" style="width: 50%">ID Macaddress</span>
            <input type='text' class='col col-sm-4 form-control' style="background:#e9ecef" id='macaddress' name='macaddress' value='<?=$SensorConfig['boardid'];?>'>
          </div>

          <?php
            if ($SensorType['hasAddress'] == 1) {
              ?>
              <div class='input-group mb-3'>
                <span class='input-group-text' style='width: 50%'>Sensor Address (I2C)</span>
                <input type='text' class='col col-sm-4 form-control' id='sensorid' name='sensorid' value='<?php echo( $SensorConfig['sensorAddress']) ?>'>
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
            <select class='col col-sm-4 form-select' aria-label='Default select example' name='typid' <?php if (isset($_GET['modal'])) { echo("disabled"); } ?>>
          </div>

          <div class="input-group mb-3">
            <?php
            foreach ($AllSensorTypes as $singleRowsensortyps) {
              if ($SensorConfig['typid'] == $singleRowsensortyps['id']) {
                echo "<option selected value='" . $singleRowsensortyps['id'] . "'>" . $singleRowsensortyps['name'] . "</option>";
              } else {
                echo "<option value='" . $singleRowsensortyps['id'] . "'>" . $singleRowsensortyps['name'] . "</option>";
              }
            }
            ?>
            </select>
          </div>

          <div class="input-group mb-3">
            <span class="input-group-text" style="width: 50%">Location</span>
            <input type='text' class='col col-sm-4 form-control' id='locationOfMeasurement' name='locationOfMeasurement' value='<?=$SensorConfig['locationOfMeasurement'];?>'>
          </div>

          <fieldset class="border p-2 mb-3 mysensorsfieldset">
            <legend  class="float-none w-auto mysensorsfieldsetlegend">Value / Channel <?php 
            if (isset($_GET['channel'])) {
              echo $_GET['channel'];
            } else {
              echo "1";
            }
            ?></legend>
            <?php
            if (!isset($_GET['channel'])) {
            ?>

            <div class='input-group mb-3'>
              <span class='input-group-text' style='width: 50%'>Name</span>
              <input type='text' class='col col-sm-4 form-control' id='nameValue1' name='nameValue1' value='<?php echo $SensorConfig['nameValue1'] ?>'>
            </div>

            <div class='input-group mt-3'>
              <span class='input-group-text' style='width: 50%'>on Dashboard</span>
              <div class="form-control">
                <?php
                  //if(isset($row['onDashboard']) && $row['onDashboard'] == '1') {
                    if(isset($SensorConfig['Value1onDashboard']) && $SensorConfig['Value1onDashboard'] == '1') {
                      ?>
                      <input class='col col-sm-4 form-check-input' type='checkbox' id='Value1onDashboard' name='Value1onDashboard' value='1' checked='1'>
                    <?php
                  } else {
                    ?>
                      <input class='col col-sm-4 form-check-input' type='checkbox' id='Value1onDashboard' name='Value1onDashboard' value='1'>
                    <?php
                  }
                ?>
              </div>
            </div>
          </fieldset>

          <fieldset class="border p-2 mb-3 mysensorsfieldset">
          <legend  class="float-none w-auto mysensorsfieldsetlegend">Value / Channel 2</legend>
            <div class='input-group mb-3'>
              <span class='input-group-text' style='width: 50%'>Name</span>
              <input type='text' class='col col-sm-4 form-control' id='nameValue2' name='nameValue2' value='<?php echo $SensorConfig['nameValue2'] ?>'>
            </div>

            <div class='input-group mt-3'>
              <span class='input-group-text' style='width: 50%'>on Dashboard</span>
              <div class="form-control">
                <?php
                  //if(isset($row['onDashboard']) && $row['onDashboard'] == '1') {
                    if(isset($SensorConfig['Value2onDashboard']) && $SensorConfig['Value2onDashboard'] == '1') {
                      ?>
                      <input class='col col-sm-4 form-check-input' type='checkbox' id='Value2onDashboard' name='Value2onDashboard' value='1' checked='1'>
                    <?php
                  } else {
                    ?>
                      <input class='col col-sm-4 form-check-input' type='checkbox' id='Value2onDashboard' name='Value2onDashboard' value='1'>
                    <?php
                  }
                ?>
              </div>
            </div>
          </fieldset>

          <fieldset class="border p-2 mb-3 mysensorsfieldset">
          <legend  class="float-none w-auto mysensorsfieldsetlegend">Value / Channel 3</legend>
            <div class='input-group mb-3'>
              <span class='input-group-text' style='width: 50%'>Name</span>
              <input type='text' class='col col-sm-4 form-control' id='nameValue3' name='nameValue3' value='<?php echo $SensorConfig['nameValue3'] ?>'>
            </div>

            <div class='input-group mt-3'>
              <span class='input-group-text' style='width: 50%'>on Dashboard</span>
              <div class="form-control">
                <?php
                  //if(isset($row['onDashboard']) && $row['onDashboard'] == '1') {
                    if(isset($SensorConfig['Value3onDashboard']) && $SensorConfig['Value3onDashboard'] == '1') {
                      ?>
                      <input class='col col-sm-4 form-check-input' type='checkbox' id='Value3onDashboard' name='Value3onDashboard' value='1' checked='1'>
                    <?php
                  } else {
                    ?>
                      <input class='col col-sm-4 form-check-input' type='checkbox' id='Value3onDashboard' name='Value3onDashboard' value='1'>
                    <?php
                  }
                ?>
              </div>
            </div>
          </fieldset>

          <fieldset class="border p-2 mb-3 mysensorsfieldset">
          <legend  class="float-none w-auto mysensorsfieldsetlegend">Value / Channel 4</legend>
            <div class='input-group mb-3'>
              <span class='input-group-text' style='width: 50%'>Name</span>
              <input type='text' class='col col-sm-4 form-control' id='nameValue4' name='nameValue4' value='<?php echo $SensorConfig['nameValue4'] ?>'>
            </div>

            <div class='input-group mt-3'>
              <span class='input-group-text' style='width: 50%'>on Dashboard</span>
              <div class="form-control">
                <?php
                  //if(isset($row['onDashboard']) && $row['onDashboard'] == '1') {
                    if(isset($SensorConfig['Value4onDashboard']) && $SensorConfig['Value4onDashboard'] == '1') {
                      ?>
                      <input class='col col-sm-4 form-check-input' type='checkbox' id='Value4onDashboard' name='Value4onDashboard' value='1' checked='1'>
                    <?php
                  } else {
                    ?>
                      <input class='col col-sm-4 form-check-input' type='checkbox' id='Value4onDashboard' name='Value4onDashboard' value='1'>
                    <?php
                  }
                ?>
              </div>
            </div>
          </fieldset>
                  
          <div class='input-group mb-3'>
            <span class='input-group-text' style='width: 50%'>Nr of Used Sensors</span>
            <input type='text' class='col col-sm-4 form-control' id='NrOfUsedSensors' name='NrOfUsedSensors' value='<?php echo $SensorConfig['NrOfUsedSensors'] ?>'>
          </div>
          <?php
          } else {
          ?>

            <div class='input-group mb-3'>
              <span class='input-group-text' style='width: 50%'>Channel</span>
              <input type='text' class='col col-sm-4 form-control' id='channel' name='channel' value='<?php echo $_GET['channel'] ?>'>
            </div>

          <div class='input-group mb-3'>
            <span class='input-group-text' style='width: 50%'>Name</span>
            <input type='text' class='col col-sm-4 form-control' id='nameValue<?php echo $_GET['channel'] ?>' name='nameValue<?php echo $_GET['channel'] ?>' value='<?php echo $SensorConfig['nameValue' . $_GET['channel'] ] ?>'>
          </div>

          <fieldset class="border p-2 mysensorsfieldset">
            <legend  class="float-none w-auto mysensorsfieldsetlegend">Gauge</legend>
            <div class='input-group mb-3'>
              <span class='input-group-text' style='width: 50%'>MinValue</span>
              <input type='text' class='col col-sm-4 form-control' id='Value<?php echo $_GET['channel'] ?>GaugeMinValue' name='Value<?php echo $_GET['channel'] ?>GaugeMinValue' value='<?php echo $SensorConfig['Value' . $_GET['channel'] . 'GaugeMinValue'] ?>'>
            </div>

            <div class='input-group'>
              <span class='input-group-text' style='width: 50%'>MaxValue</span>
              <input type='text' class='col col-sm-4 form-control' id='Value<?php echo $_GET['channel'] ?>GaugeMaxValue' name='Value<?php echo $_GET['channel'] ?>GaugeMaxValue' value='<?php echo $SensorConfig['Value' . $_GET['channel'] . 'GaugeMaxValue'] ?>'>
            </div>

            <fieldset class="border p-2">
              <legend  class="float-none w-auto mysensorsfieldsetlegend">Red Area Low</legend>
              <div class='input-group mb-3'>
                <span class='input-group-text' style='width: 50%'>Value</span>
                <input type='text' class='col col-sm-4 form-control' id='Value<?php echo $_GET['channel'] ?>GaugeRedAreaLowValue' name='Value<?php echo $_GET['channel'] ?>GaugeRedAreaLowValue' value='<?php echo $SensorConfig['Value' . $_GET['channel'] . 'GaugeRedAreaLowValue'] ?>'>
              </div>

              <div class='input-group'>
                <span class='input-group-text' style='width: 50%'>Color</span>
                <input type='text' class='col col-sm-4 form-control' id='Value<?php echo $_GET['channel'] ?>GaugeRedAreaLowColor' name='Value<?php echo $_GET['channel'] ?>GaugeRedAreaLowColor' value='<?php echo $SensorConfig['Value' . $_GET['channel'] . 'GaugeRedAreaLowColor'] ?>'>
              </div>
            </fieldset>

            <fieldset class="border p-2">
              <legend  class="float-none w-auto mysensorsfieldsetlegend">Red Area High</legend>
              <div class='input-group mb-3'>
                <span class='input-group-text' style='width: 50%'>Value</span>
                <input type='text' class='col col-sm-4 form-control' id='Value<?php echo $_GET['channel'] ?>GaugeRedAreaHighValue' name='Value<?php echo $_GET['channel'] ?>GaugeRedAreaHighValue' value='<?php echo $SensorConfig['Value' . $_GET['channel'] . 'GaugeRedAreaHighValue'] ?>'>
              </div>

              <div class='input-group'>
                <span class='input-group-text' style='width: 50%'>Color</span>
                <input type='text' class='col col-sm-4 form-control' id='Value<?php echo $_GET['channel'] ?>GaugeRedAreaHighColor' name='Value<?php echo $_GET['channel'] ?>GaugeRedAreaHighColor' value='<?php echo $SensorConfig['Value' . $_GET['channel'] . 'GaugeRedAreaHighColor'] ?>'>
              </div>
            </fieldset>

            <div class='input-group mt-3'>
              <span class='input-group-text' style='width: 50%'>GaugeNormalAreaColor</span>
              <input type='text' class='col col-sm-4 form-control' id='Value<?php echo $_GET['channel'] ?>GaugeNormalAreaColor' name='Value<?php echo $_GET['channel'] ?>GaugeNormalAreaColor' value='<?php echo $SensorConfig['Value' . $_GET['channel'] . 'GaugeNormalAreaColor'] ?>'>
            </div>
          </fieldset>

          <div class='input-group mt-3 mb-3'>
            <span class='input-group-text' style='width: 50%'>show on Dashboard</span>
            <div class="form-control">
              <?php
                //if(isset($row['onDashboard']) && $row['onDashboard'] == '1') {
                  if(isset($SensorConfig['Value' . $_GET['channel'] . 'onDashboard']) && $SensorConfig['Value' . $_GET['channel'] . 'onDashboard'] == '1') {
                    ?>
                    <input class='col col-sm-4 form-check-input' type='checkbox' id='Value<?php echo $_GET['channel'] ?>onDashboard' name='Value<?php echo $_GET['channel'] ?>onDashboard' value='1' checked='1'>
                  <?php
                } else {
                  ?>
                    <input class='col col-sm-4 form-check-input' type='checkbox' id='Value<?php echo $_GET['channel'] ?>onDashboard' name='Value<?php echo $_GET['channel'] ?>onDashboard' value='1'>
                  <?php
                }
              ?>
            </div>
          </div>

          <div class='input-group mt-3 mb-3'>
            <span class='input-group-text' style='width: 50%'>DashboardOrdnerNr</span>
            <input type='text' class='col col-sm-4 form-control' id='Value<?php echo $_GET['channel'] ?>DashboardOrdnerNr' name='Value<?php echo $_GET['channel'] ?>DashboardOrdnerNr' value='<?php echo $SensorConfig['Value' . $_GET['channel'] . 'DashboardOrdnerNr'] ?>' <?php if (isset($_GET['modal'])) { echo("disabled"); } ?>>
          </div>
                  
          <?php 
          }
          if (!isset($_GET['modal'])) {
          ?>
          <div class="input-group mb-3">
            <span class="input-group-text" style="width: 50%">on Dashboard</span>
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
          </div>
          <?php
          }
          ?>
  </div>
  <div class="modal-footer">
  <?php
    if (!isset($_GET['modal'])) {
    ?>
      <a class='col col-sm-2 m-1 btn btn-primary' href='inputmask_boards.php?id=<?php echo $_GET['boardid'] ?>' role='button'>Back</a>
    <?php
    } else {
    ?>
      <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Close</button>
    <?php
    }
  ?>
  <input type='submit' class='btn btn-primary' id='submit_inputmask_sensors' name='submit_inputmask_sensors' value='Save' >
</div>
</div>
</form>

<?php
  if (!isset($_GET['modal'])) {
    include("common/footer.inc.php");
  }
?>
