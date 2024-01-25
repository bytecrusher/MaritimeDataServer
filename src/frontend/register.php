<?php

require_once(__DIR__ . "/func/myFunctions.func.php");
require_once("func/dbUpdateData.php");

$config = new configuration();
$var_AdminEmailAddress = $config::$adminEmailAddress;

if (count($_POST) > 0) {
    /* Form Required Field Validation */
    foreach ($_POST as $key => $value) {
        if (empty($_POST[$key])) {
            $message = ucwords($key) . " field is required";
            $type = "error";
            break;
        }
    }
    /* Password Matching Validation */
    if ($_POST['password'] != $_POST['confirm_password']) {
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
        $dbData = myFunctions::isUserRegistered($_POST["userEmail"]);

        if (!$dbData) {
            $hashedPassword = password_hash(($_POST["password"]), PASSWORD_DEFAULT);

            $current_id = dbUpdateData::insertUser($_POST["userEmail"], $hashedPassword, $_POST["firstName"], $_POST["lastName"]);

            if (! empty($current_id)) {
                $actual_link = "http://$_SERVER[HTTP_HOST]" . "/frontend/activate.php?id=" . $current_id;
                $toEmail = $_POST["userEmail"];
                $subject = "User Registration Activation Email";
                $content = "Hi " . $_POST["firstName"] . " click this link to activate your account. <a href='" . $actual_link . "'>" . $actual_link . "</a><br>Your MDS Team.";
                $mailHeaders = "From: MDS User Registration <" . $var_AdminEmailAddress . ">\r\n";
                $mailHeaders .= "Reply-To: " . $var_AdminEmailAddress . "\r\n";
                $mailHeaders .= "Content-Type: text/html\r\n";                

                if (mail($toEmail, $subject, $content, $mailHeaders)) {
                    $message = "You have registered and the activation mail is sent to your email. Click the activation link to activate you account.";
                    $type = "success";
                }
                unset($_POST);
            } else {
                $message = "problem in registration. Try Again!";
            }
        } else {
            $message = "User Email is already in use.";
            $type = "error";
        }
    }
}
?>
<html>
<head>
<?php
	session_start();
	require_once(__DIR__ . "/func/myFunctions.func.php");
	require_once(__DIR__ . "/func/user.class.php");
	include(__DIR__ . "/common/header.inc.php");
?>
<title>User Registration</title>
<style>
.gender-radio {
    width: auto;
}

#loader-icon {
    margin-left: 80px;
    display: none;
}
</style>
</head>
<body>
    <?php
        $userObj = new user("test@test.de"); // TODO check if this is correct
        $myError = $userObj->getError();
        if ($myError == "42S02") {
            $error_msg =  "<div class='alert alert-danger' role='alert'>Tables does not exist. Please run install. 
            <a href='./../install/index.php'>Install</a></div>";
        } 
        if(isset($error_msg) && !empty($error_msg)) {
            echo $error_msg;
        }
    ?>
    <div class="container main-container registration-form">
    <?php if(isset($message)) { 
            $success_msg = $message;
        ?>
        <div class="container small-container-330">
            <div class="message <?php echo $type; ?>"><?php echo $message; ?></div>
        </div>
        <?php } else { ?>
        <form name="frmRegistration" method="post" action="">
            <h2>User Activation Email</h2>
            <div class="form-group">
                <label for="firstName">First name:</label>
                <input type="text" id="firstName" size="40" maxlength="250" name="firstName" class="form-control" required value="<?php if(isset($_POST['firstName'])) echo $_POST['firstName']; ?>">
            </div>
            <div class="form-group">
                <label for="lastName">Last name:</label>
                <input type="text" id="lastName" size="40" maxlength="250" name="lastName" class="form-control" required value="<?php if(isset($_POST['lastName'])) echo $_POST['lastName']; ?>">
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" size="40"  maxlength="250" name="password" class="form-control" required value="">
            </div>
            <div class="form-group">
                <label for="confirm_password">Password repeat:</label>
                <input type="password" id="confirm_password" size="40" maxlength="250" name="confirm_password" class="form-control" required value="">
            </div>
            <div class="form-group">
                <label for="userEmail">E-Mail:</label>
                <input type="email" id="userEmail" size="40" maxlength="250" name="userEmail" class="form-control" required value="<?php if(isset($_POST['userEmail'])) echo $_POST['userEmail']; ?>">
            </div>
            <div class="form-group">
                <input type="checkbox" name="terms"> I accept Terms and
                Conditions
            </div>
            <div class="form-group mt-2">
                <button type="submit" class="btn btn-lg btn-primary btn-block" name="submit" id="btn-submit" value="Register" onclick="showLoader();">Register</button>
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
    include("./common/footer.inc.php")
?>
</body>
</html>