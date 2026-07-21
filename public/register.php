<?php
require_once dirname(__DIR__) . "/bootstrap/app.php";
mds_start_session();
require_once dirname(__DIR__) . "/app/Application/myFunctions.func.php";
require_once dirname(__DIR__) . "/app/Application/dbUpdateData.php";
require_once dirname(__DIR__) . "/app/Application/NotificationService.php";
require_once dirname(__DIR__) . "/app/Domain/User/user.class.php";

$config = new configuration();

$sendActivationEmail = static function ($userId, $toEmail, $firstName) use ($config) {
    $actualLink = mds_absolute_url('activate.php?id=' . (int)$userId);
    $applicationName = trim((string)$config::$applicationName) ?: 'Maritime Data Server';
    if (mds_current_language() === 'de') {
        $subject = $applicationName . ' - Konto aktivieren';
        $content = "Hallo " . trim((string)$firstName) . ",\n\n";
        $content .= "oeffne den folgenden Link, um dein Konto zu aktivieren:\n" . $actualLink . "\n\n";
        $content .= "Dein Maritime Data Server Team\n";
    } else {
        $subject = $applicationName . ' - Account activation';
        $content = "Hi " . trim((string)$firstName) . ",\n\n";
        $content .= "open the following link to activate your account:\n" . $actualLink . "\n\n";
        $content .= "Your Maritime Data Server team\n";
    }

    $sent = NotificationService::sendTransactionalEmail(
        $toEmail,
        $subject,
        $content,
        'account-activation',
        $config
    );
    writeToLogFunction::info(
        'Registration activation email dispatch finished.',
        __FILE__,
        array('userId' => (int)$userId, 'acceptedByPhpMail' => $sent)
    );
    return $sent;
};

if (count($_POST) > 0) {
    if (!mds_verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = mds_t('login.csrf');
        $type = "error";
    }
    if (!isset($message)) {
        $rateLimit = mds_rate_limit_attempt(
            'register',
            strtolower(trim((string)($_POST["userEmail"] ?? ''))) . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
            5,
            3600
        );
        if (!$rateLimit['allowed']) {
            $message = mds_t('register.too_many');
            $type = "error";
        }
    }
    /* Form Required Field Validation */
    foreach ($_POST as $key => $value) {
        if ($key === 'csrf_token' || $key === 'terms') {
            continue;
        }
        if (!isset($message) && empty($_POST[$key])) {
            $message = ucwords($key) . " field is required";
            $type = "error";
            break;
        }
    }
    /* Password Matching Validation */
    if (($_POST['password'] ?? '') !== ($_POST['confirm_password'] ?? '')) {
        $message = 'Passwords should be same<br>';
        $type = "error";
    }

    /* Email Validation */
    if (! isset($message)) {
        if (! filter_var($_POST["userEmail"], FILTER_VALIDATE_EMAIL)) {
            $message = "Invalid UserEmail";
            $type = "error";
        }
    }

    /* Validation to check if Terms and Conditions are accepted */
    if (! isset($message)) {
        if (! isset($_POST["terms"])) {
            $message = "Accept Terms and conditions before submit";
            $type = "error";
        }
    }

    if (! isset($message)) {
        $userEmail = strtolower(trim((string)$_POST["userEmail"]));
        $dbData = myFunctions::isUserRegistered($userEmail);

        if (!$dbData) {
            $hashedPassword = password_hash(($_POST["password"]), PASSWORD_DEFAULT);

            $current_id = dbUpdateData::insertUser($userEmail, $hashedPassword, $_POST["firstName"], $_POST["lastName"]);

            if (! empty($current_id)) {
                $mailAccepted = $sendActivationEmail($current_id, $userEmail, $_POST["firstName"]);
                $message = $mailAccepted ? mds_t('register.activation_mail_queued') : mds_t('register.activation_mail_failed');
                $type = $mailAccepted ? "success" : "error";
                unset($_POST);
            } else {
                $message = "problem in registration. Try Again!";
                $type = "error";
            }
        } else {
            $existingUser = new user($userEmail);
            if (!(bool)$existingUser->isActive()) {
                $mailAccepted = $sendActivationEmail($existingUser->getId(), $existingUser->getEmail(), $existingUser->getFirstName());
                $message = $mailAccepted ? mds_t('register.activation_mail_queued') : mds_t('register.activation_mail_failed');
                $type = $mailAccepted ? "success" : "error";
                unset($_POST);
            } else {
                $message = mds_t('register.email_in_use');
                $type = "error";
            }
        }
    }
}
include(dirname(__DIR__) . "/app/Presentation/Common/header.inc.php");
?>
    <?php
    if(isset($error_msg) && !empty($error_msg)) {
        echo $error_msg;
    }
    ?>
    <div class="container main-container registration-form">
    <?php if(isset($message)) { 
            $success_msg = $message;
        ?>
        <div class="container small-container-330">
            <div class="message <?php echo mds_h($type); ?>"><?php echo mds_h($message); ?></div>
        </div>
        <?php } else { ?>
        <form name="frmRegistration" method="post" action="">
            <?php echo mds_csrf_input(); ?>
            <h2><?php echo htmlspecialchars(mds_t('register.heading'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <div class="form-group">
                <label for="firstName"><?php echo htmlspecialchars(mds_t('register.first_name'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                <input type="text" id="firstName" size="40" maxlength="250" name="firstName" class="form-control" required value="<?php if(isset($_POST['firstName'])) echo $_POST['firstName']; ?>">
            </div>
            <div class="form-group">
                <label for="lastName"><?php echo htmlspecialchars(mds_t('register.last_name'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                <input type="text" id="lastName" size="40" maxlength="250" name="lastName" class="form-control" required value="<?php if(isset($_POST['lastName'])) echo $_POST['lastName']; ?>">
            </div>
            <div class="form-group">
                <label for="password"><?php echo htmlspecialchars(mds_t('common.password'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                <input type="password" id="password" size="40"  maxlength="250" name="password" class="form-control" required value="">
            </div>
            <div class="form-group">
                <label for="confirm_password"><?php echo htmlspecialchars(mds_t('register.password_repeat'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                <input type="password" id="confirm_password" size="40" maxlength="250" name="confirm_password" class="form-control" required value="">
            </div>
            <div class="form-group">
                <label for="userEmail"><?php echo htmlspecialchars(mds_t('common.email'), ENT_QUOTES, 'UTF-8'); ?>:</label>
                <input type="email" id="userEmail" size="40" maxlength="250" name="userEmail" class="form-control" required value="<?php if(isset($_POST['userEmail'])) echo mds_h($_POST['userEmail']); ?>">
            </div>
            <div class="form-group">
                <input type="checkbox" name="terms"> <?php echo htmlspecialchars(mds_t('register.accept_terms'), ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <div class="form-group mt-2">
                <button type="submit" class="btn btn-lg btn-primary btn-block" name="submit" id="btn-submit" value="Register" onclick="showLoader();"><?php echo htmlspecialchars(mds_t('register.submit'), ENT_QUOTES, 'UTF-8'); ?></button>
            </div>
            <div id="loader-icon" class="loader">
                <img src="register/loader.gif" />
            </div>
        </form>
    <?php } ?>
    </div>
    <script>
    function showLoader() {
        document.getElementById("loader-icon").style.display = 'block';
        document.getElementById("btn-submit").style.display = 'none';
    }
    </script>
<?php
    include(dirname(__DIR__) . "/app/Presentation/Common/footer.inc.php")
?>
