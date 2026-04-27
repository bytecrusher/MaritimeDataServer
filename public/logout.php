<?php
require_once dirname(__DIR__) . "/bootstrap/app.php";
mds_start_session();
session_destroy();
unset($_SESSION['userId']);

//Remove Cookies
mds_clear_remember_login_cookies();
require_once dirname(__DIR__) . "/app/Infrastructure/Database/dbConfig.func.php";
require_once dirname(__DIR__) . "/app/Application/myFunctions.func.php";

include_once dirname(__DIR__) . "/app/Presentation/Common/header.inc.php";
?>

<div class="container main-container">
The logout was successful. <a href="login.php">Back to login.</a>.
</div>
<?php
include_once dirname(__DIR__) . "/app/Presentation/Common/footer.inc.php";
?>
