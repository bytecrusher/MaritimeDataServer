<?php
// Check if Session exist and user is logged in.
require_once("../func/myFunctions.func.php");

session_start();

if (isset($_SESSION['userObj'])) {
    $currentUser = unserialize($_SESSION['userObj']);
    echo json_encode(true);
} else {
    $currentUser = false;
    echo json_encode(false);
}