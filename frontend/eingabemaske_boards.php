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
    <li class="nav-item" role="presentation"><a class="nav-link active" href="#board" role="tab" data-toggle="tab">Board</a></li>
    <li class="nav-item" role="presentation"><a class="nav-link" href="#sensors" role="tab" data-toggle="tab">Sensors</a></li>
  </ul>

  <div class="tab-content">
    <div role="tabpanel" class="tab-pane active" id="board">
      <form method='post' action='settings.php#confBoards' class='form-horizontal col-sm-offset-2 col-sm-9'>
        <?php
          $statement = $pdo->prepare("SELECT * FROM boardconfig WHERE id=$_GET[id]");
          $result = $statement->execute();
          while($row = $statement->fetch()) {
        ?>
        <div class="row">
          <label for='id' class='col col-sm-5 col-form-label'>Id</label>
          <input type='text' readonly class='col col-sm-6 form-control' id='id' name='id' value='<?=$row['id'];?>'>
        </div>

        <div class="row">
          <label for='macaddress' class='col col-sm-5 col-form-label'>Macaddress</label>
          <input type='text' readonly class='col col-sm-6 form-control' id='macaddress' name='macaddress' value='<?=$row['macaddress'];?>'>
        </div>

        <div class="row"> <!-- Still needs to be implemented in DB -->
          <label for='boardtype' class='col col-sm-5 col-form-label'>Board Type</label>
          <input type='text' readonly class='col col-sm-6 form-control' id='boardtype' name='boardtype' value='tbd'>
        </div>

        <div class="row">
          <label for='name' class='col col-sm-5 col-form-label'>Name</label>
          <input type='text' class='col col-sm-6 form-control' id='name' name='name' value='<?=$row['name'];?>'>
        </div>

        <div class="row">
          <label for='location' class='col col-sm-5 col-form-label'>Location</label>
          <input type='text' class='col col-sm-6 form-control' id='location' name='location' value='<?=$row['location'];?>'>
        </div>

        <div class="row">
          <label for='description' class='col col-sm-5 col-form-label'>Description</label>
          <input type='text' class='col col-sm-6 form-control' id='description' name='description' value='<?=$row['description'];?>'>
        </div>

        <div class="row">
          <label for='performupdate' class='col col-sm-5 form-check-label'>Perform update</label>
          <div class="col col-sm-6 p-0">
          <?php
            if(isset($row['performupdate']) && $row['performupdate'] == '1') {
              echo"<input type='checkbox' id='performupdate' name='performupdate' value=" . $row['performupdate'] . " checked=" . $row['performupdate'] . ">";
            } else {
              echo"<input type='checkbox' id='performupdate' name='performupdate' value='1'>";
            }
          ?>
          </div>
        </div>
        <div class="row">
          <label for='firmwareversion' class='col col-sm-5 col-form-label'>Firmware version</label>
          <input type='text' class='col col-sm-6 form-control' readonly id='firmwareversion' name='firmwareversion' value='<?=$row['firmwareversion'];?>'>
        </div>
        <div class="row">
          <label for='alarmOnUnavailable' class='col-sm-5 form-check-label'>Alarm on unavailable </label>
          <div class="col col-sm-6 p-0">
            <?php
              if(isset($row['alarmOnUnavailable']) && $row['alarmOnUnavailable'] == '1') {
                echo "<input type='checkbox' id='alarmOnUnavailable' name='alarmOnUnavailable' value=" . $row['alarmOnUnavailable'] . " checked=" . $row['alarmOnUnavailable'] . ">";
              } else {
                echo "<input type='checkbox' id='alarmOnUnavailable' name='alarmOnUnavailable' value='1'>";
              }
            ?>
          </div>
        </div>
        <div class="row">
            <label for='updateDataTimer' class='col col-sm-5 col-form-label'>Update Data Timer (in Minutes)</label>
            <input type='text' class='col col-sm-6 form-control' id='updateDataTimer' name='updateDataTimer'  value='<?=$row['updateDataTimer'];?>' required>
        </div>
        <?php
          }
        ?>
        <div class='row'>
          <div class="col-sm-offset-2 col-sm-10">
            <a class='mr-2 btn btn-primary' href='settings.php#confBoards' role='button'>Back</a>
            <input type='submit' class="btn btn-primary" id='submit_eingabemaske_boards' name='submit_eingabemaske_boards' value='Save'>
          </div>
        </div>
      </form>
  </div>

    <div role="tabpanel" class="tab-pane" id="sensors">
      <table class="table">
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
        $mysensors = myFunctions::getAllSensorsOfBoard($varId);
        foreach($mysensors as $singleRowMysensor) {
          $Sensorname = myFunctions::getSensorType($singleRowMysensor['typid']);
          echo "<tr>";
          echo "<td>".$count++."</td>";
          echo "<td>". $singleRowMysensor['id'] . "</td>";
          echo "<td>". $Sensorname['name'] . ", " . $Sensorname['description'] . "</td>";
          echo "<td>". $singleRowMysensor['sensorAddress'] . "</td>";
          echo "<td>".$singleRowMysensor['name']."</td>";
          echo "<td>".$singleRowMysensor['description']."</td>";
          echo "<td>".$singleRowMysensor['messOrt']."</td>";
          if(isset($singleRowMysensor['onDashboard']) && $singleRowMysensor['onDashboard'] == '1')
          {
            echo "<td><input type='checkbox' id='onDashboard' name='onDashboard' value=" . $singleRowMysensor['onDashboard'] . " checked=" . $singleRowMysensor['onDashboard'] . " disabled></td>";
          }
          else
          {
            echo "<td><input type='checkbox' id='onDashboard' name='onDashboard' value='1' disabled></td>";
          }
          echo "<td><a href=\"eingabemaske_sensors.php?id=" . $singleRowMysensor['id'] . "&boardid=" . $_GET['id'] . "\"><i class='fas fa-pencil-alt'> </i></td>";
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
