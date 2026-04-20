<?php
/*
* File for Display Data for the user
*
*/
// Note: add the option to define virtual sensor groups for visual grouping.

session_start();
require_once dirname(__DIR__, 2) . "/bootstrap/app.php";
require_once dirname(__DIR__, 2) . "/app/Infrastructure/Database/dbConfig.func.php";
require_once dirname(__DIR__, 2) . "/app/Application/myFunctions.func.php";
require_once dirname(__DIR__, 2) . "/app/Domain/User/user.class.php";
require_once dirname(__DIR__, 2) . "/app/Domain/Board/board.class.php";
require_once dirname(__DIR__, 2) . "/app/Infrastructure/Logging/writeToLogFunction.func.php";

$currentUser = false;
if (isset($_SESSION['userId']) && (int) $_SESSION['userId'] > 0) {
    $sessionUserData = dbGetData::getUserById((int) $_SESSION['userId']);
    if (is_array($sessionUserData) && !empty($sessionUserData['email'])) {
        $currentUser = new user($sessionUserData['email']);
        $_SESSION['userObj'] = serialize($currentUser);
    }
} elseif (isset($_SESSION['userObj'])) {
    $sessionUserObj = @unserialize($_SESSION['userObj'], ['allowed_classes' => ['user']]);
    if ($sessionUserObj instanceof user) {
        $currentUser = $sessionUserObj;
        $_SESSION['userId'] = $currentUser->getId();
    }
}

if (!$currentUser) {
    header("Location: ./index.php");    // if user not logged in
    die();
}

include_once __DIR__ . "/internal.html"; // NOSONAR - Legacy Template-Einbindung
require_once dirname(__DIR__, 2) . "/app/Domain/Board/get_data.php";
$config = new configuration();
$varDemoMode = $config::$demoMode;
?>
<script src="./js/internal.js"></script>
