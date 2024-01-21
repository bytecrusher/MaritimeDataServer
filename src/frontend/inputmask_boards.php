<?php
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

  $pdo = dbConfig::getInstance();
  $varId = $_GET['id'];
  $singleRowBoardId = myFunctions::getBoardById($varId);
  $boardobj = new board($_GET['id']);
?>

<div class="jumbotron">
  <div class="container">
    <div class="row">
      <div class="col">
        <h1>Edit board "<?php echo $boardobj->getName() ?>"</h1>
      </div>
      <div class="col"> <!-- Depending on the board type, select the appropriate image -->
          <img src="img/img_ESP32.png" class="rounded float-right" alt="img/img_ESP32.png" width="100" height="100">
      </div>
    </div>
  </div>
</div>

<div class="container main-container">
  <ul class="nav nav-tabs" role="tablist">
    <li class="nav-item" role="presentation"><a class="nav-link active" href="#board" role="tab" data-bs-toggle="tab">Board</a></li>
    <li class="nav-item" role="presentation"><a class="nav-link" href="#sensors" role="tab" data-bs-toggle="tab">Sensors</a></li>
  </ul>

  <div class="tab-content">
    <div role="tabpanel" class="tab-pane active" id="board">
      <form method='post' action='settings.php#confBoards' class='form-horizontal col-sm-offset-2 col-sm-9'>

      <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%">id</span>
          <input type="text" readonly class="form-control" style="background:#e9ecef" id="id" name='id' value='<?=$boardobj->getId();?>'>
        </div>

        <div class="input-group mb-3">
        <span class="input-group-text" style="width: 30%">Macaddress</span>
          <input type="text" readonly class="form-control" style="background:#e9ecef" id='macaddress' name='macaddress' value='<?=$boardobj->getMacAddress();?>'>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%">Board Type</span>
          <input type="text" readonly class="form-control" style="background:#e9ecef" id='boardtype' name='boardtype' value='<?=$boardobj->getBoardTypeName();?>'>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%">Name</span>
          <input type="text"  class="form-control" id='name' name='name' value='<?=$boardobj->getName();?>'>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%">Location</span>
          <input type="text" class="form-control" id='location' name='location' value='<?=$boardobj->getLocation();?>'>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%">Description</span>
          <input type="text" class="form-control" id='description' name='description' value='<?=$boardobj->getDescription();?>'>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%">TTN app id</span>
          <input type="text" class="form-control" id='ttn_app_id' name='ttn_app_id' value='<?=$boardobj->getTtnAppId();?>' pattern="^[_A-Za-z0-9\-]{1,36}" maxlength="36" title="Höchstens 36 Zeichen sowie nur Kleinbuchstaben und Zahlen." style="background:#e9ecef" readonly>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%">TTN dev id</span>
          <input type="text" class="form-control" id='ttn_dev_id' name='ttn_dev_id' value='<?=$boardobj->getTtnDevId();?>' pattern="^[_A-Za-z0-9\-]{1,36}" maxlength="36" title="Höchstens 36 Zeichen sowie nur Kleinbuchstaben und Zahlen." style="background:#e9ecef" readonly>
        </div>

        <!--div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%">Perform Firmware update</span>
          <div class="form-control">
          <?php
            //if(isset($boardobj->getPerformUpdate()) && $row['performupdate'] == '1') {
              if($boardobj->getPerformUpdate() == '1') {
              echo"<input class='form-check-input' type='checkbox' id='performupdate' name='performupdate' value=" . $boardobj->getPerformUpdate() . " checked=" . $boardobj->getPerformUpdate() . ">";
            } else {
              echo"<input class='form-check-input' type='checkbox' id='performupdate' name='performupdate' value='1'>";
            }
          ?>
          </div>
        </div-->

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%">Firmware version</span>
          <input type="text" readonly class="form-control" id='firmwareversion' name='firmwareversion' value='<?=$boardobj->getFirmwareVersion();?>' style="background:#e9ecef" readonly>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%; white-space: break-spaces">Alarm on unavailable</span>

          <label style="width: 70%;">
            <div class="form-control">
              <?php
                if($boardobj->getAlarmOnUnavailable() == '1') {
                  echo "<input class='form-check-input' type='checkbox' id='alarmOnUnavailable' name='alarmOnUnavailable' value=" . $boardobj->getAlarmOnUnavailable() . " checked=" . $boardobj->getAlarmOnUnavailable() . ">";
                } else {
                  echo "<input class='form-check-input' type='checkbox' id='alarmOnUnavailable' name='alarmOnUnavailable' value='1'>";
                }
              ?>
            </div>
          </label>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%;">On Dashboard?</span>
          <label style="width: 70%;">
            <div class="form-control">
            <?php
              if($boardobj->isOnDashboard() == '1') {
                echo "<input class='form-check-input' type='checkbox' id='onDashboard' name='onDashboard' value=" . $boardobj->isOnDashboard() . " checked=" . $boardobj->isOnDashboard() . ">";
              } else {
                echo "<input class='form-check-input' type='checkbox' id='onDashboard' name='onDashboard' value='1'>";
              }
            ?>
            </div>
            </label>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%; white-space: break-spaces">Update interval (Minutes)</span>
          <input type="text" class="form-control" id='updateDataTimer' name='updateDataTimer' value='<?=$boardobj->getUpdateDataTimer();?>' style="background:#e9ecef" readonly>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%; white-space: break-spaces">Mark as offline after (Minutes)</span>
          <input type="text" class="form-control" id='offlineDataTimer' name='offlineDataTimer' value='<?=$boardobj->getOfflineDataTimer();?>' title="After this timer, the board displays as offline.">
        </div>

        <?php
				if(($currentUser->getUserGroupAdmin() == 1) ) {
          $AllUsers =(myFunctions::getAllUsers());
				?>
        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%; white-space: break-spaces">owner User</span>
          <select class='col col-sm-4 form-select' aria-label='Default select example' name='ownerid'>
							<?php
              if ($boardobj->getOwnerUserId() == null) {
                echo "<option selected value=''></option>";
              } else {
                echo "<option value=''></option>";
              }
							foreach ($AllUsers as $singleRowUser) {
								if ($boardobj->getOwnerUserId() == $singleRowUser['id']) {
									echo "<option selected value='" . $singleRowUser['id'] . "'>" . $singleRowUser['id'] . " : " . $singleRowUser['email'] . "</option>";
								} else {
									echo "<option value='" . $singleRowUser['id'] . "'>" . $singleRowUser['id'] . " : " . $singleRowUser['email'] . "</option>";
								}
							}
							?></select>
        </div>
				<?php
				}
			?>

        <div class='row'>
          <div class="col-sm-offset-2 col-sm-8">
            <input type='submit' class="btn btn-danger" id='submit_inputmask_boards_remove' name='submit_inputmask_boards_remove' value='Remove Board' onclick="clicked(event)">
          </div>
          <div class="col-sm-offset-2 col-sm-4">
          <div class="float-end">
            <a class='mr-2 btn btn-primary' href='settings.php#confBoards' role='button'>Back</a>
            <input type='submit' class="btn btn-primary" id='submit_inputmask_boards' name='submit_inputmask_boards' value='Save'>
          </div>
          </div>
        </div>
      </form>
  </div>

    <div role="tabpanel" class="tab-pane" id="sensors">
      <table class="table table-bordered">
        <thead>
          <tr>
            <th>#</th><th>sensor id</th><th>Sensor Type</th><th>Sensor Address</th><th>Name</th><th>Description</th><th>Location</th><th>on Dashboard</th><th>edit</th>
          </tr>
        </thead>
      <tbody>
      <?php
      // collect all Sensor IDs that belong to the Board.
        $count = 1;
        $Sensorname = null;
        $mysensors = myFunctions::getAllSensorsOfBoardold($varId);
        foreach($mysensors as $singleRowMysensor) {
          $Sensorname = myFunctions::getSensorType($singleRowMysensor['typid']);
          echo "<tr>";
          echo "<td>".$count++."</td>";
          echo "<td>". $singleRowMysensor['id'] . "</td>";
          echo "<td>". $Sensorname['name'] . ", " . $Sensorname['description'] . "</td>";
          echo "<td>". $singleRowMysensor['sensorAddress'] . "</td>";
          echo "<td>".$singleRowMysensor['name']."</td>";
          echo "<td>".$singleRowMysensor['description']."</td>";
          echo "<td>".$singleRowMysensor['locationOfMeasurement']."</td>";
          if(isset($singleRowMysensor['onDashboard']) && $singleRowMysensor['onDashboard'] == '1')
          {
            echo "<td><input type='checkbox' id='onDashboard' name='onDashboard' value=" . $singleRowMysensor['onDashboard'] . " checked=" . $singleRowMysensor['onDashboard'] . " disabled></td>";
          }
          else
          {
            echo "<td><input type='checkbox' id='onDashboard' name='onDashboard' value='1' disabled></td>";
          }
          echo "<td><a href=\"inputmask_sensors.php?id=" . $singleRowMysensor['id'] . "&boardid=" . $_GET['id'] . "\"><i class='bi bi-pencil-fill'> </i></td>";
          echo "</tr>";
        }
      ?>
      </tbody></table>
    </div>
  </div>
</div>
<script>
  function clicked(e)
  {
    if(!confirm('Are you sure to remove your board?')) {
      e.preventDefault();
    }
  }
</script>
<?php
  include("common/footer.inc.php");
?>
