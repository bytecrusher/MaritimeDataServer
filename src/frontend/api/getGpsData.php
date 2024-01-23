<?php
/*
*   Collects all GPS from a specific board.
*/
header('Content-Type: application/json');
// Get data from DB for display in JS.
require_once("../func/myFunctions.func.php");
$aResult = array();
if( !isset($_POST['functionName']) ) { $aResult['error'] = 'No function name!'; }
    if( !isset($_POST['userId']) ) { $aResult['error'] = 'No user id!'; }
    if( !isset($aResult['error']) ) {
    switch($_POST['functionName']) {
        case 'get':
            $myBoards = myFunctions::getMyBoards($_POST['userId']);
            foreach ($myBoards as $myBoard) {
                $myGpsData = myFunctions::getAllGpsData($myBoard['id']);
                if ($myGpsData != 0) {
                    $aResult[$myBoard['id']] = $myGpsData;
                    //$aResult['name'] = $myBoard['name'];
                }
            }
            break;
        default:
           $aResult['error'] = 'Not found function '.$_POST['functionName'].'!';
           break;
    }
}
echo json_encode($aResult);