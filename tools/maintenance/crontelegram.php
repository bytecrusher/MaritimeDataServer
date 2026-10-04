<?php
    require_once(dirname(__DIR__, 2) . "/bootstrap/app.php");
    require_once(dirname(__DIR__, 2) . "/app/Domain/Board/get_data.php");
    $deviceOnline = checkDeviceIsOnline();

    $maxtimeout=strtotime("-15 Minutes");
    //echo "aktuelle zeit -x " . date("Y-m-d h:i:sa", $d2) . "<br>";
    $dbtimestamp=strtotime($deviceOnline[0]['reading_time']);
    //echo "DB timestamp " . date("Y-m-d h:i:sa", $d);

    // Telegram function which you can call
    function telegram($msg) {
        global $telegrambot,$telegramchatid;
        $url='https://api.telegram.org/bot'.$telegrambot.'/sendMessage';$data=array('chat_id'=>$telegramchatid,'text'=>$msg);
        $options=array('http'=>array('method'=>'POST','header'=>"Content-Type:application/x-www-form-urlencoded\r\n",'content'=>http_build_query($data),),);
        $context=stream_context_create($options);
        $result=file_get_contents($url,false,$context);
        return $result;
    }

    // Set your Bot ID and Chat ID.
    $telegrambot='1667893062:AAGo0-SKdrqjCfjAUMmbsgmUtGcerOxcLnk';
    $telegramchatid=780544921;

    if ($dbtimestamp < $maxtimeout) {
        //echo "<span class='label label-danger'>Offline</span>";
        // Function call with your own text or variable
        telegram ("Device is Offline");
      } else {
        //echo "<span class='label label-success'>Online</span>";
        // Function call with your own text or variable
        //telegram ("Device is Online");
      }
?>
