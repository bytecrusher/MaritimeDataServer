<?php
/*
*   Collects all GPS from a specific board.
*/
session_start();
header('Content-Type: application/json');
require_once(__DIR__ . "/../../Application/myFunctions.func.php");
$aResult = array();

$sessionUserId = isset($_SESSION['userId']) ? (int)$_SESSION['userId'] : 0;
if ($sessionUserId <= 0) {
    http_response_code(401);
    echo json_encode(array('error' => 'Authentication required.'));
    exit;
}

if (!isset($_POST['functionName'])) {
    $aResult['error'] = 'No function name!';
} elseif ($_POST['functionName'] !== 'get') {
    $aResult['error'] = 'Not found function ' . $_POST['functionName'] . '!';
} else {
    $myBoards = myFunctions::getMyBoards($sessionUserId);
    foreach ($myBoards as $myBoard) {
        $myGpsData = myFunctions::getAllGpsData($myBoard['id']);
        if ($myGpsData != 0) {
            $aResult[$myBoard['id']] = $myGpsData;
        }
    }
}

echo json_encode($aResult);
