<?php
  session_start();
  require_once("func/dbConfig.func.php");
  require_once("func/myFunctions.func.php");
  require_once("func/user.class.php");
  include("common/header.inc.php");

  $user = user::check_user();
  $pdo = dbConfig::getInstance();
  $varId = $_GET['id'];
  $singleRowBoardId = myFunctions::getBoardById($varId);

?>

<div class="jumbotron" style="padding: 1rem 1rem; margin-bottom: 1rem;">
  <div class="container">

  <div class="row">
    <div class="col">
      <h1>Edit board "<?php echo $singleRowBoardId['name'] ?>"</h1>
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
        <?php
          $statement = $pdo->prepare("SELECT * FROM boardconfig WHERE id=$_GET[id]");
          $result = $statement->execute();
          while($row = $statement->fetch()) {
        ?>

        <?php // TODO replace the fix width in spam against some % ?>
        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%">id</span>
          <input type="text" readonly class="form-control" style="background:#e9ecef" id="id" name='id' value='<?=$row['id'];?>'>
        </div>

        <div class="input-group mb-3">
        <span class="input-group-text" style="width: 30%">Macaddress</span>
          <input type="text" readonly class="form-control" style="background:#e9ecef" id='macaddress' name='macaddress' value='<?=$row['macaddress'];?>'>
        </div>

        <div class="input-group mb-3"><!-- Still needs to be implemented in DB -->
          <span class="input-group-text" style="width: 30%">Board Type</span>
          <input type="text" readonly class="form-control" style="background:#e9ecef" id='boardtype' name='boardtype' value='tbd'>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%">Name</span>
          <input type="text"  class="form-control" id='name' name='name' value='<?=$row['name'];?>'>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%">Location</span>
          <input type="text" class="form-control" id='location' name='location' value='<?=$row['location'];?>'>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%">Description</span>
          <input type="text" class="form-control" id='description' name='description' value='<?=$row['description'];?>'>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%">TTN app id</span>
          <input type="text" class="form-control" id='ttn_app_id' name='ttn_app_id' value='<?=$row['ttn_app_id'];?>' pattern="^[_a-z0-9]{1,36}" maxlength="36" title="Höchstens 36 Zeichen sowie nur Kleinbuchstaben und Zahlen.">
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%">TTN dev id</span>
          <input type="text" class="form-control" id='ttn_dev_id' name='ttn_dev_id' value='<?=$row['ttn_dev_id'];?>' pattern="^[_a-z0-9]{1,36}" maxlength="36" title="Höchstens 36 Zeichen sowie nur Kleinbuchstaben und Zahlen.">
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%">Perform update</span>
          <div class="form-control">
          <?php
            if(isset($row['performupdate']) && $row['performupdate'] == '1') {
              echo"<input class='form-check-input' type='checkbox' id='performupdate' name='performupdate' value=" . $row['performupdate'] . " checked=" . $row['performupdate'] . ">";
            } else {
              echo"<input class='form-check-input' type='checkbox' id='performupdate' name='performupdate' value='1'>";
            }
          ?>
          </div>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%">Firmware version</span>
          <input type="text" class="form-control" id='firmwareversion' name='firmwareversion' value='<?=$row['firmwareversion'];?>'>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%; white-space: break-spaces">Alarm on unavailable</span>

          <!--div class="col col-sm-6 p-0"-->
          <div class="form-control">
            <?php
              if(isset($row['alarmOnUnavailable']) && $row['alarmOnUnavailable'] == '1') {
                echo "<input class='form-check-input' type='checkbox' id='alarmOnUnavailable' name='alarmOnUnavailable' value=" . $row['alarmOnUnavailable'] . " checked=" . $row['alarmOnUnavailable'] . ">";
              } else {
                echo "<input class='form-check-input' type='checkbox' id='alarmOnUnavailable' name='alarmOnUnavailable' value='1'>";
              }
            ?>
          </div>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%;">On Dashboard?</span>
          <!--label for='onDashboard' class='col-sm-5 form-check-label'>On Dashboard?</label-->
          <!--div class="col col-sm-6 p-0"-->
            <div class="form-control">
            <?php
              if(isset($row['onDashboard']) && $row['onDashboard'] == '1') {
                echo "<input class='form-check-input' type='checkbox' id='onDashboard' name='onDashboard' value=" . $row['onDashboard'] . " checked=" . $row['onDashboard'] . ">";
              } else {
                echo "<input class='form-check-input' type='checkbox' id='onDashboard' name='onDashboard' value='1'>";
              }
            ?>
            </div>
          <!--/div-->
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%; white-space: break-spaces">Update interval (Minutes)</span>
          <input type="text" class="form-control" id='updateDataTimer' name='updateDataTimer' value='<?=$row['updateDataTimer'];?>' required>
        </div>

        <?php
          }
        ?>
        <div class='row'>
          <div class="col-sm-offset-2 col-sm-10">
            <a class='mr-2 btn btn-primary' href='settings.php#confBoards' role='button'>Back</a>
            <input type='submit' class="btn btn-primary" id='submit_inputmaske_boards' name='submit_inputmaske_boards' value='Save'>
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
      // collect all Board IDs that belong to the user.
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
          echo "<td><a href=\"inputmaske_sensors.php?id=" . $singleRowMysensor['id'] . "&boardid=" . $_GET['id'] . "\"><i class='bi bi-pencil-fill'> </i></td>";
          echo "</tr>";
        }
      ?>
      </tbody></table>
    </div>
  </div>
</div>
<?php
  include("common/footer.inc.php");
?>
