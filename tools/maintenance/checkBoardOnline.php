<?php
    require_once(dirname(__DIR__, 2) . "/bootstrap/app.php");
    require_once(dirname(__DIR__, 2) . "/app/Domain/Board/get_data.php");
        
    $maxtimeout=strtotime("-15 Minutes");
    //echo "aktuelle zeit -x " . date("Y-m-d h:i:sa", $d2) . "<br>";
    //$dbtimestamp=strtotime($deviceOnline[0]['reading_time']);
    //echo "DB timestamp " . date("Y-m-d h:i:sa", $d);
  
    // get all Boards
    global $pdo;
    global $mysqli;
    $statement = $pdo->prepare("SELECT * FROM boardconfig ORDER BY id");
    $statement->execute();
    //loop through the returned data
    //$data = array();
    foreach ($statement as $row) {
        //$data[] = $row;
        echo "board id: " . $row['id'] . ", ";
        $boardIsOnline = checkDeviceIsOnline($row['id']);
        if ($boardIsOnline) {
            //$boardIsOnline = checkDeviceIsOnline($row['id']);
            //echo ", Board is Online: " . $boardIsOnline;
            echo ", Board is Online: true";

        } else {
            echo ", Board is Online: false";
        }
        echo "<br>";
    }


    //if ($dbtimestamp < $maxtimeout) {
        //echo "<span class='label label-danger'>Offline</span>";
        // Function call with your own text or variable
    //    telegram ("Device is Offline");
    //  } else {
        //echo "<span class='label label-success'>Online</span>";
        // Function call with your own text or variable
        //telegram ("Device is Online");
    //  }
?>
