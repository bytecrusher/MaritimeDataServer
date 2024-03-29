<?php
/*
*   Collects all GPS from a specific board.
*/
    header('Content-Type: application/json');
    // Get data from DB for display in JS.
    require_once("../func/myFunctions.func.php");
    $aResult = array();
    if( !isset($_POST['functionname']) ) { $aResult['error'] = 'No function name!'; }
        if( !isset($_POST['userid']) ) { $aResult['error'] = 'No userid!'; }
        if( !isset($aResult['error']) ) {
        switch($_POST['functionname']) {
            case 'get':
                $myboards = myFunctions::getMyBoards($_POST['userid']);
                foreach ($myboards as $myboard) {
                    $myGpsData = myFunctions::getAllGpsData($myboard['id']);
                    if ($myGpsData != 0) {
                        $aResult[$myboard['id']] = $myGpsData;
                        //$aResult['name'] = $myboard['name'];
                    }
                }
               break;
            default:
               $aResult['error'] = 'Not found function '.$_POST['functionname'].'!';
               break;
        }
    }
    echo json_encode($aResult);
?>
