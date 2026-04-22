<?php
require_once(dirname(__DIR__, 2) . "/bootstrap/app.php");
require_once(dirname(__DIR__, 2) . "/app/Infrastructure/Database/dbConfig.func.php");
require_once(dirname(__DIR__, 2) . "/app/Application/myFunctions.func.php");
require_once(dirname(__DIR__, 2) . "/app/Domain/Board/get_data.php");

$config = new configuration();
$var_AdminEmailAddress = $config::$adminEmailAddress;

$allOfflineBoardsToNotify = myFunctions::getAllOfflineBoardsToNotify();

$userWithNotificationOn = myFunctions::getAllUsersWithReceiveNotifications(); //ToDO: reduce the amount of data (attributes), the ID and email will be enough?!

if ($config::$sendEmails) {
    echo("Sendmail active");
    foreach ($userWithNotificationOn as &$eachUser) {
        $toEmail = $eachUser["email"];
        $subject = "Sendmail test";
        $mailHeaders = "From: Maritime Data Server<" . $var_AdminEmailAddress . ">\r\n";
        $mailHeaders .= "Reply-To: (Maritime Data Server)" . $var_AdminEmailAddress . "\r\n";
        $mailHeaders .= "Content-Type: text/html\r\n";
    
        foreach ($allOfflineBoardsToNotify as &$singleBoard) {
            if (($singleBoard["ownerUserId"] == $eachUser["id"]) && (!checkDeviceIsOnline($singleBoard["id"]))) {
                echo($singleBoard["name"] . "<br>");
                $message = "Board " . $singleBoard["name"] . " Offline.";
                if (mail($toEmail, $subject, $message, $mailHeaders)) {
                    $type = "success";
                } else {
                    //todo: log into log file.
                }
                myFunctions::setAlreadyNotified($singleBoard["id"]);
            }
        }
    }
}
