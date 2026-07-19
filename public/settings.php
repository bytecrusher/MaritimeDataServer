<?php
/**
 *
 * @author: Guntmar Höche
 * @license: TBD
 */

require_once dirname(__DIR__) . "/bootstrap/app.php";
mds_start_session();
require_once dirname(__DIR__) . "/app/Infrastructure/Database/dbConfig.func.php";
require_once dirname(__DIR__) . "/app/Application/myFunctions.func.php";
require_once dirname(__DIR__) . "/app/Application/SettingsPageService.php";
require_once dirname(__DIR__) . "/app/Domain/User/user.class.php";
require_once dirname(__DIR__) . "/app/Application/dbUpdateData.php";
require_once dirname(__DIR__) . "/app/Infrastructure/Logging/writeToLogFunction.func.php";

$config  = new configuration();
$userObj = SettingsPageService::resolveCurrentUserFromSession();
if (!$userObj) {
  $loginTarget = function_exists('mds_route_path') ? mds_route_path('index.php') : './index.php';
  header('Location: ' . $loginTarget);
  echo '<!doctype html><meta charset="utf-8"><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($loginTarget, ENT_QUOTES, 'UTF-8') . '">';
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !mds_verify_csrf_token($_POST['csrf_token'] ?? '')) {
  $error_msg = mds_t('login.csrf');
} elseif(isset($_GET['save'])) {
  $saveResult = SettingsPageService::handleSettingsSave($userObj, $config, $_GET['save'], $_POST);
  $userObj = $saveResult['userObj'];
  $success_msg = $saveResult['success_msg'];
  $error_msg = $saveResult['error_msg'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error_msg) && (isset($_POST['submit_formBoards']) || isset($_POST['submit_formBoards_remove']))) {
  $boardResult = SettingsPageService::handleBoardFormSubmission($userObj, $_POST);
  $success_msg = $boardResult['success_msg'] ?? $success_msg ?? null;
  $error_msg = $boardResult['error_msg'] ?? $error_msg ?? null;
}

try {
  $pageData = SettingsPageService::buildPageData($userObj, $config);
} catch (Throwable $settingsPageException) {
  writeToLogFunction::exception($settingsPageException, $_SERVER["SCRIPT_FILENAME"], array(
    'message' => 'Settings page data could not be loaded. Rendering fallback page.',
    'userId' => (int)$userObj->getId(),
  ));
  $error_msg = mds_t('settings.load_error');
  $pageData = array(
    'demoMode' => (bool) $config::$demoMode,
    'showQrCode' => $config::$ShowQrCode,
    'apiKey' => $config::$apiKey,
    'otaUpdateSecret' => $config::$otaUpdateSecret,
    'googleSiteVerification' => $config::$googleSiteVerification,
    'sendEmails' => $config::$sendEmails,
    'myBoards' => array(),
    'allBoards' => array(),
    'allUsers' => array(),
    'accessBoards' => array(),
    'canManageAccess' => false,
    'timeZones' => SettingsPageService::getTimeZoneList(),
    'currentLogContent' => SettingsPageService::getCurrentLogContent(),
    'otaUpdateLogs' => array(
      'path' => dirname(__DIR__) . '/var/ota/logs/log.csv',
      'entries' => array(),
      'message' => 'OTA log could not be loaded.',
    ),
    'isAdmin' => myFunctions::isUserAdmin((int)$userObj->getId()),
    'notificationOverview' => array(
      'jobStatus' => null,
      'offlineBoards' => array(),
      'activeSensorAlerts' => array(),
    ),
    'migrationStatus' => array(),
  );
}
$varDemoMode = $pageData['demoMode'];
$varShowQrCode = $pageData['showQrCode'];
$var_apiKey = $pageData['apiKey'];
$varOtaUpdateSecret = $pageData['otaUpdateSecret'];
$varGoogleSiteVerification = $pageData['googleSiteVerification'] ?? $config::$googleSiteVerification;
$varSend_emails = $pageData['sendEmails'];
$myBoards = $pageData['myBoards'];
$allBoards = $pageData['allBoards'];
$allUsers = $pageData['allUsers'];
$accessBoards = $pageData['accessBoards'] ?? array();
$canManageAccess = (bool)($pageData['canManageAccess'] ?? false);
$timeZones = $pageData['timeZones'];
$currentLogContent = $pageData['currentLogContent'];
$otaUpdateLogs = $pageData['otaUpdateLogs'] ?? array('path' => '', 'entries' => array(), 'message' => '');
$isAdmin = $pageData['isAdmin'];
$notificationOverview = $pageData['notificationOverview'];
$notificationJobStatus = $notificationOverview['jobStatus'] ?? null;
$offlineBoardOverview = $notificationOverview['offlineBoards'] ?? array();
$activeSensorAlertsOverview = $notificationOverview['activeSensorAlerts'] ?? array();
$migrationStatus = $pageData['migrationStatus'] ?? array();

include_once dirname(__DIR__) . "/app/Presentation/Common/header.inc.php";
?>

<style>
th.rotated-text {
    height: 140px;
    white-space: nowrap;
    padding: 0 !important;
}

th.rotated-text > div {
    transform:
        translate(0px, 0px)
        rotate(270deg);
    width: 30px;
}

th.rotated-text > div > span {
    padding: 5px 10px;
}

.settings-tabs {
    flex-wrap: nowrap;
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: thin;
}

.settings-tabs .nav-item {
    flex: 0 0 auto;
}

.settings-tabs .nav-link {
    white-space: nowrap;
}

.settings-tab-content {
    background: white;
}

@media (max-width: 767.98px) {
    .settings-tabs {
        gap: 0.35rem;
        padding-bottom: 0.35rem;
    }

    .settings-tabs .nav-link {
        padding: 0.7rem 0.95rem;
        border-radius: 999px;
        font-size: 0.95rem;
    }

    .settings-tab-content {
        border-top: 1px solid #ddd;
    }

    .settings-tab-content .row {
        padding-top: 12px;
    }

    .settings-tab-content .col-sm-4,
    .settings-tab-content .col-sm-10,
    .settings-tab-content .col-sm-2 {
        width: 100%;
    }

    #settings-log-content {
        min-height: 320px;
    }
}
</style>

<link href="https://cdn.jsdelivr.net/npm/bootstrap5-toggle@5.0.4/css/bootstrap5-toggle.min.css" rel="stylesheet">

<script src="https://cdn.jsdelivr.net/npm/bootstrap5-toggle@5.0.4/js/bootstrap5-toggle.jquery.min.js"></script>

<div class="jumbotron" style="padding: 1rem 1rem;">
  <div class="container">
    <h1><?php echo htmlspecialchars(mds_t('settings.title'), ENT_QUOTES, 'UTF-8'); ?></h1>
  </div>
</div>
<div class="container-xl main-container">
  <div id="alert-container"></div>
  <?php
  if(isset($success_msg) && !empty($success_msg)) {
  ?>  <script>
      window.onload = function () {
        //on success
        g = document.createElement('div');
                g.setAttribute("class", "alert alert-success alert-dismissible bg-opacity-70 bg-gray bg-opacity-20 shadow-risen");
                g.setAttribute("role", "alert");
                g.textContent = <?php echo json_encode(strip_tags((string)$success_msg), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
                const closeButton = document.createElement('button');
                closeButton.type = 'button';
                closeButton.className = 'btn-close';
                closeButton.setAttribute('data-bs-dismiss', 'alert');
                closeButton.setAttribute('aria-label', 'Close');
                g.appendChild(closeButton);
                const bsAlert = new bootstrap.Alert(g);
                // Dismiss time out
                setTimeout(() => {
                  bsAlert.close();
                }, 5000);
                $("#alert-container").append(g);
      }
    </script>
  <?php } ?>

  <?php
  if(isset($error_msg) && !empty($error_msg)) {
  ?>
    <script>
      window.onload = function () {
        //on error
        g = document.createElement('div');
        g.setAttribute("class", "alert alert-danger alert-dismissible bg-opacity-70 bg-gray bg-opacity-20 shadow-risen");
        g.setAttribute("role", "alert");
        g.textContent = <?php echo json_encode(strip_tags((string)$error_msg), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'btn-close';
        closeButton.setAttribute('data-bs-dismiss', 'alert');
        closeButton.setAttribute('aria-label', 'Close');
        g.appendChild(closeButton);
        const bsAlert = new bootstrap.Alert(g);
        // Dismiss time out
        setTimeout(() => {
          bsAlert.close();
        }, 5000);
        $("#alert-container").append(g);
      }
    </script>
  <?php } ?>
  <div>
    <div class="card mb-3 shadow-sm">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <h5 class="card-title mb-2"><?php echo htmlspecialchars(mds_t('settings.notification_status'), ENT_QUOTES, 'UTF-8'); ?></h5>
            <?php if ($notificationJobStatus) { ?>
              <div><strong><?php echo htmlspecialchars(mds_t('settings.last_job_status'), ENT_QUOTES, 'UTF-8'); ?>:</strong> <?php echo htmlspecialchars($notificationJobStatus['status'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
              <div><strong><?php echo htmlspecialchars(mds_t('settings.started'), ENT_QUOTES, 'UTF-8'); ?>:</strong> <?php echo htmlspecialchars($notificationJobStatus['startedAt'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
              <div><strong><?php echo htmlspecialchars(mds_t('settings.finished'), ENT_QUOTES, 'UTF-8'); ?>:</strong> <?php echo htmlspecialchars($notificationJobStatus['finishedAt'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
              <div><strong><?php echo htmlspecialchars(mds_t('settings.offline_mails'), ENT_QUOTES, 'UTF-8'); ?>:</strong> <?php echo htmlspecialchars((string)($notificationJobStatus['offlineSent'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></div>
              <div><strong><?php echo htmlspecialchars(mds_t('settings.sensor_mails'), ENT_QUOTES, 'UTF-8'); ?>:</strong> <?php echo htmlspecialchars((string)($notificationJobStatus['sensorAlertsSent'] ?? 0), ENT_QUOTES, 'UTF-8'); ?></div>
            <?php } else { ?>
              <div class="text-muted"><?php echo htmlspecialchars(mds_t('settings.no_notification_status'), ENT_QUOTES, 'UTF-8'); ?></div>
            <?php } ?>
          </div>
          <div class="col-md-4">
            <h5 class="card-title mb-2"><?php echo htmlspecialchars(mds_t('settings.offline_boards'), ENT_QUOTES, 'UTF-8'); ?></h5>
            <div><strong><?php echo htmlspecialchars(mds_t('settings.configured'), ENT_QUOTES, 'UTF-8'); ?>:</strong> <?php echo count($offlineBoardOverview); ?></div>
            <?php if (!empty($offlineBoardOverview)) { ?>
              <div class="small text-muted mt-2">
                <?php
                  $offlinePreview = array_slice($offlineBoardOverview, 0, 3);
                  foreach ($offlinePreview as $offlineBoard) {
                    echo htmlspecialchars(($offlineBoard['name'] ?: $offlineBoard['macAddress']) . ' (' . (int)$offlineBoard['offlineDataTimer'] . ' min)', ENT_QUOTES, 'UTF-8') . '<br>';
                  }
                ?>
              </div>
            <?php } ?>
          </div>
          <div class="col-md-4">
            <h5 class="card-title mb-2"><?php echo htmlspecialchars(mds_t('settings.sensor_alerts'), ENT_QUOTES, 'UTF-8'); ?></h5>
            <div><strong><?php echo htmlspecialchars(mds_t('settings.configured_channels'), ENT_QUOTES, 'UTF-8'); ?>:</strong> <?php echo count($activeSensorAlertsOverview); ?></div>
            <?php
              $activeAlertsOnly = array_filter($activeSensorAlertsOverview, function ($channel) {
                return !empty($channel['AlertState']);
              });
            ?>
            <div><strong><?php echo htmlspecialchars(mds_t('settings.currently_active'), ENT_QUOTES, 'UTF-8'); ?>:</strong> <?php echo count($activeAlertsOnly); ?></div>
            <?php if (!empty($activeAlertsOnly)) { ?>
              <div class="small text-muted mt-2">
                <?php
                  $activeAlertsPreview = array_slice(array_values($activeAlertsOnly), 0, 3);
                  foreach ($activeAlertsPreview as $activeAlert) {
                    echo htmlspecialchars(($activeAlert['boardName'] ?: '- unnamed -') . ' / ' . ($activeAlert['name'] ?: ('Channel ' . $activeAlert['channelNr'])) . ' [' . $activeAlert['AlertState'] . ']', ENT_QUOTES, 'UTF-8') . '<br>';
                  }
                ?>
              </div>
            <?php } ?>
          </div>
        </div>
      </div>
    </div>
    <!-- Nav tabs -->
    <ul class="nav nav-tabs settings-tabs" id="settingsTabs" role="tablist">
      <li class="nav-item" role="presentation"><a class="nav-link active" href="#data" role="tab" data-bs-toggle="tab"><?php echo htmlspecialchars(mds_t('settings.personal_data'), ENT_QUOTES, 'UTF-8'); ?></a></li>
      <li class="nav-item" role="presentation"><a class="nav-link" href="#email" role="tab" data-bs-toggle="tab"><?php echo htmlspecialchars(mds_t('common.email'), ENT_QUOTES, 'UTF-8'); ?></a></li>
      <li class="nav-item" role="presentation"><a class="nav-link" href="#password" role="tab" data-bs-toggle="tab"><?php echo htmlspecialchars(mds_t('common.password'), ENT_QUOTES, 'UTF-8'); ?></a></li>
      <li class="nav-item" role="presentation"><a class="nav-link" href="#confBoards" role="tab" data-bs-toggle="tab"><?php echo htmlspecialchars(mds_t('settings.my_boards'), ENT_QUOTES, 'UTF-8'); ?></a></li>
      <?php if ($canManageAccess) { ?>
        <li class="nav-item" role="presentation"><a class="nav-link" href="#access" role="tab" data-bs-toggle="tab"><?php echo htmlspecialchars(mds_t('settings.access'), ENT_QUOTES, 'UTF-8'); ?></a></li>
      <?php } ?>
      <li class="nav-item" role="presentation"><a class="nav-link" href="#confDashboard" role="tab" data-bs-toggle="tab"><?php echo htmlspecialchars(mds_t('settings.dashboard'), ENT_QUOTES, 'UTF-8'); ?></a></li>
      <li class="nav-item" role="presentation"><a class="nav-link" href="#privacy" role="tab" data-bs-toggle="tab"><?php echo htmlspecialchars(mds_t('nav.privacy'), ENT_QUOTES, 'UTF-8'); ?></a></li>
      <?php
        if($isAdmin) {
        ?>
          <li class='nav-item' role='presentation'><a class='nav-link' href='#allBoards' role='tab' data-bs-toggle='tab'><?php echo htmlspecialchars(mds_t('settings.all_boards'), ENT_QUOTES, 'UTF-8'); ?></a></li>
          <li class='nav-item' role='presentation'><a class='nav-link' href='#users' role='tab' data-bs-toggle='tab'><?php echo htmlspecialchars(mds_t('settings.users'), ENT_QUOTES, 'UTF-8'); ?></a></li>
          <li class='nav-item' role='presentation'><a class='nav-link' href='#serverSetting' role='tab' data-bs-toggle='tab'><?php echo htmlspecialchars(mds_t('settings.server'), ENT_QUOTES, 'UTF-8'); ?></a></li>
          <li class='nav-item' role='presentation'><a class='nav-link' href='#migration' role='tab' data-bs-toggle='tab'><?php echo htmlspecialchars(mds_t('settings.migration'), ENT_QUOTES, 'UTF-8'); ?></a></li>
          <li class='nav-item' role='presentation'><a class='nav-link' href='#log' role='tab' data-bs-toggle='tab'><?php echo htmlspecialchars(mds_t('settings.log'), ENT_QUOTES, 'UTF-8'); ?></a></li>
        <?php
        }
      ?>
    </ul>

    <!-- Personal data -->
    <div class="tab-content settings-tab-content">
      <div role="tabpanel" class="tab-pane show active" id="data">
        <form action="?save=personal_data" method="post" class="form-horizontal">
          <?php echo mds_csrf_input(); ?>
          <div class="form-group">
            <div class="row">
              <label for="inputFirstName" class="col-sm-2 control-label"><?php echo htmlspecialchars(mds_t('settings.first_name'), ENT_QUOTES, 'UTF-8'); ?></label>
              <div class="col-sm-4">
                <input class="form-control" id="inputFirstName" name="firstName" type="text" value="<?php echo htmlspecialchars($userObj->getFirstName(), ENT_QUOTES, 'UTF-8'); ?>" required>
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="row">
              <label for="inputLastname" class="col-sm-2 control-label"><?php echo htmlspecialchars(mds_t('settings.last_name'), ENT_QUOTES, 'UTF-8'); ?></label>
              <div class="col-sm-4">
                <input class="form-control" id="inputLastname" name="lastName" type="text" value="<?php echo htmlspecialchars($userObj->getLastName(), ENT_QUOTES, 'UTF-8'); ?>" required>
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="row">
              <label for="inputTimezone" class="col-sm-2 control-label"><?php echo htmlspecialchars(mds_t('settings.timezone'), ENT_QUOTES, 'UTF-8'); ?></label>
              <?php $userTimezone = htmlspecialchars($userObj->getTimezone() ?? "", ENT_QUOTES, 'UTF-8'); ?>
              <div class="col-sm-4">
              <select class="form-select" aria-label="Default select example" id="inputTimezone" name="Timezone">
                <option value="0"><?php echo htmlspecialchars(mds_t('settings.select_timezone'), ENT_QUOTES, 'UTF-8'); ?></option>
                <?php foreach($timeZones as $t) { ?>
                  <?php if($t['zone'] == $userTimezone ) { ?>
                    <option value="<?php print $t['zone'] ?>" selected>
                      <?php print $t['zone'] . ' - ' . $t['diff_from_GMT'] ?>
                    </option>
                  <?php } else { ?>
                    <option value="<?php print $t['zone'] ?>">
                      <?php print $t['zone'] . ' - ' . $t['diff_from_GMT'] ?>
                    </option>
                  <?php } ?>
                <?php } ?>
              </select>
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="row">
              <label for="inputLanguage" class="col-sm-2 control-label"><?php echo htmlspecialchars(mds_t('common.language'), ENT_QUOTES, 'UTF-8'); ?></label>
              <div class="col-sm-4">
                <?php $userLanguage = $userObj->getLanguage(); ?>
                <select class="form-select" id="inputLanguage" name="language">
                  <option value="en" <?php if ($userLanguage === 'en') { echo 'selected'; } ?>><?php echo htmlspecialchars(mds_t('language.english'), ENT_QUOTES, 'UTF-8'); ?></option>
                  <option value="de" <?php if ($userLanguage === 'de') { echo 'selected'; } ?>><?php echo htmlspecialchars(mds_t('language.german'), ENT_QUOTES, 'UTF-8'); ?></option>
                </select>
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="row">
              <div class="col col-sm-2"><?php echo htmlspecialchars(mds_t('settings.receive_notifications'), ENT_QUOTES, 'UTF-8'); ?></div>
              <label class="col col-sm-4">
                <input type='hidden' class='form-check-input' name='receiveNotifications' value='0'>
                <input type='checkbox' class='form-check-input' id='receiveNotifications' name='receiveNotifications' value='1' <?php if ($userObj->getReceiveNotifications()) { echo 'checked'; } ?>>
              </label>
            </div>
          </div>

          <div class="form-group">
            <div class="row">
              <div class="col col-sm-2"><?php echo htmlspecialchars(mds_t('settings.offline_alerts'), ENT_QUOTES, 'UTF-8'); ?></div>
              <label class="col col-sm-4">
                <input type='hidden' class='form-check-input' name='receiveOfflineNotifications' value='0'>
                <input type='checkbox' class='form-check-input' id='receiveOfflineNotifications' name='receiveOfflineNotifications' value='1' <?php if ($userObj->getReceiveOfflineNotifications()) { echo 'checked'; } ?>>
              </label>
            </div>
          </div>

          <div class="form-group">
            <div class="row">
              <div class="col col-sm-2"><?php echo htmlspecialchars(mds_t('settings.sensor_alerts_question'), ENT_QUOTES, 'UTF-8'); ?></div>
              <label class="col col-sm-4">
                <input type='hidden' class='form-check-input' name='receiveSensorNotifications' value='0'>
                <input type='checkbox' class='form-check-input' id='receiveSensorNotifications' name='receiveSensorNotifications' value='1' <?php if ($userObj->getReceiveSensorNotifications()) { echo 'checked'; } ?>>
              </label>
            </div>
          </div>

          <div class="form-group">
            <div class="row">
              <div class="col-sm-offset-2 col-sm-10">
              <button type="submit" class="btn btn-primary"><?php echo htmlspecialchars(mds_t('common.save'), ENT_QUOTES, 'UTF-8'); ?></button>
              <button type="submit" class="btn btn-outline-secondary ms-2" formaction="?save=testMailUser"><?php echo htmlspecialchars(mds_t('settings.send_test_mail'), ENT_QUOTES, 'UTF-8'); ?></button>
              </div>
            </div>
          </div>
        </form>
      </div>

      <!-- change of email address -->
      <div role="tabpanel" class="tab-pane" id="email">
        <p style="margin-bottom: 0px; margin-top: 1rem;"><?php echo htmlspecialchars(mds_t('settings.change_email_hint'), ENT_QUOTES, 'UTF-8'); ?></p>
        <form action="?save=email" method="post" class="form-horizontal">
          <?php echo mds_csrf_input(); ?>
          <div class="form-group">
            <div class="row">
              <label for="inputPasswordForValidation" class="col-sm-2 control-label"><?php echo htmlspecialchars(mds_t('common.password'), ENT_QUOTES, 'UTF-8'); ?></label>
              <div class="col-sm-4">
                <input class="form-control" id="inputPasswordForValidation" name="password" type="password" required>
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="row">
              <label for="inputEmail" class="col-sm-2 control-label"><?php echo htmlspecialchars(mds_t('common.email'), ENT_QUOTES, 'UTF-8'); ?></label>
              <div class="col-sm-4">
                <input class="form-control" id="inputEmail" name="email" type="email" value="<?php echo htmlspecialchars($userObj->getEmail(), ENT_QUOTES, 'UTF-8'); ?>" required>
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="row">
              <label for="inputEmail2" class="col-sm-2 control-label"><?php echo htmlspecialchars(mds_t('settings.email_repeat'), ENT_QUOTES, 'UTF-8'); ?></label>
              <div class="col-sm-4">
                <input class="form-control" id="inputEmail2" name="email2" type="email"  required>
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="row">
              <div class="col-sm-offset-2 col-sm-10">
              <button type="submit" class="btn btn-primary"><?php echo htmlspecialchars(mds_t('common.save'), ENT_QUOTES, 'UTF-8'); ?></button>
              </div>
            </div>
          </div>
        </form>
      </div>

      <!-- change password -->
      <div role="tabpanel" class="tab-pane" id="password">
        <p style="margin-bottom: 0px; margin-top: 1rem;"><?php echo htmlspecialchars(mds_t('settings.change_password_hint'), ENT_QUOTES, 'UTF-8'); ?></p>
        <form action="?save=password" method="post" class="form-horizontal">
          <?php echo mds_csrf_input(); ?>
          <div class="form-group">
            <div class="row">
              <label for="inputPassword" class="col-sm-2 control-label"><?php echo htmlspecialchars(mds_t('settings.old_password'), ENT_QUOTES, 'UTF-8'); ?></label>
              <div class="col-sm-4">
                <input class="form-control" id="inputPasswordOld" name="passwordOld" type="password" required>
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="row">
              <label for="inputPasswordNew" class="col-sm-2 control-label"><?php echo htmlspecialchars(mds_t('settings.new_password'), ENT_QUOTES, 'UTF-8'); ?></label>
              <div class="col-sm-4">
                <input class="form-control" id="inputPasswordNew" name="passwordNew" type="password" required>
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="row">
              <label for="inputPasswordNew2" class="col-sm-2 control-label"><?php echo htmlspecialchars(mds_t('settings.new_password_repeat'), ENT_QUOTES, 'UTF-8'); ?></label>
              <div class="col-sm-4">
                <input class="form-control" id="inputPasswordNew" name="passwordNew2" type="password"  required>
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="row">
              <div class="col-sm-offset-2 col-sm-10">
              <button type="submit" class="btn btn-primary"><?php echo htmlspecialchars(mds_t('common.save'), ENT_QUOTES, 'UTF-8'); ?></button>
              </div>
            </div>
          </div>
        </form>
      </div>

      <!-- Configure the user's boards -->
      <div role="tabpanel" class="tab-pane" id="confBoards">
        <div class="container-fluid border mb-2">
        <span><?php echo htmlspecialchars(mds_t('settings.toggle_columns'), ENT_QUOTES, 'UTF-8'); ?></span>
          <div class="form-check form-switch d-inline-block pe-4">
            <label for="inlineCheckbox1" class="form-check-label">id</label>
            <input id="inlineCheckbox1" value="toggleDisplayId" class="form-check-input myToggleButton" type="checkbox" checked data-size="small">
          </div>
          <div class="form-check form-switch d-inline-block pe-4">
            <label for="inlineCheckbox2" class="form-check-label"><?php echo htmlspecialchars(mds_t('settings.mac_address'), ENT_QUOTES, 'UTF-8'); ?></label>
            <input id="inlineCheckbox2" value="toggleDisplayMacAddress" class="form-check-input myToggleButton" type="checkbox" checked data-size="small">
          </div>

          <div class="form-check form-switch d-inline-block pe-4">
            <label for="inlineCheckbox3" class="form-check-label"><?php echo htmlspecialchars(mds_t('common.location'), ENT_QUOTES, 'UTF-8'); ?></label>
            <input id="inlineCheckbox3" value="toggleDisplayLocation" class="form-check-input myToggleButton" type="checkbox" checked data-size="small">
          </div>

          <div class="form-check form-switch d-inline-block pe-4">
            <label for="inlineCheckbox4" class="form-check-label"><?php echo htmlspecialchars(mds_t('settings.ttn_id'), ENT_QUOTES, 'UTF-8'); ?></label>
            <input id="inlineCheckbox4" value="toggleDisplayTtnDevId" class="form-check-input myToggleButton" type="checkbox" checked data-size="small">
          </div>

          <div class="form-check form-check-inline">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal"><?php echo htmlspecialchars(mds_t('settings.add_new_board'), ENT_QUOTES, 'UTF-8'); ?></button>
          </div>

        </div>
        <div class="panel panel-default table-responsive">
          <table class="table table-bordered">
          <thead>
          <tr>
            <th class='toggleDisplayId'>id</th>
            <th class='toggleDisplayMacAddress '><div><span><?php echo htmlspecialchars(mds_t('settings.mac_address'), ENT_QUOTES, 'UTF-8'); ?></span></div></th>
            <th><?php echo htmlspecialchars(mds_t('common.name'), ENT_QUOTES, 'UTF-8'); ?></th>
            <th class="toggleDisplayLocation"><?php echo htmlspecialchars(mds_t('common.location'), ENT_QUOTES, 'UTF-8'); ?></th>
            <th><?php echo htmlspecialchars(mds_t('common.description'), ENT_QUOTES, 'UTF-8'); ?></th>
            <th class="toggleDisplayTtnDevId"><?php echo htmlspecialchars(mds_t('settings.ttn_dev_id'), ENT_QUOTES, 'UTF-8'); ?></th>
            <th class=' rotated-text'><div><span><?php echo htmlspecialchars(mds_t('common.sensors'), ENT_QUOTES, 'UTF-8'); ?></span></div></th>
            <th class=' rotated-text'><div><span><?php echo htmlspecialchars(mds_t('form.board.alarm_unavailable'), ENT_QUOTES, 'UTF-8'); ?></span></div></th>
            <th class=' rotated-text'><div><span><?php echo htmlspecialchars(mds_t('common.details'), ENT_QUOTES, 'UTF-8'); ?></span></div></th>
          </tr>
          </thead>
          <tbody>
          <?php
          $countBoardRow = 1;
          foreach($myBoards as $singleRowMyBoard) {
          ?>
            <tr>
            <td class='toggleDisplayId'> <?php echo (int)$singleRowMyBoard['id']; ?></td>
            <td class='toggleDisplayMacAddress' style='word-wrap: break-word;min-width: 160px;max-width: 160px;'><?php echo mds_h($singleRowMyBoard['macAddress']); ?></td>
            <td><?php echo mds_h($singleRowMyBoard['name']); ?></td>
            <td class='toggleDisplayLocation'><?php echo mds_h($singleRowMyBoard['location']); ?></td>
            <td><?php echo mds_h($singleRowMyBoard['description']); ?></td>
            <td class='toggleDisplayTtnDevId' style='word-wrap: break-word;min-width: 160px;max-width: 160px;'><?php echo mds_h($singleRowMyBoard['ttnDevId']); ?></td>
          <?php
            $sensorsOfBoard = myFunctions::getAllSensorsOfBoardOld($singleRowMyBoard['id']);
            if (is_array($sensorsOfBoard)) {
              $sensorsOfBoard = array_values(array_filter($sensorsOfBoard, function ($sensorRow) use ($userObj) {
                return myFunctions::canUserAccessSensor((int)$userObj->getId(), (int)($sensorRow['id'] ?? 0));
              }));
            }
            echo "<td>".count($sensorsOfBoard)."</td>";

            if(isset($singleRowMyBoard['alarmOnUnavailable']) && $singleRowMyBoard['alarmOnUnavailable'] == '1') {
            ?>
              <td><input type='checkbox' class='form-check-input' id='alarmOnUnavailable<?php echo (int)$singleRowMyBoard['id']; ?>' disabled name='alarmOnUnavailable' value='1' checked></td>
            <?php
            } else {
            ?>
              <td><input type='checkbox' class='form-check-input' id='alarmOnUnavailable<?php echo (int)$singleRowMyBoard['id']; ?>' disabled name='alarmOnUnavailable' value='1'></td>
            <?php
            }
            ?>
              <td>
                <?php if (myFunctions::canUserEditBoard((int)$userObj->getId(), (int)$singleRowMyBoard['id'])) { ?>
                  <a href="formBoards.php?id=<?php echo (int)$singleRowMyBoard['id']; ?>"><i class='bi bi-pencil-fill'> </i></a>
                <?php } else { ?>
                  <span class="text-muted">-</span>
                <?php } ?>
              </td>
            </tr>
            <?php
          }
          ?>
          </tbody></table>
        </div>
        <!-- Modal -->
        <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <form class="row g-3" action="?save=addNewUserToBoard" method="post" class="form-horizontal">
              <?php echo mds_csrf_input(); ?>
              <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel"><?php echo htmlspecialchars(mds_t('settings.add_new_board'), ENT_QUOTES, 'UTF-8'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <div class="input-group mb-3">
                  <select class="form-select input-group-text" aria-label="Default select example" id="valueType" name="valueType">
                    <option value="ttn" selected><?php echo htmlspecialchars(mds_t('settings.ttn_dev_id'), ENT_QUOTES, 'UTF-8'); ?></option>
                    <option value="mac"><?php echo htmlspecialchars(mds_t('settings.mac_address'), ENT_QUOTES, 'UTF-8'); ?></option>
                  </select>
                  <input type="text" class="form-control" placeholder="<?php echo htmlspecialchars(mds_t('settings.enter_value'), ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars(mds_t('common.value'), ENT_QUOTES, 'UTF-8'); ?>" aria-describedby="macAddress" name="inputValue" id="inputValue" required>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo htmlspecialchars(mds_t('common.close'), ENT_QUOTES, 'UTF-8'); ?></button>
                <button type="submit" class="btn btn-primary"><?php echo htmlspecialchars(mds_t('settings.save_changes'), ENT_QUOTES, 'UTF-8'); ?></button>
              </div>
            </form>
          </div>
        </div>
        </div>
      </div>

      <?php if ($canManageAccess) { ?>
      <div role="tabpanel" class="tab-pane" id="access">
        <div class="panel panel-default p-3">
          <h4><?php echo htmlspecialchars(mds_t('settings.access_title'), ENT_QUOTES, 'UTF-8'); ?></h4>
          <p class="text-muted"><?php echo htmlspecialchars(mds_t('settings.access_hint'), ENT_QUOTES, 'UTF-8'); ?></p>
          <?php foreach ($accessBoards as $accessBoard) { ?>
            <?php
              $accessBoardId = (int)($accessBoard['id'] ?? 0);
              $boardPermissions = $accessBoard['boardPermissions'] ?? array();
              $sensorPermissions = $accessBoard['sensorPermissions'] ?? array();
              $accessSensors = $accessBoard['sensors'] ?? array();
            ?>
            <div class="card mb-4">
              <div class="card-header">
                <strong><?php echo mds_h($accessBoard['name'] ?: ('Board #' . $accessBoardId)); ?></strong>
                <span class="text-muted ms-2"><?php echo mds_h($accessBoard['macAddress'] ?? ''); ?></span>
              </div>
              <div class="card-body">
                <div class="row g-4">
                  <div class="col-lg-6">
                    <h5><?php echo htmlspecialchars(mds_t('settings.board_access'), ENT_QUOTES, 'UTF-8'); ?></h5>
                    <form action="?save=boardAccess#access" method="post" class="row gy-2 gx-2 align-items-end">
                      <?php echo mds_csrf_input(); ?>
                      <input type="hidden" name="boardId" value="<?php echo $accessBoardId; ?>">
                      <div class="col-md-5">
                        <label class="form-label"><?php echo htmlspecialchars(mds_t('settings.user'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <select class="form-select" name="targetUserId" required>
                          <?php foreach ($allUsers as $accessUser) { ?>
                            <option value="<?php echo (int)$accessUser['id']; ?>">
                              <?php echo mds_h(trim(($accessUser['firstName'] ?? '') . ' ' . ($accessUser['lastName'] ?? '')) ?: ($accessUser['email'] ?? ('User #' . $accessUser['id']))); ?>
                            </option>
                          <?php } ?>
                        </select>
                      </div>
                      <div class="col-md-3">
                        <label class="form-label"><?php echo htmlspecialchars(mds_t('settings.role'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <select class="form-select" name="role">
                          <option value="observer"><?php echo htmlspecialchars(mds_t('settings.role_observer'), ENT_QUOTES, 'UTF-8'); ?></option>
                          <option value="user"><?php echo htmlspecialchars(mds_t('settings.role_user'), ENT_QUOTES, 'UTF-8'); ?></option>
                          <option value="owner"><?php echo htmlspecialchars(mds_t('settings.role_owner'), ENT_QUOTES, 'UTF-8'); ?></option>
                        </select>
                      </div>
                      <div class="col-md-4">
                        <button type="submit" name="permissionAction" value="save" class="btn btn-primary"><?php echo htmlspecialchars(mds_t('common.save'), ENT_QUOTES, 'UTF-8'); ?></button>
                        <button type="submit" name="permissionAction" value="remove" class="btn btn-outline-danger"><?php echo htmlspecialchars(mds_t('common.remove'), ENT_QUOTES, 'UTF-8'); ?></button>
                      </div>
                    </form>
                    <div class="table-responsive mt-3">
                      <table class="table table-sm table-striped">
                        <thead>
                          <tr>
                            <th><?php echo htmlspecialchars(mds_t('settings.user'), ENT_QUOTES, 'UTF-8'); ?></th>
                            <th><?php echo htmlspecialchars(mds_t('settings.role'), ENT_QUOTES, 'UTF-8'); ?></th>
                            <th><?php echo htmlspecialchars(mds_t('settings.permissions'), ENT_QUOTES, 'UTF-8'); ?></th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($boardPermissions as $permissionRow) { ?>
                            <tr>
                              <td><?php echo mds_h($permissionRow['email'] ?? ('User #' . ($permissionRow['userId'] ?? ''))); ?></td>
                              <td><?php echo mds_h($permissionRow['role'] ?? ''); ?></td>
                              <td>
                                <?php
                                  $permissionLabels = array();
                                  if (!empty($permissionRow['canView'])) { $permissionLabels[] = mds_t('settings.permission_view'); }
                                  if (!empty($permissionRow['canEdit'])) { $permissionLabels[] = mds_t('settings.permission_edit'); }
                                  if (!empty($permissionRow['canManageUsers'])) { $permissionLabels[] = mds_t('settings.permission_manage'); }
                                  if (!empty($permissionRow['canReceiveAlerts'])) { $permissionLabels[] = mds_t('settings.permission_alerts'); }
                                  echo htmlspecialchars(implode(', ', $permissionLabels), ENT_QUOTES, 'UTF-8');
                                ?>
                              </td>
                            </tr>
                          <?php } ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                  <div class="col-lg-6">
                    <h5><?php echo htmlspecialchars(mds_t('settings.sensor_access'), ENT_QUOTES, 'UTF-8'); ?></h5>
                    <form action="?save=sensorAccess#access" method="post" class="row gy-2 gx-2 align-items-end">
                      <?php echo mds_csrf_input(); ?>
                      <input type="hidden" name="boardId" value="<?php echo $accessBoardId; ?>">
                      <div class="col-md-4">
                        <label class="form-label"><?php echo htmlspecialchars(mds_t('common.sensor'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <select class="form-select" name="sensorId" required>
                          <?php foreach ($accessSensors as $accessSensor) { ?>
                            <option value="<?php echo (int)$accessSensor['id']; ?>"><?php echo mds_h($accessSensor['name'] ?: ('Sensor #' . $accessSensor['id'])); ?></option>
                          <?php } ?>
                        </select>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label"><?php echo htmlspecialchars(mds_t('settings.user'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <select class="form-select" name="targetUserId" required>
                          <?php foreach ($allUsers as $accessUser) { ?>
                            <option value="<?php echo (int)$accessUser['id']; ?>"><?php echo mds_h($accessUser['email'] ?? ('User #' . $accessUser['id'])); ?></option>
                          <?php } ?>
                        </select>
                      </div>
                      <div class="col-md-4">
                        <label class="form-label"><?php echo htmlspecialchars(mds_t('settings.role'), ENT_QUOTES, 'UTF-8'); ?></label>
                        <select class="form-select" name="role">
                          <option value="observer"><?php echo htmlspecialchars(mds_t('settings.role_observer'), ENT_QUOTES, 'UTF-8'); ?></option>
                          <option value="user"><?php echo htmlspecialchars(mds_t('settings.role_user'), ENT_QUOTES, 'UTF-8'); ?></option>
                        </select>
                      </div>
                      <div class="col-12">
                        <button type="submit" name="permissionAction" value="save" class="btn btn-primary"><?php echo htmlspecialchars(mds_t('common.save'), ENT_QUOTES, 'UTF-8'); ?></button>
                        <button type="submit" name="permissionAction" value="remove" class="btn btn-outline-danger"><?php echo htmlspecialchars(mds_t('common.remove'), ENT_QUOTES, 'UTF-8'); ?></button>
                      </div>
                    </form>
                    <div class="table-responsive mt-3">
                      <table class="table table-sm table-striped">
                        <thead>
                          <tr>
                            <th><?php echo htmlspecialchars(mds_t('common.sensor'), ENT_QUOTES, 'UTF-8'); ?></th>
                            <th><?php echo htmlspecialchars(mds_t('settings.user'), ENT_QUOTES, 'UTF-8'); ?></th>
                            <th><?php echo htmlspecialchars(mds_t('settings.role'), ENT_QUOTES, 'UTF-8'); ?></th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($sensorPermissions as $permissionRow) { ?>
                            <tr>
                              <td><?php echo mds_h($permissionRow['sensorName'] ?? ('Sensor #' . ($permissionRow['sensorId'] ?? ''))); ?></td>
                              <td><?php echo mds_h($permissionRow['email'] ?? ('User #' . ($permissionRow['userId'] ?? ''))); ?></td>
                              <td><?php echo mds_h($permissionRow['role'] ?? ''); ?></td>
                            </tr>
                          <?php } ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php } ?>
        </div>
      </div>
      <?php } ?>

      <!-- Configure the user's dashboard -->
      <div role="tabpanel" class="tab-pane" id="confDashboard">
        <div class="panel panel-default">
          <form action="?save=dashboard_data" method="post" class="form-horizontal">
            <?php echo mds_csrf_input(); ?>
            <div class="form-group">
              <div class="row">
                <label for="inputUpdateInterval" class="col-sm-2 control-label"><?php echo htmlspecialchars(mds_t('settings.update_interval'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="col-sm-4">
                  <input class="form-control" id="inputUpdateInterval" name="updateInterval" type="number" value="<?php echo htmlspecialchars((string)$userObj->getDashboardUpdateInterval(), ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                </div>
            </div>

            <div class="form-group">
              <div class="row">
                <div class="col col-sm-2"><?php echo htmlspecialchars(mds_t('settings.only_online_default'), ENT_QUOTES, 'UTF-8'); ?></div>
                <label class="col col-sm-4">
                  <input type='hidden' class='form-check-input' name='dashboardOnlineOnly' value='0'>
                  <input type='checkbox' class='form-check-input' id='dashboardOnlineOnly' name='dashboardOnlineOnly' value='1' <?php if ((int)$userObj->getDashboardOnlineOnly() === 1) { echo 'checked'; } ?>>
                </label>
              </div>
            </div>

            <div class="form-group">
              <div class="row">
                <label for="preferredChartWindowDays" class="col-sm-2 control-label"><?php echo htmlspecialchars(mds_t('settings.default_chart_range'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="col-sm-4">
                  <?php $preferredChartWindowDays = (int)$userObj->getPreferredChartWindowDays(); ?>
                  <select class="form-select" id="preferredChartWindowDays" name="preferredChartWindowDays">
                    <option value="1" <?php if ($preferredChartWindowDays === 1) { echo 'selected'; } ?>><?php echo htmlspecialchars(mds_t('settings.last_24_hours'), ENT_QUOTES, 'UTF-8'); ?></option>
                    <option value="7" <?php if ($preferredChartWindowDays === 7) { echo 'selected'; } ?>><?php echo htmlspecialchars(mds_t('settings.last_days', array(7)), ENT_QUOTES, 'UTF-8'); ?></option>
                    <option value="14" <?php if ($preferredChartWindowDays === 14) { echo 'selected'; } ?>><?php echo htmlspecialchars(mds_t('settings.last_days', array(14)), ENT_QUOTES, 'UTF-8'); ?></option>
                    <option value="30" <?php if ($preferredChartWindowDays === 30) { echo 'selected'; } ?>><?php echo htmlspecialchars(mds_t('settings.last_days', array(30)), ENT_QUOTES, 'UTF-8'); ?></option>
                  </select>
                </div>
              </div>
            </div>

            <div class="form-group">
              <div class="row">
                <label for="eventTimelineWindowHours" class="col-sm-2 control-label"><?php echo htmlspecialchars(mds_t('settings.event_timeline_window'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="col-sm-4">
                  <?php $eventTimelineWindowHours = (int)$userObj->getEventTimelineWindowHours(); ?>
                  <select class="form-select" id="eventTimelineWindowHours" name="eventTimelineWindowHours">
                    <?php foreach (array(3, 6, 12, 24, 48, 72) as $timelineWindowOption) { ?>
                      <option value="<?php echo $timelineWindowOption; ?>" <?php if ($eventTimelineWindowHours === $timelineWindowOption) { echo 'selected'; } ?>>
                        <?php echo htmlspecialchars(mds_t('settings.last_hours', array($timelineWindowOption)), ENT_QUOTES, 'UTF-8'); ?>
                      </option>
                    <?php } ?>
                  </select>
                  <div class="form-text"><?php echo htmlspecialchars(mds_t('settings.event_timeline_window_hint'), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
              </div>
            </div>

            <div class="form-group">
              <div class="row">
                <div class="col-sm-offset-2 col-sm-10">
                <button type="submit" class="btn btn-primary"><?php echo htmlspecialchars(mds_t('common.save'), ENT_QUOTES, 'UTF-8'); ?></button>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>

      <div role="tabpanel" class="tab-pane" id="privacy">
        <div class="panel panel-default p-3">
          <h4 class="mb-3"><?php echo htmlspecialchars(mds_t('settings.privacy_tools'), ENT_QUOTES, 'UTF-8'); ?></h4>
          <p class="text-muted">
            <?php echo htmlspecialchars(mds_t('settings.privacy_review_text'), ENT_QUOTES, 'UTF-8'); ?>
          </p>
          <div class="d-flex flex-wrap gap-2">
            <a href="<?php echo htmlspecialchars(mds_route_path('privacy.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-secondary"><?php echo htmlspecialchars(mds_t('settings.open_privacy_page'), ENT_QUOTES, 'UTF-8'); ?></a>
            <a href="<?php echo htmlspecialchars(mds_route_path('privacy_export.php'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary"><?php echo htmlspecialchars(mds_t('settings.download_my_data'), ENT_QUOTES, 'UTF-8'); ?></a>
          </div>
          <p class="small text-muted mt-3 mb-0">
            <?php echo htmlspecialchars(mds_t('settings.privacy_export_text'), ENT_QUOTES, 'UTF-8'); ?>
          </p>
          <p class="small text-muted mt-2 mb-0">
            <?php echo htmlspecialchars(mds_t('settings.privacy_contact_text'), ENT_QUOTES, 'UTF-8'); ?>
            <?php echo htmlspecialchars((string)($config::$privacyContactEmail ?: $config::$adminEmailAddress ?: $config::$systemEmailAddress ?: mds_t('settings.not_configured')), ENT_QUOTES, 'UTF-8'); ?>
          </p>
        </div>
      </div>

      <div role="tabpanel" class="tab-pane" id="allBoards">
      <form action="?save=allBoards" method="post" class="form-horizontal">
        <?php echo mds_csrf_input(); ?>
        <div class="container-fluid border mb-2">
        <span><?php echo htmlspecialchars(mds_t('settings.toggle_columns'), ENT_QUOTES, 'UTF-8'); ?></span>
          <div class="form-check form-switch d-inline-block pe-4">
            <label for="inlineCheckbox1" class="form-check-label">id</label>
            <input id="inlineCheckbox1" value="toggleDisplayId" class="form-check-input myToggleButton" type="checkbox" checked data-size="small">
          </div>
          <div class="form-check form-switch d-inline-block pe-4">
            <label for="inlineCheckbox2" class="form-check-label"><?php echo htmlspecialchars(mds_t('settings.mac_address'), ENT_QUOTES, 'UTF-8'); ?></label>
            <input id="inlineCheckbox2" value="toggleDisplayMacAddress" class="form-check-input myToggleButton" type="checkbox" checked data-size="small">
          </div>

          <div class="form-check form-switch d-inline-block pe-4">
            <label for="inlineCheckbox3" class="form-check-label"><?php echo htmlspecialchars(mds_t('common.location'), ENT_QUOTES, 'UTF-8'); ?></label>
            <input id="inlineCheckbox3" value="toggleDisplayLocation" class="form-check-input myToggleButton" type="checkbox" checked data-size="small">
          </div>

          <div class="form-check form-switch d-inline-block pe-4">
            <label for="inlineCheckbox4" class="form-check-label"><?php echo htmlspecialchars(mds_t('settings.ttn_app_id'), ENT_QUOTES, 'UTF-8'); ?></label>
            <input id="inlineCheckbox4" value="toggleDisplayTtnAppId" class="form-check-input myToggleButton" type="checkbox" checked data-size="small">
          </div>
          <div class="form-check form-switch d-inline-block pe-4">
            <label for="inlineCheckbox5" class="form-check-label"><?php echo htmlspecialchars(mds_t('settings.ttn_dev_id'), ENT_QUOTES, 'UTF-8'); ?></label>
            <input id="inlineCheckbox5" value="toggleDisplayTtnDevId" class="form-check-input myToggleButton" type="checkbox" checked data-size="small">
          </div>

        </div>
        <div class="panel panel-default table-responsive">
          <table class="table table-bordered">
          <thead>
          <tr>
            <th class='toggleDisplayId'>id</th>
            <th class='toggleDisplayMacAddress '><div><span><?php echo htmlspecialchars(mds_t('settings.mac_address'), ENT_QUOTES, 'UTF-8'); ?></span></div></th>
            <th><div><span><?php echo htmlspecialchars(mds_t('settings.owner_user'), ENT_QUOTES, 'UTF-8'); ?></span></div></th>
            <th><?php echo htmlspecialchars(mds_t('common.name'), ENT_QUOTES, 'UTF-8'); ?></th>
            <th class="toggleDisplayLocation"><?php echo htmlspecialchars(mds_t('common.location'), ENT_QUOTES, 'UTF-8'); ?></th>
            <th><?php echo htmlspecialchars(mds_t('common.description'), ENT_QUOTES, 'UTF-8'); ?></th>
            <th class="toggleDisplayTtnAppId"><?php echo htmlspecialchars(mds_t('settings.ttn_app_id'), ENT_QUOTES, 'UTF-8'); ?></th>
            <th class="toggleDisplayTtnDevId"><?php echo htmlspecialchars(mds_t('settings.ttn_dev_id'), ENT_QUOTES, 'UTF-8'); ?></th>
            <th class='rotated-text'><div><span><?php echo htmlspecialchars(mds_t('common.sensors'), ENT_QUOTES, 'UTF-8'); ?></span></div></th>
            <th class='rotated-text'><div><span><?php echo htmlspecialchars(mds_t('form.board.alarm_unavailable'), ENT_QUOTES, 'UTF-8'); ?></span></div></th>
            <th class='rotated-text'><div><span><?php echo htmlspecialchars(mds_t('common.details'), ENT_QUOTES, 'UTF-8'); ?></span></div></th>
          </tr>
          </thead>
          <tbody>
          <?php
          $countBoardRow = 1;
          foreach($allBoards as $singleRowMyBoard) {
          ?>
            <tr>
            <td class='toggleDisplayId'> <?php echo (int)$singleRowMyBoard['id']; ?></td>
            <td class='toggleDisplayMacAddress' style='word-wrap: break-word;min-width: 160px;max-width: 160px;'><?php echo mds_h($singleRowMyBoard['macAddress']); ?></td>
            <td><?php echo mds_h($singleRowMyBoard['ownerUserId']); ?></td>

            <td><?php echo mds_h($singleRowMyBoard['name']); ?></td>
            <td class='toggleDisplayLocation'><?php echo mds_h($singleRowMyBoard['location']); ?></td>
            <td><?php echo mds_h($singleRowMyBoard['description']); ?></td>
            <td class='toggleDisplayTtnAppId' style='word-wrap: break-word;min-width: 160px;max-width: 160px;'><?php echo mds_h($singleRowMyBoard['ttnAppId']); ?></td>
            <td class='toggleDisplayTtnDevId' style='word-wrap: break-word;min-width: 160px;max-width: 160px;'><?php echo mds_h($singleRowMyBoard['ttnDevId']); ?></td>
          <?php
            $sensorsOfBoard = myFunctions::getAllSensorsOfBoardOld($singleRowMyBoard['id']);
            echo "<td>".count($sensorsOfBoard)."</td>";

            if(isset($singleRowMyBoard['alarmOnUnavailable']) && $singleRowMyBoard['alarmOnUnavailable'] == '1') {
            ?>
              <td><input type='checkbox' class='form-check-input' id='alarmOnUnavailable<?php echo (int)$singleRowMyBoard['id']; ?>' disabled name='alarmOnUnavailable' value='1' checked></td>
            <?php
            } else {
            ?>
              <td><input type='checkbox' class='form-check-input' id='alarmOnUnavailable<?php echo (int)$singleRowMyBoard['id']; ?>' disabled name='alarmOnUnavailable' value='1'></td>
            <?php
            }
            ?>
              <td><a href="formBoards.php?id=<?php echo (int)$singleRowMyBoard['id']; ?>"><i class='bi bi-pencil-fill'> </i></a></td>
            </tr>
            <?php
          }
          ?>
          </tbody></table>
        </div>
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
            <button type="submit" class="btn btn-primary"><?php echo htmlspecialchars(mds_t('common.save'), ENT_QUOTES, 'UTF-8'); ?></button>
            </div>
          </div>
        </form>
      </div>


      <!-- Modification of other users -->
      <div role="tabpanel" class="tab-pane" id="users">
        <p style="margin-bottom: 0px; margin-top: 1rem;"><?php echo htmlspecialchars(mds_t('settings.users_hint'), ENT_QUOTES, 'UTF-8'); ?></p>
        <form action="?save=users" method="post" class="form-horizontal">
          <?php echo mds_csrf_input(); ?>
          <div class="panel panel-default">
          <table class="table">
          <tr>
            <th>#</th><th><?php echo htmlspecialchars(mds_t('common.active'), ENT_QUOTES, 'UTF-8'); ?></th><th><?php echo htmlspecialchars(mds_t('settings.first_name'), ENT_QUOTES, 'UTF-8'); ?></th><th><?php echo htmlspecialchars(mds_t('settings.last_name'), ENT_QUOTES, 'UTF-8'); ?></th><th><?php echo htmlspecialchars(mds_t('common.email'), ENT_QUOTES, 'UTF-8'); ?></th><th><?php echo htmlspecialchars(mds_t('common.admin'), ENT_QUOTES, 'UTF-8'); ?></th>
          </tr>
          <?php
          if($isAdmin) {
            $count = 1;
            foreach($allUsers as $singleRowUser) {
              ?>
              <tr>
                <td><?php echo $count++ ?></td>
                <?php
                if(isset($singleRowUser['active']) && $singleRowUser['active'] == '1')
                {
                ?>
                  <td><input type='hidden' class='form-check-input' id='active<?php echo (int)$singleRowUser['id']; ?>' name='active[<?php echo (int)$singleRowUser['id']; ?>]' value='0'>
                  <input type='checkbox' class='form-check-input' id='active<?php echo (int)$singleRowUser['id']; ?>' name='active[<?php echo (int)$singleRowUser['id']; ?>]' value='1' checked></td>
                <?php
                }
                else
                {
                ?>
                  <td><input type='hidden' class='form-check-input' id='active<?php echo (int)$singleRowUser['id']; ?>' name='active[<?php echo (int)$singleRowUser['id']; ?>]' value='0'>
                  <input type='checkbox' class='form-check-input' id='active<?php echo (int)$singleRowUser['id']; ?>' name='active[<?php echo (int)$singleRowUser['id']; ?>]' value='1'></td>
                <?php
                }
                ?>
                <td><?php echo mds_h($singleRowUser['firstName']); ?></td>
                <td><?php echo mds_h($singleRowUser['lastName']); ?></td>
                <td><a href="mailto:<?php echo mds_h($singleRowUser['email']); ?>"><?php echo mds_h($singleRowUser['email']); ?></a></td>
                <?php
                if(isset($singleRowUser['userGroupAdmin']) && $singleRowUser['userGroupAdmin'] == '1')
                {
                ?>
                  <td><input type='hidden' class='form-check-input' id='userGroupAdmin<?php echo (int)$singleRowUser['id']; ?>' name='userGroupAdmin[<?php echo (int)$singleRowUser['id']; ?>]' value='0'>
                  <input type='checkbox' class='form-check-input' id='userGroupAdmin<?php echo (int)$singleRowUser['id']; ?>' name='userGroupAdmin[<?php echo (int)$singleRowUser['id']; ?>]' value='1' checked></td>
                <?php
                }
                else
                {
                ?>
                  <td><input type='hidden' class='form-check-input' id='userGroupAdmin<?php echo (int)$singleRowUser['id']; ?>' name='userGroupAdmin[<?php echo (int)$singleRowUser['id']; ?>]' value='0'>
                  <input type='checkbox' class='form-check-input' id='userGroupAdmin<?php echo (int)$singleRowUser['id']; ?>' name='userGroupAdmin[<?php echo (int)$singleRowUser['id']; ?>]' value='1'></td>
                <?php
                }
                ?>
              </tr>
              <?php
            }
          }
          ?>
          </table>
          </div>
          <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
            <button type="submit" class="btn btn-primary"><?php echo htmlspecialchars(mds_t('common.save'), ENT_QUOTES, 'UTF-8'); ?></button>
            </div>
          </div>
        </form>
      </div>

      <!-- Modification of Server Setting -->
      <div role="tabpanel" class="tab-pane" id="serverSetting">
        <form action="?save=serverSetting" method="post" class="form-horizontal">
          <?php echo mds_csrf_input(); ?>
          <div class="panel panel-default">
            <div class="form-group">
              <div class="row">
            <?php
              if ($varDemoMode) {
              ?>
                <div class="col col-sm-2">
                  <?php echo htmlspecialchars(mds_t('settings.demo_mode'), ENT_QUOTES, 'UTF-8'); ?></div>
                <label class="col col-sm-4">
                    <input type='hidden' class='form-check-input' id='demoMode' name='demoMode' value='0'>
                    <input type='checkbox' class='form-check-input' id='demoMode' name='demoMode' checked=true value='1'>
                </label>
              <?php
              } else {
              ?>
                <div class="col col-sm-2">
                  <?php echo htmlspecialchars(mds_t('settings.demo_mode'), ENT_QUOTES, 'UTF-8'); ?></div>
                <label class="col col-sm-4">
                    <input type='hidden' class='form-check-input' id='demoMode' name='demoMode' checked=true value='0'>
                    <input type='checkbox' class='form-check-input' id='demoMode' name='demoMode' value='1'>
                </label>
              <?php
              }
            ?>
            </div>
            </div>
          </div>
          <div class="panel panel-default">
            <div class="form-group">
              <div class="row">
            <?php
              if ($varShowQrCode) {
              ?>
                <div class="col col-sm-2">
                  <?php echo htmlspecialchars(mds_t('settings.show_qr_code'), ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <label class="col col-sm-4">
                    <input type='hidden' class='form-check-input' id='ShowQrCode' name='ShowQrCode' value='0'>
                    <input type='checkbox' class='form-check-input' id='ShowQrCode' name='ShowQrCode' checked=true value='1'>
                </label>
              <?php
              } else {
              ?>
                <div class="col col-sm-2">
                  <?php echo htmlspecialchars(mds_t('settings.show_qr_code'), ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <label class="col col-sm-4">
                    <input type='hidden' class='form-check-input' id='ShowQrCode' name='ShowQrCode' checked=true value='0'>
                    <input type='checkbox' class='form-check-input' id='ShowQrCode' name='ShowQrCode' value='1'>
                </label>
              <?php
              }
            ?>
            </div>
            </div>
          </div>

          <div class="panel panel-default">
            <div class="form-group">
              <div class="row">
                <label for="apiKey" class="col col-sm-2 control-label">API Key:</label>
                <div class="col col-sm-4">
                  <input class="form-control" id="apiKey" name="apiKey" type="text" value="<?php echo mds_h($var_apiKey); ?>" required>
                </div>
              </div>
            </div>
          </div>

          <div class="panel panel-default">
            <div class="form-group">
              <div class="row">
                <label for="otaUpdateSecret" class="col col-sm-2 control-label"><?php echo htmlspecialchars(mds_t('settings.ota_update_secret'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="col col-sm-4">
                  <input class="form-control" id="otaUpdateSecret" name="otaUpdateSecret" type="password" autocomplete="new-password" value="<?php echo mds_h($varOtaUpdateSecret); ?>">
                  <div class="form-text"><?php echo htmlspecialchars(mds_t('settings.ota_update_secret_help'), ENT_QUOTES, 'UTF-8'); ?></div>
                  <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" id="showOtaUpdateSecret" onclick="document.getElementById('otaUpdateSecret').type = this.checked ? 'text' : 'password';">
                    <label class="form-check-label" for="showOtaUpdateSecret"><?php echo htmlspecialchars(mds_t('settings.show_secret'), ENT_QUOTES, 'UTF-8'); ?></label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="panel panel-default">
            <div class="form-group">
              <div class="row">

              <?php
              if ($varSend_emails) {
              ?>
                <div class="col col-sm-2">
                <?php echo htmlspecialchars(mds_t('settings.send_emails'), ENT_QUOTES, 'UTF-8'); ?>:
                </div>
                <label class="col col-sm-4">
                    <input type='hidden' class='form-check-input' id='sendEmails' name='sendEmails' value='0'>
                    <input type='checkbox' class='form-check-input' id='sendEmails' name='sendEmails' checked=true value='1'>
                </label>
              <?php
              } else {
              ?>
                <div class="col col-sm-2">
                <?php echo htmlspecialchars(mds_t('settings.send_emails'), ENT_QUOTES, 'UTF-8'); ?>:
                </div>
                <label class="col col-sm-4">
                    <input type='hidden' class='form-check-input' id='sendEmails' name='sendEmails' checked=true value='0'>
                    <input type='checkbox' class='form-check-input' id='sendEmails' name='sendEmails' value='1'>
                </label>
              <?php
              }
            ?>
            </div>
            </div>
          </div>

          <div class="panel panel-default">
            <div class="form-group">
              <div class="row">
                <label for="systemEmailAddress" class="col col-sm-2 control-label"><?php echo htmlspecialchars(mds_t('settings.system_email_sender'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="col col-sm-4">
                  <input class="form-control" id="systemEmailAddress" name="systemEmailAddress" type="text" value="<?php echo $config::$systemEmailAddress; ?>" required>
                </div>
              </div>
            </div>
          </div>

          <div class="panel panel-default">
            <div class="form-group">
              <div class="row">
                <label for="applicationName" class="col col-sm-2 control-label"><?php echo htmlspecialchars(mds_t('settings.application_name'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="col col-sm-4">
                  <input class="form-control" id="applicationName" name="applicationName" type="text" value="<?php echo $config::$applicationName; ?>" required>
                </div>
              </div>
            </div>
          </div>

          <div class="panel panel-default">
            <div class="form-group">
              <div class="row">
                <label for="googleSiteVerification" class="col col-sm-2 control-label">Google Search Console:</label>
                <div class="col col-sm-4">
                  <input class="form-control" id="googleSiteVerification" name="googleSiteVerification" type="text" value="<?php echo mds_h($varGoogleSiteVerification); ?>" placeholder="google-site-verification token">
                  <div class="form-text"><?php echo mds_h(mds_current_language() === 'de' ? 'Nur den content-Wert des Google-Meta-Tags eintragen.' : 'Enter only the content value of the Google verification meta tag.'); ?></div>
                </div>
              </div>
            </div>
          </div>

          <div class="panel panel-default">
            <div class="form-group">
              <div class="row">
                <label for="privacyContactEmail" class="col col-sm-2 control-label"><?php echo htmlspecialchars(mds_t('settings.privacy_contact_email'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="col col-sm-4">
                  <input class="form-control" id="privacyContactEmail" name="privacyContactEmail" type="email" value="<?php echo htmlspecialchars((string)$config::$privacyContactEmail, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
              </div>
            </div>
          </div>

          <div class="panel panel-default">
            <div class="form-group">
              <div class="row">
                <label for="imprintCompanyName" class="col col-sm-2 control-label"><?php echo htmlspecialchars(mds_t('settings.imprint_company'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="col col-sm-4">
                  <input class="form-control" id="imprintCompanyName" name="imprintCompanyName" type="text" value="<?php echo htmlspecialchars((string)$config::$imprintCompanyName, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
              </div>
            </div>
          </div>

          <div class="panel panel-default">
            <div class="form-group">
              <div class="row">
                <label for="imprintAddress" class="col col-sm-2 control-label"><?php echo htmlspecialchars(mds_t('settings.imprint_address'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="col col-sm-4">
                  <textarea class="form-control" id="imprintAddress" name="imprintAddress" rows="3"><?php echo htmlspecialchars((string)$config::$imprintAddress, ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>
              </div>
            </div>
          </div>

          <div class="panel panel-default">
            <div class="form-group">
              <div class="row">
                <label for="imprintEmail" class="col col-sm-2 control-label"><?php echo htmlspecialchars(mds_t('settings.imprint_email'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="col col-sm-4">
                  <input class="form-control" id="imprintEmail" name="imprintEmail" type="email" value="<?php echo htmlspecialchars((string)$config::$imprintEmail, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
              </div>
            </div>
          </div>

          <div class="panel panel-default">
            <div class="form-group">
              <div class="row">
                <label for="imprintPhone" class="col col-sm-2 control-label"><?php echo htmlspecialchars(mds_t('settings.imprint_phone'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="col col-sm-4">
                  <input class="form-control" id="imprintPhone" name="imprintPhone" type="text" value="<?php echo htmlspecialchars((string)$config::$imprintPhone, ENT_QUOTES, 'UTF-8'); ?>">
                </div>
              </div>
            </div>
          </div>

          <div class="panel panel-default">
            <div class="form-group">
              <div class="row">
                <label for="defaultGaugeStyle" class="col col-sm-2 control-label"><?php echo htmlspecialchars(mds_t('settings.default_gauge_style'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="col col-sm-4">
                  <select class="form-select" id="defaultGaugeStyle" name="defaultGaugeStyle">
                    <?php
                      $defaultGaugeStyles = array(
                        'classic' => 'Classic',
                        'minimal' => 'Minimal',
                        'bold' => 'Bold',
                        'arc' => 'Arc',
                        'ring' => 'Ring',
                        'clock' => 'Clock',
                        'industrial' => 'Industrial'
                      );
                      $selectedDefaultGaugeStyle = $config::$defaultGaugeStyle ?? 'classic';
                      foreach ($defaultGaugeStyles as $defaultGaugeStyleValue => $defaultGaugeStyleLabel) {
                        $selected = $selectedDefaultGaugeStyle === $defaultGaugeStyleValue ? 'selected' : '';
                        echo "<option value='" . htmlspecialchars($defaultGaugeStyleValue, ENT_QUOTES, 'UTF-8') . "' $selected>" . htmlspecialchars($defaultGaugeStyleLabel, ENT_QUOTES, 'UTF-8') . "</option>";
                      }
                    ?>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <div class="panel panel-default">
            <div class="form-group">
              <div class="row">
                <div class="col col-sm-2">
                  <?php echo htmlspecialchars(mds_t('settings.dashboard_default'), ENT_QUOTES, 'UTF-8'); ?>
                </div>
                <label class="col col-sm-4">
                    <input type='hidden' class='form-check-input' id='defaultDashboardOnlineOnly' name='defaultDashboardOnlineOnly' value='0'>
                    <input type='checkbox' class='form-check-input' id='defaultDashboardOnlineOnly' name='defaultDashboardOnlineOnly' value='1' <?php if ((string)$config::$defaultDashboardOnlineOnly === '1') { echo 'checked'; } ?>>
                    <span class="ms-2"><?php echo htmlspecialchars(mds_t('settings.only_online_default'), ENT_QUOTES, 'UTF-8'); ?></span>
                </label>
              </div>
            </div>
          </div>

          <div class="panel panel-default">
            <div class="form-group">
              <div class="row">
                <label for="defaultChartWindowDays" class="col col-sm-2 control-label"><?php echo htmlspecialchars(mds_t('settings.default_chart_range'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                <div class="col col-sm-4">
                  <select class="form-select" id="defaultChartWindowDays" name="defaultChartWindowDays">
                    <?php $selectedDefaultChartWindowDays = (string)($config::$defaultChartWindowDays ?? '7'); ?>
                    <option value="1" <?php if ($selectedDefaultChartWindowDays === '1') { echo 'selected'; } ?>><?php echo htmlspecialchars(mds_t('settings.last_24_hours'), ENT_QUOTES, 'UTF-8'); ?></option>
                    <option value="7" <?php if ($selectedDefaultChartWindowDays === '7') { echo 'selected'; } ?>><?php echo htmlspecialchars(mds_t('settings.last_days', array(7)), ENT_QUOTES, 'UTF-8'); ?></option>
                    <option value="14" <?php if ($selectedDefaultChartWindowDays === '14') { echo 'selected'; } ?>><?php echo htmlspecialchars(mds_t('settings.last_days', array(14)), ENT_QUOTES, 'UTF-8'); ?></option>
                    <option value="30" <?php if ($selectedDefaultChartWindowDays === '30') { echo 'selected'; } ?>><?php echo htmlspecialchars(mds_t('settings.last_days', array(30)), ENT_QUOTES, 'UTF-8'); ?></option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <div class="panel panel-default">
            <div class="form-group">
              <div class="row">
                <label for="logRetentionDays" class="col col-sm-2 control-label"><?php echo htmlspecialchars(mds_t('settings.log_retention_days'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="col col-sm-4">
                  <input class="form-control" id="logRetentionDays" name="logRetentionDays" type="number" min="1" value="<?php echo htmlspecialchars((string)($config::$logRetentionDays ?? '90'), ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
              </div>
            </div>
          </div>

          <div class="panel panel-default">
            <div class="form-group">
              <div class="row">
                <label for="passwordResetRetentionDays" class="col col-sm-2 control-label"><?php echo htmlspecialchars(mds_t('settings.reset_code_retention_days'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="col col-sm-4">
                  <input class="form-control" id="passwordResetRetentionDays" name="passwordResetRetentionDays" type="number" min="1" value="<?php echo htmlspecialchars((string)($config::$passwordResetRetentionDays ?? '30'), ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
              </div>
            </div>
          </div>

          <div class="panel panel-default">
            <div class="form-group">
              <div class="row">
                <label for="telemetryRetentionDays" class="col col-sm-2 control-label"><?php echo htmlspecialchars(mds_t('settings.telemetry_retention_days'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="col col-sm-4">
                  <input class="form-control" id="telemetryRetentionDays" name="telemetryRetentionDays" type="number" min="1" value="<?php echo htmlspecialchars((string)($config::$telemetryRetentionDays ?? '365'), ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
              </div>
            </div>
          </div>

          <div class="panel panel-default">
            <div class="form-group">
              <div class="row">
                <label for="gpsRetentionDays" class="col col-sm-2 control-label"><?php echo htmlspecialchars(mds_t('settings.gps_retention_days'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="col col-sm-4">
                  <input class="form-control" id="gpsRetentionDays" name="gpsRetentionDays" type="number" min="1" value="<?php echo htmlspecialchars((string)($config::$gpsRetentionDays ?? '90'), ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
              </div>
            </div>
          </div>

          <div class="panel panel-default">
            <div class="form-group">
              <div class="row">
                <label for="ttnDebugRetentionDays" class="col col-sm-2 control-label"><?php echo htmlspecialchars(mds_t('settings.ttn_debug_retention_days'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="col col-sm-4">
                  <input class="form-control" id="ttnDebugRetentionDays" name="ttnDebugRetentionDays" type="number" min="1" value="<?php echo htmlspecialchars((string)($config::$ttnDebugRetentionDays ?? '30'), ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
              </div>
            </div>
          </div>

          <div class="panel panel-default">
            <div class="form-group">
              <div class="row">
                <label for="securityTokenRetentionDays" class="col col-sm-2 control-label"><?php echo htmlspecialchars(mds_t('settings.security_token_retention_days'), ENT_QUOTES, 'UTF-8'); ?></label>
                <div class="col col-sm-4">
                  <input class="form-control" id="securityTokenRetentionDays" name="securityTokenRetentionDays" type="number" min="1" value="<?php echo htmlspecialchars((string)($config::$securityTokenRetentionDays ?? '45'), ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
              </div>
            </div>
          </div>

          <div class="form-group">
            <div class="col col-sm-offset-2 col-sm-10">
              <button type="submit" class="btn btn-primary"><?php echo htmlspecialchars(mds_t('common.save'), ENT_QUOTES, 'UTF-8'); ?></button>
              <button type="submit" class="btn btn-outline-secondary ms-2" formaction="?save=testMailSystem"><?php echo htmlspecialchars(mds_t('settings.system_test_mail'), ENT_QUOTES, 'UTF-8'); ?></button>
            </div>
          </div>
        </form>
      </div>

      <div role="tabpanel" class="tab-pane" id="migration">
        <?php if (!empty($migrationStatus)) { ?>
          <div class="card mb-3 shadow-sm">
            <div class="card-body">
              <div class="d-flex flex-wrap justify-content-between gap-2 align-items-start mb-3">
                <div>
                  <h5 class="card-title mb-1"><?php echo htmlspecialchars(mds_t('settings.migration_status'), ENT_QUOTES, 'UTF-8'); ?></h5>
                  <div class="text-muted small"><?php echo htmlspecialchars(mds_t('settings.migration_status_text'), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
                <?php
                  $hasOpenMigrationActions = false;
                  foreach ($migrationStatus as $migrationStatusButtonRow) {
                    if (empty($migrationStatusButtonRow['applied'])) {
                      $hasOpenMigrationActions = true;
                      break;
                    }
                  }
                ?>
                <form action="?save=runAutomaticMigrations" method="post" class="m-0">
                  <?php echo mds_csrf_input(); ?>
                  <button type="submit" class="btn btn-primary btn-sm" <?php if (!$hasOpenMigrationActions) { echo 'disabled'; } ?>>
                    <?php echo htmlspecialchars(mds_t('settings.migration_run_actions'), ENT_QUOTES, 'UTF-8'); ?>
                  </button>
                </form>
              </div>
              <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                  <thead>
                    <tr>
                      <th><?php echo htmlspecialchars(mds_t('common.status'), ENT_QUOTES, 'UTF-8'); ?></th>
                      <th><?php echo htmlspecialchars(mds_t('common.description'), ENT_QUOTES, 'UTF-8'); ?></th>
                      <th><?php echo htmlspecialchars(mds_t('settings.migration_file'), ENT_QUOTES, 'UTF-8'); ?></th>
                      <th><?php echo htmlspecialchars(mds_t('settings.migration_missing_checks'), ENT_QUOTES, 'UTF-8'); ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($migrationStatus as $migrationStatusRow) { ?>
                      <tr>
                        <td>
                          <?php if (!empty($migrationStatusRow['applied'])) { ?>
                            <span class="badge bg-success"><?php echo htmlspecialchars(mds_t('settings.migration_applied'), ENT_QUOTES, 'UTF-8'); ?></span>
                          <?php } else { ?>
                            <span class="badge bg-warning text-dark"><?php echo htmlspecialchars(mds_t('settings.migration_missing'), ENT_QUOTES, 'UTF-8'); ?></span>
                          <?php } ?>
                        </td>
                        <td><?php echo htmlspecialchars((string)$migrationStatusRow['label'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><code><?php echo htmlspecialchars((string)$migrationStatusRow['file'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                        <td>
                          <?php
                            $missingChecks = $migrationStatusRow['missingChecks'] ?? array();
                            echo htmlspecialchars(empty($missingChecks) ? '-' : implode(', ', $missingChecks), ENT_QUOTES, 'UTF-8');
                          ?>
                        </td>
                      </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        <?php } ?>
      </div>

      <!-- Modification of Log -->
      <div role="tabpanel" class="tab-pane" id="log">
        <div class="card mb-3 shadow-sm">
          <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between gap-2 align-items-start mb-3">
              <div>
                <h5 class="card-title mb-1"><?php echo htmlspecialchars(mds_t('settings.ota_update_log'), ENT_QUOTES, 'UTF-8'); ?></h5>
                <div class="text-muted small"><?php echo htmlspecialchars(mds_t('settings.ota_update_log_text'), ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
              <?php if (!empty($otaUpdateLogs['path'])) { ?>
                <code class="small"><?php echo htmlspecialchars((string)$otaUpdateLogs['path'], ENT_QUOTES, 'UTF-8'); ?></code>
              <?php } ?>
            </div>
            <?php if (!empty($otaUpdateLogs['entries'])) { ?>
              <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                  <thead>
                    <tr>
                      <th><?php echo htmlspecialchars(mds_t('common.status'), ENT_QUOTES, 'UTF-8'); ?></th>
                      <th><?php echo htmlspecialchars(mds_t('settings.ota_time'), ENT_QUOTES, 'UTF-8'); ?></th>
                      <th><?php echo htmlspecialchars(mds_t('settings.ota_request'), ENT_QUOTES, 'UTF-8'); ?></th>
                      <th><?php echo htmlspecialchars(mds_t('settings.ota_message'), ENT_QUOTES, 'UTF-8'); ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($otaUpdateLogs['entries'] as $otaLogEntry) { ?>
                      <?php
                        $otaStatus = (string)($otaLogEntry['status'] ?? 'info');
                        $otaStatusClasses = array(
                          'sent' => 'bg-success',
                          'current' => 'bg-secondary',
                          'disabled' => 'bg-warning text-dark',
                          'rejected' => 'bg-danger',
                          'missing' => 'bg-danger',
                          'checked' => 'bg-info text-dark',
                          'info' => 'bg-secondary',
                        );
                        $otaStatusClass = $otaStatusClasses[$otaStatus] ?? 'bg-secondary';
                      ?>
                      <tr>
                        <td><span class="badge <?php echo htmlspecialchars($otaStatusClass, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(mds_t('settings.ota_status_' . $otaStatus), ENT_QUOTES, 'UTF-8'); ?></span></td>
                        <td><?php echo htmlspecialchars((string)($otaLogEntry['datetime'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><code><?php echo htmlspecialchars((string)($otaLogEntry['request'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></code></td>
                        <td><?php echo htmlspecialchars((string)($otaLogEntry['message'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                      </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>
            <?php } else { ?>
              <div class="alert alert-secondary mb-0">
                <?php echo htmlspecialchars((string)($otaUpdateLogs['message'] ?? mds_t('settings.ota_no_logs')), ENT_QUOTES, 'UTF-8'); ?>
              </div>
            <?php } ?>
          </div>
        </div>
        <div class="panel panel-default p-2"><?php echo htmlspecialchars(mds_t('settings.log_hint'), ENT_QUOTES, 'UTF-8'); ?></div>
        <div class="panel panel-default p-2">
          <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
            <span class="text-muted small"><?php echo htmlspecialchars(mds_t('settings.log_filter'), ENT_QUOTES, 'UTF-8'); ?></span>
            <div class="btn-group btn-group-sm flex-wrap" role="group" aria-label="<?php echo htmlspecialchars(mds_t('settings.log_filter'), ENT_QUOTES, 'UTF-8'); ?>">
              <?php
                $logLevelFilters = array(
                  'ALL' => mds_t('settings.log_filter_all'),
                  'INFO' => 'Info',
                  'WARNING' => 'Warning',
                  'ERROR' => 'Error',
                  'EXCEPTION' => 'Exception',
                  'DEBUG' => 'Debug',
                );
                foreach ($logLevelFilters as $logLevelValue => $logLevelLabel) {
                  $inputId = 'log-filter-' . strtolower($logLevelValue);
              ?>
                <input type="radio" class="btn-check" name="logLevelFilter" id="<?php echo htmlspecialchars($inputId, ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo htmlspecialchars($logLevelValue, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" <?php if ($logLevelValue === 'ALL') { echo 'checked'; } ?>>
                <label class="btn btn-outline-secondary" for="<?php echo htmlspecialchars($inputId, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($logLevelLabel, ENT_QUOTES, 'UTF-8'); ?></label>
              <?php } ?>
            </div>
            <span id="log-filter-count" class="text-muted small"></span>
          </div>
          <textarea id="settings-log-content" style="height: 400px; width: 100%; font-family: monospace;" readonly><?php echo htmlspecialchars($currentLogContent); ?></textarea>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
  $(function() {
    var hash = document.location.hash;
    if (hash.match('#confBoards')) {
      $('#settingsTabs a[href="' + hash + '"]').tab('show');
    } else if (hash.match('#confSensors')) {
      $('#settingsTabs a[href="#' + hash.split('#')[1] + '"]').tab('show');
    }
  });

  $(function() {
    var $settingsTabs = $('#settingsTabs a[data-bs-toggle="tab"]');
    if ($settingsTabs.length === 0) {
      return;
    }

    if ($('#settingsTabs a.active').length === 0) {
      $settingsTabs.first().tab('show');
    }

    $settingsTabs.on('shown.bs.tab', function(event) {
      var targetHash = $(event.target).attr('href');
      if (targetHash) {
        history.replaceState(null, '', targetHash);
      }
    });
  });

  $(function() {
    $('.myToggleButton').change(function() {  
      $('#console-event').text('Toggle: ' + $(this).prop('checked'))
      if ($(this).prop('checked') == true) {
        $(".table ." + $(this).attr("value")).show();
      } else {
        $(".table ." + $(this).attr("value")).hide();
      }
    })
  })

  $(function() {
    var rawLogContent = <?php echo json_encode((string)$currentLogContent, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    var logTextarea = $('#settings-log-content');
    var logCount = $('#log-filter-count');

    function applyLogFilter(level) {
      var logLines = rawLogContent.split(/\r?\n/);
      var filteredLines = logLines;

      if (level !== 'ALL') {
        filteredLines = logLines.filter(function(line) {
          return line.indexOf('[' + level + ']') !== -1;
        });
      }

      logTextarea.val(filteredLines.join('\n'));
      logCount.text(filteredLines.filter(function(line) {
        return line.trim() !== '';
      }).length + ' ' + <?php echo json_encode(mds_t('settings.log_filter_entries'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>);
    }

    $('input[name="logLevelFilter"]').on('change', function() {
      applyLogFilter($(this).val());
    });

    applyLogFilter('ALL');
  });
</script>
<?php
include_once dirname(__DIR__) . "/app/Presentation/Common/footer.inc.php";
?>
