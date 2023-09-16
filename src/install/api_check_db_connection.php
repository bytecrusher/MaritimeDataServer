<?php
$var_dbname = $var_dbusername = $var_dbpassword = $pdo = $rtn = null;
$var_dbhostname = "localhost";
if (isset($_POST["action"])) {
  $var_dbhostname = $_POST["dbhostname"];
  $var_dbname = $_POST["dbname"];
  $var_dbusername = $_POST["dbusername"];
  $var_dbpassword = $_POST["dbpassword"];
  $pdo = null;
  
  if ($_POST["action"] == "savedb") {
    $pdo = testDbConnection($var_dbhostname, $var_dbname, $var_dbusername, $var_dbpassword);
    if ($pdo != false) {
      $filename = 'database.sql';

      // Temporary variable, used to store current query
      $templine = '';
      $lines = file($filename);
      foreach ($lines as $line_num => $line) {
        if (substr($line, 0, 2) != '--' && $line != '') {
          $templine .= $line;
          // If it has a semicolon at the end, it's the end of the query
          if (substr(trim($line), -1, 1) == ';') {
            $sql = $templine;
            try {
              $rowinfo = "Tables created successfully.";
              foreach ($pdo->query($sql) as $row) {
                $rowinfo = $rowinfo . ", " . $row;
              }
              $rtn = array("error"=>"false", "success_text"=>$rowinfo);
            }
            catch(PDOException $e) {
              $error_msg = $e->getCode() . ": ";
              if (str_contains($e->getCode(), '42S01')) {
                $error_msg = $error_msg . "At least one Table already exist. Script Stopped.<br/>";
              } else {
                $error_msg = $error_msg . "error.<br/>";
              }
              $rtn = array("error"=>"true", "error_text"=>$error_msg);
              break;
            }
            $templine = '';
          }
        }
      }

      $path = __DIR__ . '/../config.json';
      $jsonString = file_get_contents($path);
      $jsonData = json_decode($jsonString, true);
      $jsonData['db_host'] = $var_dbhostname;
      $jsonData['db_name'] = $var_dbname;
      $jsonData['db_user'] = $var_dbusername;
      $jsonData['db_password'] = $var_dbpassword;
      $jsonString = json_encode($jsonData, JSON_PRETTY_PRINT);
      // Write in the file
      $fp = fopen($path, 'w');
      fwrite($fp, $jsonString);
      fclose($fp);

    } else {
      $rtn = array("error"=>"true", "error_text"=>$error_msg);
    }
  } else if ($_POST["action"] == "testdb") {
    if(testDbConnection($var_dbhostname, $var_dbname, $var_dbusername, $var_dbpassword)) {
      $rtn = array("error"=>"false", "success_text"=>$success_msg);
    } else {
      $rtn = array("error"=>"true", "error_text"=>$error_msg);
    }
  } else if ($_POST["action"] == "createadmin") {

  }
  http_response_code(200);
  print json_encode($rtn);
}

function testDbConnection($var_dbhostname, $var_dbname, $var_dbusername, $var_dbpassword) {
    global $success_msg, $error_msg, $pdo;
    try {
      $pdo = new PDO("mysql:host=" . $var_dbhostname . ";dbname=" . $var_dbname, $var_dbusername, $var_dbpassword);
      if ($pdo != null) {
        $success_msg = "Successfull connected to Database.";
      }
    }
    catch(PDOException $e)
    {
      if ($e->getCode() == 2002) {
        $error_msg = "Database not found. Correct Hostname?<br/>";
      } else if ($e->getCode() == 1044) {
        $error_msg = "Wrond Database name.<br/>";
      } else if ($e->getCode() == 1045) {
        $error_msg = "Access denied for user. Correct Username and Password?<br/>";
      }
      return false;
    }
    return $pdo;
} 

?>