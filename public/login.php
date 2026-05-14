<?php
require_once dirname(__DIR__) . "/bootstrap/app.php";
mds_start_session();
require_once dirname(__DIR__) . "/app/Infrastructure/Database/dbConfig.func.php";
require_once dirname(__DIR__) . "/app/Application/myFunctions.func.php";
require_once dirname(__DIR__) . "/app/Domain/User/user.class.php";
require_once dirname(__DIR__) . "/app/Application/dbUpdateData.php";
require_once dirname(__DIR__) . "/app/Infrastructure/Logging/writeToLogFunction.func.php";
//writeToLogFunction::write_to_log("test", $_SERVER["SCRIPT_FILENAME"]);

$error_msg = "";
if(isset($_POST['email']) && isset($_POST['password'])) {
  if (!mds_verify_csrf_token($_POST['csrf_token'] ?? '')) {
    $error_msg = "<div class='alert alert-danger' role='alert'>" . htmlspecialchars(mds_t('login.csrf'), ENT_QUOTES, 'UTF-8') . "</div>";
  } else {
  $rateLimit = mds_rate_limit_attempt(
    'login',
    strtolower(trim((string)($_POST['email'] ?? ''))) . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
    5,
    900
  );
  if (!$rateLimit['allowed']) {
    $error_msg = "<div class='alert alert-danger' role='alert'>" . htmlspecialchars(mds_t('login.too_many'), ENT_QUOTES, 'UTF-8') . "</div>";
  } else {
  $email = $_POST['email'];
  $password = $_POST['password'];
  $userObj = new user($email);
  $myError = $userObj->getError();
  if ($myError == "42S02") {
    $error_msg = "<div class='alert alert-danger' role='alert'>" . htmlspecialchars(mds_t('login.install_missing'), ENT_QUOTES, 'UTF-8') .
      " <a href='./../install/index.php'>Install</a></div>";
  } else {
    //var_dump($myError);
    if ($userObj->userExist() != false) {
      $_SESSION['userObj'] = serialize($userObj);
      if ($userObj->isActive() == true) {
        //Check Password
        if ($userObj !== false && password_verify($password, $userObj->getPassword()) && $userObj->isActive() != false) {
          $_SESSION['userId'] = $userObj->getId();
          mds_set_current_language($userObj->getLanguage());
    
          //Does the user want to stay logged in?
          if(isset($_POST['angemeldet_bleiben'])) {
            dbUpdateData::insertSecurityToken($userObj->getId());
          }
          header("location: internal.php");
          exit;
        } else {
          $error_msg =  "<div class='alert alert-danger' role='alert'>" . htmlspecialchars(mds_t('login.failed'), ENT_QUOTES, 'UTF-8') . "</div>";
        }
      } else {
        $error_msg =  "<div class='alert alert-danger' role='alert'>" . htmlspecialchars(mds_t('login.failed'), ENT_QUOTES, 'UTF-8') . "</div>";
      }
    } else {
      $error_msg =  "<div class='alert alert-danger' role='alert'>" . htmlspecialchars(mds_t('login.failed'), ENT_QUOTES, 'UTF-8') . "</div>";
    }
  }
  }
  }
}

$email_value = "";
if(isset($_POST['email'])) {
  $email_value = htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8');
}
include_once dirname(__DIR__) . "/app/Presentation/Common/header.inc.php";
?>
<div class="container small-container-330 form-signin">
  <form action="login.php" method="post">
  <?php echo mds_csrf_input(); ?>
  <h2 class="form-signin-heading"><?php echo htmlspecialchars(mds_t('login.title'), ENT_QUOTES, 'UTF-8'); ?></h2>

  <?php
  if(isset($error_msg) && !empty($error_msg)) {
    echo $error_msg;
  }
  ?>
  <label for="inputEmail" class="sr-only"><?php echo htmlspecialchars(mds_t('common.email'), ENT_QUOTES, 'UTF-8'); ?></label>
  <input type="email" name="email" id="inputEmail" class="form-control" placeholder="<?php echo htmlspecialchars(mds_t('common.email'), ENT_QUOTES, 'UTF-8'); ?>" value="<?php echo $email_value; ?>" required autofocus>
  <label for="inputPassword" class="sr-only"><?php echo htmlspecialchars(mds_t('common.password'), ENT_QUOTES, 'UTF-8'); ?></label>
  <input type="password" name="password" id="inputPassword" class="form-control" placeholder="<?php echo htmlspecialchars(mds_t('common.password'), ENT_QUOTES, 'UTF-8'); ?>" required>
  <div class="checkbox">
    <label>
    <input type="checkbox" value="remember-me" name="angemeldet_bleiben" value="1"> <?php echo htmlspecialchars(mds_t('nav.remember_login'), ENT_QUOTES, 'UTF-8'); ?>
    </label>
  </div>
  <button class="btn btn-lg btn-primary btn-block" type="submit"><?php echo htmlspecialchars(mds_t('common.login'), ENT_QUOTES, 'UTF-8'); ?></button>
  <br>
  <a href="resetPassword.php"><?php echo htmlspecialchars(mds_t('nav.reset_password'), ENT_QUOTES, 'UTF-8'); ?></a>
  </form>

</div>

<?php
include_once dirname(__DIR__) . "/app/Presentation/Common/footer.inc.php";
?>
