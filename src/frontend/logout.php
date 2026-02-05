<?php
session_start();
session_destroy();
unset($_SESSION['userId']);

//Remove Cookies
setcookie("identifier","",time()-(3600*24*365));
setcookie("securityToken","",time()-(3600*24*365));

require_once "func/dbConfig.func.php";
require_once "func/myFunctions.func.php";

include_once "common/header.inc.php";
?>

<div class="container main-container">
The logout was successful. <a href="login.php">Back to login.</a>.
</div>
<?php
include_once "common/footer.inc.php";
?>
