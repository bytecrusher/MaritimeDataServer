<?php
// Check if Session exist and user is logged in.
require_once("../func/myFunctions.func.php");

session_start();

if (isset($_SESSION['userobj'])) {
    $currentUser = unserialize($_SESSION['userobj']);
    echo json_encode(true);
} else {
    $currentUser = false;
    echo json_encode(false);
}

?>