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

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && !mds_verify_csrf_token($_POST['csrf_token'] ?? '')) {
    header("Location: settings.php#confBoards");
    die();
  }

  try {
    $pageData = BoardFormPageService::buildPageData($currentUser, $_GET['id'] ?? 0);
  } catch (Throwable $e) {
    header("Location: settings.php#confBoards");
    die();
  }

  $success_msg = null;
  $error_msg = null;
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_sensor_group_delete'])) {
    $deletedSensorId = (int)($_POST['sensor_id'] ?? 0);
    try {
      $deleteCounts = BoardFormPageService::deleteSensorGroup(
        $currentUser,
        $pageData['boardId'],
        $deletedSensorId
      );
      $success_msg = mds_t('form.board.sensor_delete_success', array(
        $deletedSensorId,
        (int)($deleteCounts['sensorData'] ?? 0),
      ));
      $pageData = BoardFormPageService::buildPageData($currentUser, $pageData['boardId']);
    } catch (Throwable $e) {
      $error_msg = mds_t('form.board.sensor_delete_error');
    }
  } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_unused_sensor_cleanup'])) {
    try {
      $cleanupCounts = BoardFormPageService::cleanupUnusedSensors(
        $currentUser,
        $pageData['boardId'],
        SensorCleanupService::DEFAULT_STALE_DAYS
      );
      $success_msg = mds_t('form.board.sensor_cleanup_success', array(
        (int)($cleanupCounts['sensorConfig'] ?? 0),
        (int)($cleanupCounts['sensorData'] ?? 0),
      ));
      $pageData = BoardFormPageService::buildPageData($currentUser, $pageData['boardId']);
    } catch (Throwable $e) {
      $error_msg = mds_t('form.board.sensor_cleanup_error');
    }
  }

  $varId = $pageData['boardId'];
  $singleRowBoardId = $pageData['boardRow'];
  $boardObj = $pageData['boardObj'];
  $mySensors = $pageData['sensors'];
  $sensorOverview = $pageData['sensorOverview'];
  $sensorOverviewById = array();
  foreach ($sensorOverview as $sensorStatus) {
    $sensorOverviewById[(int)$sensorStatus['id']] = $sensorStatus;
  }
  $cleanupCandidateCount = (int)$pageData['cleanupCandidateCount'];
  $canCleanupSensors = !empty($pageData['canCleanupSensors']);
  $allUsers = $pageData['allUsers'];
  $isAdmin = $pageData['isAdmin'];

  include(dirname(__DIR__) . "/app/Presentation/Common/header.inc.php");
?>

<div class="jumbotron">
  <div class="container">
    <div class="row">
      <div class="col">
        <h1><?php echo htmlspecialchars(mds_t('form.board.edit', array($boardObj->getName())), ENT_QUOTES, 'UTF-8'); ?></h1>
      </div>
      <div class="col"> <!-- Depending on the board type, select the appropriate image -->
          <img src="<?php echo htmlspecialchars(mds_asset_path('img/img_ESP32.png'), ENT_QUOTES, 'UTF-8'); ?>" class="rounded float-right" alt="img/img_ESP32.png" width="100" height="100">
      </div>
    </div>
  </div>
</div>

<div class="container main-container">
  <?php if ($success_msg !== null) { ?>
    <div class="alert alert-success" role="alert"><?php echo mds_h($success_msg); ?></div>
  <?php } ?>
  <?php if ($error_msg !== null) { ?>
    <div class="alert alert-danger" role="alert"><?php echo mds_h($error_msg); ?></div>
  <?php } ?>

  <ul class="nav nav-tabs" role="tablist">
    <li class="nav-item" role="presentation"><a class="nav-link active" href="#board" role="tab" data-bs-toggle="tab"><?php echo htmlspecialchars(mds_t('common.board'), ENT_QUOTES, 'UTF-8'); ?></a></li>
    <li class="nav-item" role="presentation"><a class="nav-link" href="#sensors" role="tab" data-bs-toggle="tab"><?php echo htmlspecialchars(mds_t('common.sensors'), ENT_QUOTES, 'UTF-8'); ?></a></li>
  </ul>

  <div class="tab-content">
    <div role="tabpanel" class="tab-pane active" id="board">
      <form method='post' action='settings.php#confBoards' class='form-horizontal col-sm-offset-2 col-sm-9'>
      <?php echo mds_csrf_input(); ?>

      <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%">id</span>
          <input type="text" readonly class="form-control" style="background:#e9ecef" id="id" name='id' value='<?php echo mds_h($boardObj->getId()); ?>'>
        </div>

        <div class="input-group mb-3">
        <span class="input-group-text" style="width: 30%"><?php echo htmlspecialchars(mds_t('settings.mac_address'), ENT_QUOTES, 'UTF-8'); ?></span>
          <input type="text" readonly class="form-control" style="background:#e9ecef" id='macAddress' name='macAddress' value='<?php echo mds_h($boardObj->getMacAddress()); ?>'>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%"><?php echo htmlspecialchars(mds_t('form.board.type'), ENT_QUOTES, 'UTF-8'); ?></span>
          <input type="text" readonly class="form-control" style="background:#e9ecef" id='boardType' name='boardType' value='<?php echo mds_h($boardObj->getBoardTypeName()); ?>'>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%"><?php echo htmlspecialchars(mds_t('common.name'), ENT_QUOTES, 'UTF-8'); ?></span>
          <input type="text"  class="form-control" id='name' name='name' value='<?php echo mds_h($boardObj->getName()); ?>'>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%"><?php echo htmlspecialchars(mds_t('common.location'), ENT_QUOTES, 'UTF-8'); ?></span>
          <input type="text" class="form-control" id='location' name='location' value='<?php echo mds_h($boardObj->getLocation()); ?>'>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%"><?php echo htmlspecialchars(mds_t('common.description'), ENT_QUOTES, 'UTF-8'); ?></span>
          <input type="text" class="form-control" id='description' name='description' value='<?php echo mds_h($boardObj->getDescription()); ?>'>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%"><?php echo htmlspecialchars(mds_t('settings.ttn_app_id'), ENT_QUOTES, 'UTF-8'); ?></span>
          <input type="text" class="form-control" id='ttnAppId' name='ttnAppId' value='<?php echo mds_h($boardObj->getTtnAppId()); ?>' pattern="^[_A-Za-z0-9\-]{1,36}" maxlength="36" title="Höchstens 36 Zeichen sowie nur Kleinbuchstaben und Zahlen." style="background:#e9ecef" readonly>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%"><?php echo htmlspecialchars(mds_t('settings.ttn_dev_id'), ENT_QUOTES, 'UTF-8'); ?></span>
          <input type="text" class="form-control" id='ttnDevId' name='ttnDevId' value='<?php echo mds_h($boardObj->getTtnDevId()); ?>' pattern="^[_A-Za-z0-9\-]{1,36}" maxlength="36" title="Höchstens 36 Zeichen sowie nur Kleinbuchstaben und Zahlen." style="background:#e9ecef" readonly>
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
          <span class="input-group-text" style="width: 30%"><?php echo htmlspecialchars(mds_t('form.board.firmware_version'), ENT_QUOTES, 'UTF-8'); ?></span>
          <input type="text" readonly class="form-control" id='firmwareversion' name='firmwareversion' value='<?php echo mds_h($boardObj->getFirmwareVersion()); ?>' style="background:#e9ecef" readonly>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%; white-space: break-spaces"><?php echo htmlspecialchars(mds_t('form.board.alarm_unavailable'), ENT_QUOTES, 'UTF-8'); ?></span>

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
          <span class="input-group-text" style="width: 30%;"><?php echo htmlspecialchars(mds_t('form.board.on_dashboard'), ENT_QUOTES, 'UTF-8'); ?></span>
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
          <span class="input-group-text" style="width: 30%; white-space: break-spaces"><?php echo htmlspecialchars(mds_t('settings.update_interval'), ENT_QUOTES, 'UTF-8'); ?></span>
          <input type="text" class="form-control" id='updateDataTimer' name='updateDataTimer' value='<?php echo mds_h($boardObj->getUpdateDataTimer()); ?>' style="background:#e9ecef" readonly>
        </div>

        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%; white-space: break-spaces"><?php echo htmlspecialchars(mds_t('form.board.offline_after'), ENT_QUOTES, 'UTF-8'); ?></span>
          <input type="text" class="form-control" id='offlineDataTimer' name='offlineDataTimer' value='<?php echo mds_h($boardObj->getOfflineDataTimer()); ?>' title="After this timer, the board displays as offline.">
        </div>

        <?php
				if($isAdmin) {
				?>
        <div class="input-group mb-3">
          <span class="input-group-text" style="width: 30%; white-space: break-spaces"><?php echo htmlspecialchars(mds_t('settings.owner_user'), ENT_QUOTES, 'UTF-8'); ?></span>
          <select class='col col-sm-4 form-select' aria-label='Default select example' name='ownerId'>
							<?php
              if ($boardObj->getOwnerUserId() == null) {
                echo "<option selected value=''></option>";
              } else {
                echo "<option value=''></option>";
              }
							foreach ($allUsers as $singleRowUser) {
								if ($boardObj->getOwnerUserId() == $singleRowUser['id']) {
									echo "<option selected value='" . (int)$singleRowUser['id'] . "'>" . (int)$singleRowUser['id'] . " : " . mds_h($singleRowUser['email']) . "</option>";
								} else {
									echo "<option value='" . (int)$singleRowUser['id'] . "'>" . (int)$singleRowUser['id'] . " : " . mds_h($singleRowUser['email']) . "</option>";
								}
							}
							?></select>
        </div>
				<?php
				}
			?>

        <div class='row'>
          <div class="col-sm-offset-2 col-sm-8">
            <input type='submit' class="btn btn-danger" id='submit_formBoards_remove' name='submit_formBoards_remove' value='<?php echo htmlspecialchars(mds_t('form.board.remove'), ENT_QUOTES, 'UTF-8'); ?>' onclick="clicked(event)">
          </div>
          <div class="col-sm-offset-2 col-sm-4">
          <div class="float-end">
            <a class='mr-2 btn btn-primary' href='settings.php#confBoards' role='button'><?php echo htmlspecialchars(mds_t('common.back'), ENT_QUOTES, 'UTF-8'); ?></a>
            <input type='submit' class="btn btn-primary" id='submit_formBoards' name='submit_formBoards' value='<?php echo htmlspecialchars(mds_t('common.save'), ENT_QUOTES, 'UTF-8'); ?>'>
          </div>
          </div>
        </div>
      </form>
  </div>

    <div role="tabpanel" class="tab-pane" id="sensors">
      <div class="table-responsive">
      <table class="table table-bordered align-middle">
        <thead>
          <tr>
            <th>#</th><th>sensor id</th><th><?php echo htmlspecialchars(mds_t('form.sensor.sensor_type'), ENT_QUOTES, 'UTF-8'); ?></th><th><?php echo htmlspecialchars(mds_t('form.sensor.sensor_address'), ENT_QUOTES, 'UTF-8'); ?></th><th><?php echo htmlspecialchars(mds_t('form.sensor.group_name'), ENT_QUOTES, 'UTF-8'); ?></th><th><?php echo htmlspecialchars(mds_t('common.description'), ENT_QUOTES, 'UTF-8'); ?></th><th><?php echo htmlspecialchars(mds_t('common.location'), ENT_QUOTES, 'UTF-8'); ?></th><th><?php echo htmlspecialchars(mds_t('form.board.last_data'), ENT_QUOTES, 'UTF-8'); ?></th><th><?php echo htmlspecialchars(mds_t('form.board.sensor_status'), ENT_QUOTES, 'UTF-8'); ?></th><th><?php echo htmlspecialchars(mds_t('form.sensor.show_dashboard'), ENT_QUOTES, 'UTF-8'); ?></th><th><?php echo htmlspecialchars(mds_t('common.edit'), ENT_QUOTES, 'UTF-8'); ?></th>
          </tr>
        </thead>
      <tbody>
      <?php
      // collect all Sensor IDs that belong to the Board.
        $count = 1;
        $Sensorname = null;
        foreach($mySensors as $singleRowMySensor) {
          $Sensorname = myFunctions::getSensorType($singleRowMySensor['typId']);
          $sensorId = (int)$singleRowMySensor['id'];
          $status = $sensorOverviewById[$sensorId] ?? array();
          $statusReason = $status['cleanupReason'] ?? 'current';
          $statusLabels = array(
            'current' => array('success', 'form.board.sensor_current'),
            'stale' => array('warning text-dark', 'form.board.sensor_stale'),
            'never' => array('secondary', 'form.board.sensor_never'),
            'protected' => array('info text-dark', 'form.board.sensor_protected'),
            'alert' => array('info text-dark', 'form.board.sensor_alert_protected'),
          );
          $statusLabel = $statusLabels[$statusReason] ?? $statusLabels['current'];
          $lastReading = empty($status['lastReading'])
            ? mds_t('form.board.sensor_never')
            : date('d.m.Y H:i:s', strtotime($status['lastReading']));
          echo "<tr>";
          echo "<td>".$count++."</td>";
          echo "<td>". $sensorId . "</td>";
          echo "<td>". mds_h(($Sensorname['name'] ?? '') . ", " . ($Sensorname['description'] ?? '')) . "</td>";
          echo "<td>". mds_h($singleRowMySensor['sensorAddress'] ?? '') . "</td>";
          echo "<td>".mds_h($singleRowMySensor['name'] ?? '')."</td>";
          echo "<td>".mds_h($singleRowMySensor['description'] ?? '')."</td>";
          echo "<td>".mds_h($singleRowMySensor['locationOfMeasurement'] ?? '')."</td>";
          echo "<td class='text-nowrap'>".mds_h($lastReading)."</td>";
          echo "<td><span class='badge bg-".mds_h($statusLabel[0])."'>".mds_h(mds_t($statusLabel[1]))."</span></td>";
          if(isset($singleRowMySensor['onDashboard']) && $singleRowMySensor['onDashboard'] == '1')
          {
            echo "<td><input type='checkbox' id='onDashboard' name='onDashboard' value=" . $singleRowMySensor['onDashboard'] . " checked=" . $singleRowMySensor['onDashboard'] . " disabled></td>";
          }
          else
          {
            echo "<td><input type='checkbox' id='onDashboard' name='onDashboard' value='1' disabled></td>";
          }
          $sensorDisplayName = trim((string)($singleRowMySensor['name'] ?? ''));
          if ($sensorDisplayName === '') {
            $sensorDisplayName = (string)($Sensorname['name'] ?? ('Sensor ' . $sensorId));
          }
          $deleteConfirmation = mds_t('form.board.sensor_delete_confirm', array($sensorDisplayName, $sensorId));
          echo "<td><div class='d-flex gap-2 align-items-center'>";
          echo "<a class='btn btn-sm btn-outline-primary' href=\"formSensors.php?id=" . $sensorId . "&boardId=" . $varId . "\" title='" . mds_h(mds_t('common.edit')) . "' aria-label='" . mds_h(mds_t('common.edit')) . "'><i class='bi bi-pencil-fill'></i></a>";
          if ($canCleanupSensors) {
            echo "<form method='post' action='formBoards.php?id=" . $varId . "#sensors' class='m-0'>";
            echo mds_csrf_input();
            echo "<input type='hidden' name='sensor_id' value='" . $sensorId . "'>";
            echo "<button type='submit' class='btn btn-sm btn-outline-danger' name='submit_sensor_group_delete' value='1' title='" . mds_h(mds_t('form.board.sensor_delete')) . "' aria-label='" . mds_h(mds_t('form.board.sensor_delete')) . "' onclick='return confirm(" . mds_h(json_encode($deleteConfirmation, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) . ");'><i class='bi bi-trash-fill'></i></button>";
            echo "</form>";
          }
          echo "</div></td>";
          echo "</tr>";
        }
      ?>
      </tbody></table>
      </div>

      <?php if ($canCleanupSensors) { ?>
        <section class="card mt-4 mb-4">
          <div class="card-body">
            <h2 class="h5"><?php echo mds_h(mds_t('form.board.sensor_cleanup_title')); ?></h2>
            <p class="text-muted mb-3"><?php echo mds_h(mds_t('form.board.sensor_cleanup_hint', array(SensorCleanupService::DEFAULT_STALE_DAYS))); ?></p>
            <?php if ($cleanupCandidateCount > 0) { ?>
              <form method="post" action="formBoards.php?id=<?php echo $varId; ?>#sensors">
                <?php echo mds_csrf_input(); ?>
                <button
                  type="submit"
                  class="btn btn-danger"
                  name="submit_unused_sensor_cleanup"
                  value="1"
                  onclick="return confirm(<?php echo mds_h(json_encode(mds_t('form.board.sensor_cleanup_confirm', array($cleanupCandidateCount, SensorCleanupService::DEFAULT_STALE_DAYS)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>);"
                ><?php echo mds_h(mds_t('form.board.sensor_cleanup_button', array($cleanupCandidateCount))); ?></button>
              </form>
            <?php } else { ?>
              <div class="alert alert-success mb-0" role="status"><?php echo mds_h(mds_t('form.board.sensor_cleanup_none')); ?></div>
            <?php } ?>
          </div>
        </section>
      <?php } ?>
    </div>
  </div>
</div>
<script>
  function clicked(e)
  {
    if(!confirm(<?php echo json_encode(mds_t('form.board.remove_confirm'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?> + ' ' + <?php echo (int)$boardObj->getId(); ?> + '?')) {
      e.preventDefault();
    }
  }
</script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (window.location.hash !== '#sensors' || typeof bootstrap === 'undefined') {
      return;
    }
    var trigger = document.querySelector('[href="#sensors"]');
    if (trigger) {
      bootstrap.Tab.getOrCreateInstance(trigger).show();
    }
  });
</script>
<?php
  include(dirname(__DIR__) . "/app/Presentation/Common/footer.inc.php");
?>
