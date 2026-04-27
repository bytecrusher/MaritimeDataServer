<?php
  require_once dirname(__DIR__) . "/bootstrap/app.php";
  mds_start_session();
  require_once dirname(__DIR__) . "/app/Infrastructure/Database/dbConfig.func.php";
  require_once dirname(__DIR__) . "/app/Application/myFunctions.func.php";
  require_once dirname(__DIR__) . "/app/Application/BoardFormPageService.php";
  require_once dirname(__DIR__) . "/app/Domain/User/user.class.php";
  require_once dirname(__DIR__) . "/app/Domain/Board/board.class.php";

  $currentUser = BoardFormPageService::resolveCurrentUserFromSession();
  if (!$currentUser) {
    header("Location: ./index.php");    // if user not logged in
    die();
  }

  try {
    $pageData = BoardFormPageService::buildPageData($currentUser, $_GET['id'] ?? 0);
  } catch (Throwable $e) {
    header("Location: settings.php#confBoards");
    die();
  }

  $varId = $pageData['boardId'];
  $singleRowBoardId = $pageData['boardRow'];
  $boardObj = $pageData['boardObj'];
  $mySensors = $pageData['sensors'];
  $allUsers = $pageData['allUsers'];
  $isAdmin = $pageData['isAdmin'];

  include(dirname(__DIR__) . "/app/Presentation/Common/header.inc.php");
?>

<div class="jumbotron">
  <div class="container">
    <div class="row">
      <div class="col">
        <h1>Edit board "<?php echo $boardObj->getName() ?>"</h1>
      </div>
      <div class="col"> <!-- Depending on the board type, select the appropriate image -->
          <img src="<?php echo htmlspecialchars(mds_asset_path('img/img_ESP32.png'), ENT_QUOTES, 'UTF-8'); ?>" class="rounded float-right" alt="img/img_ESP32.png" width="100" height="100">
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
          <input type="text" readonly class="form-control" style="background:#e9ecef" id="id" name='id' value='<?=$boardObj->getId();?>'>
        </div>

        <div class="input-group mb-3">
        <span class="input-group-text" style="width: 30%">Mac address</span>
          <input type="text" readonly class="form-control" style="background:#e9ecef" id='macAddress' name='macAddress' value='<?=$boardObj->getMacAddress();?>'>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%">Board Type</span>
          <input type="text" readonly class="form-control" style="background:#e9ecef" id='boardType' name='boardType' value='<?=$boardObj->getBoardTypeName();?>'>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%">Name</span>
          <input type="text"  class="form-control" id='name' name='name' value='<?=$boardObj->getName();?>'>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%">Location</span>
          <input type="text" class="form-control" id='location' name='location' value='<?=$boardObj->getLocation();?>'>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%">Description</span>
          <input type="text" class="form-control" id='description' name='description' value='<?=$boardObj->getDescription();?>'>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%">TTN app id</span>
          <input type="text" class="form-control" id='ttnAppId' name='ttnAppId' value='<?=$boardObj->getTtnAppId();?>' pattern="^[_A-Za-z0-9\-]{1,36}" maxlength="36" title="Höchstens 36 Zeichen sowie nur Kleinbuchstaben und Zahlen." style="background:#e9ecef" readonly>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%">TTN dev id</span>
          <input type="text" class="form-control" id='ttnDevId' name='ttnDevId' value='<?=$boardObj->getTtnDevId();?>' pattern="^[_A-Za-z0-9\-]{1,36}" maxlength="36" title="Höchstens 36 Zeichen sowie nur Kleinbuchstaben und Zahlen." style="background:#e9ecef" readonly>
        </div>

        <!--div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%">Perform Firmware update</span>
          <div class="form-control">
          <?php
            //if(isset($boardObj->getPerformUpdate()) && $row['performUpdate'] == '1') {
              if($boardObj->getPerformUpdate() == '1') {
              echo"<input class='form-check-input' type='checkbox' id='performUpdate' name='performUpdate' value=" . $boardObj->getPerformUpdate() . " checked=" . $boardObj->getPerformUpdate() . ">";
            } else {
              echo"<input class='form-check-input' type='checkbox' id='performUpdate' name='performUpdate' value='1'>";
            }
          ?>
          </div>
        </div-->

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%">Firmware version</span>
          <input type="text" readonly class="form-control" id='firmwareversion' name='firmwareversion' value='<?=$boardObj->getFirmwareVersion();?>' style="background:#e9ecef" readonly>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%; white-space: break-spaces">Alarm on unavailable</span>

          <label style="width: 70%;">
            <div class="form-control">
              <?php
                if($boardObj->getAlarmOnUnavailable() == '1') {
                  echo "<input class='form-check-input' type='checkbox' id='alarmOnUnavailable' name='alarmOnUnavailable' value=" . $boardObj->getAlarmOnUnavailable() . " checked=" . $boardObj->getAlarmOnUnavailable() . ">";
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
              if($boardObj->isOnDashboard() == '1') {
                echo "<input class='form-check-input' type='checkbox' id='onDashboard' name='onDashboard' value=" . $boardObj->isOnDashboard() . " checked=" . $boardObj->isOnDashboard() . ">";
              } else {
                echo "<input class='form-check-input' type='checkbox' id='onDashboard' name='onDashboard' value='1'>";
              }
            ?>
            </div>
            </label>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%; white-space: break-spaces">Update interval (Minutes)</span>
          <input type="text" class="form-control" id='updateDataTimer' name='updateDataTimer' value='<?=$boardObj->getUpdateDataTimer();?>' style="background:#e9ecef" readonly>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%; white-space: break-spaces">Mark as offline after (Minutes)</span>
          <input type="text" class="form-control" id='offlineDataTimer' name='offlineDataTimer' value='<?=$boardObj->getOfflineDataTimer();?>' title="After this timer, the board displays as offline.">
        </div>

        <?php
				if($isAdmin) {
				?>
        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%; white-space: break-spaces">owner User</span>
          <select class='col col-sm-4 form-select' aria-label='Default select example' name='ownerId'>
							<?php
              if ($boardObj->getOwnerUserId() == null) {
                echo "<option selected value=''></option>";
              } else {
                echo "<option value=''></option>";
              }
							foreach ($allUsers as $singleRowUser) {
								if ($boardObj->getOwnerUserId() == $singleRowUser['id']) {
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
            <input type='submit' class="btn btn-danger" id='submit_formBoards_remove' name='submit_formBoards_remove' value='Remove Board' onclick="clicked(event)">
          </div>
          <div class="col-sm-offset-2 col-sm-4">
          <div class="float-end">
            <a class='mr-2 btn btn-primary' href='settings.php#confBoards' role='button'>Back</a>
            <input type='submit' class="btn btn-primary" id='submit_formBoards' name='submit_formBoards' value='Save'>
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
        foreach($mySensors as $singleRowMySensor) {
          $Sensorname = myFunctions::getSensorType($singleRowMySensor['typId']);
          echo "<tr>";
          echo "<td>".$count++."</td>";
          echo "<td>". $singleRowMySensor['id'] . "</td>";
          echo "<td>". $Sensorname['name'] . ", " . $Sensorname['description'] . "</td>";
          echo "<td>". $singleRowMySensor['sensorAddress'] . "</td>";
          echo "<td>".$singleRowMySensor['name']."</td>";
          echo "<td>".$singleRowMySensor['description']."</td>";
          echo "<td>".$singleRowMySensor['locationOfMeasurement']."</td>";
          if(isset($singleRowMySensor['onDashboard']) && $singleRowMySensor['onDashboard'] == '1')
          {
            echo "<td><input type='checkbox' id='onDashboard' name='onDashboard' value=" . $singleRowMySensor['onDashboard'] . " checked=" . $singleRowMySensor['onDashboard'] . " disabled></td>";
          }
          else
          {
            echo "<td><input type='checkbox' id='onDashboard' name='onDashboard' value='1' disabled></td>";
          }
          echo "<td><a href=\"formSensors.php?id=" . $singleRowMySensor['id'] . "&boardId=" . $_GET['id'] . "\"><i class='bi bi-pencil-fill'> </i></td>";
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
    if(!confirm('Are you sure to remove your board with ID:' + <?=$boardObj->getId();?> + '?')) {
      e.preventDefault();
    }
  }
</script>
<?php
  include(dirname(__DIR__) . "/app/Presentation/Common/footer.inc.php");
?>
