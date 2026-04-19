<?php
    include("./../frontend/api/get_data.php");
        
    $maxtimeout=strtotime("-15 Minutes");
    //echo "aktuelle zeit -x " . date("Y-m-d h:i:sa", $d2) . "<br>";
    //$dbtimestamp=strtotime($deviceOnline[0]['reading_time']);
    //echo "DB timestamp " . date("Y-m-d h:i:sa", $d);
  
    // get all Boards
    global $pdo;
    global $mysqli;
    $query = sprintf("SELECT * FROM boardconfig ORDER BY id");
    //execute query
    $result = $pdo->query($query);
    //loop through the returned data
    //$data = array();
    foreach ($result as $row) {
        //$data[] = $row;
        echo "board id: " . $row['id'] . ", ";
        $boardIsOnline = checkDeviceIsOnline($row['id']);
        if ($boardIsOnline) {
            //$boardIsOnline = checkDeviceIsOnline($row['id']);
            //echo ", Board is Online: " . $boardIsOnline;
            echo ", Board is Online: true";

            //$query2 = sprintf("SELECT * FROM sensorconfig WHERE boardid = " . $row['id'] . " ORDER BY id");
            //$result2 = $pdo->query($query2);
            //foreach ($result2 as $row2) {
                //$data[] = $row2;
            //    echo "sensor id: " . $row2['id'] . ", ";
            //}
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
