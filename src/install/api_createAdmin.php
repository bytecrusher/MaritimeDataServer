<?php
require_once("../frontend/func/myFunctions.func.php");
require_once("../frontend/func/dbUpdateData.php");
require_once("../frontend/func/writeToLogFunction.func.php");


$path = __DIR__ . '/../config.json';
$jsonString = file_get_contents($path);
$jsonData = json_decode($jsonString, true);

$var_dbHostName = $jsonData['dbHost'];
$var_dbName = $jsonData['dbName'];
$var_dbUserName = $jsonData['dbUser'];
$var_dbPassword = $jsonData['dbPassword'];

$pdo = $rtn = null;
if (isset($_POST["action"])) {
  $error = false;
  $var_firstName = trim($_POST["firstName"]);
  $var_lastName = trim($_POST["lastName"]);
  $var_email = trim($_POST["email"]);
  $var_password = $_POST["password"];
  $var_password2 = $_POST["password2"];
  $var_apiKey = trim($_POST["apiKey"]);
  $var_demoMode = trim($_POST["demoMode"]);
  $var_md5secretString = trim($_POST["md5secretString"]);
  //$var_baseurl = trim($_POST["baseurl"]);
  
  if ($_POST["action"] == "createadmin") {
    if(empty($var_firstName) || empty($var_lastName) || empty($var_email)) {
      $rtn = array("error"=>"true", "error_text"=>"Please enter all fields.");
      $error = true;
    }
  
    if(!filter_var($var_email, FILTER_VALIDATE_EMAIL)) {
      $rtn = array("error"=>"true", "error_text"=>"Please enter a valid email address.");
      $error = true;
    }
    if(strlen($var_password) == 0) {
      $rtn = array("error"=>"true", "error_text"=>"Password necessary.");
      $error = true;
    }
    if($var_password != $var_password2) {
      $rtn = array("error"=>"true", "error_text"=>"Both passwords must be the same.");
      $error = true;
    }

    if(!$error) {
      $testDbConnectionReturn = testDbConnection($var_dbHostName, $var_dbName, $var_dbUserName, $var_dbPassword);
      if($testDbConnectionReturn) {
        $rtn = array("error"=>"false", "success_text"=>$success_msg);
        //Check that the email address has not yet been registered
        $error = false;
        if(!$error) {
          if(myFunctions::isUserRegistered($var_email)) {
            $error = true;
            $rtn = array("error"=>"true", "error_text"=>"Email already exist in db.");
          } else {
            $password_hash = password_hash($var_password, PASSWORD_DEFAULT);
            try {
              $result = dbUpdateData::insertAdmin($var_email, $password_hash, $var_firstName, $var_lastName);
            } catch (Exception $e) {
              $error_msg = "Admin not inserted successfully.";
              $rtn = array("error"=>"true", "error_text"=>"An error occurs while saving.");
              writeToLogFunction::write_to_log("Admin not inserted successfully.", $_SERVER["SCRIPT_FILENAME"]);
              writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
            }
            if($result) {
              $rtn = array("error"=>"false", "success_text"=>"Registration successful. User created.");
              $showFormular = false;
            } else {
              $rtn = array("error"=>"true", "error_text"=>"An error occurs while saving.");
            }
          }
        }
      } else {
        $rtn = array("error"=>"true", "error_text"=>$testDbConnectionReturn);
      }

      $jsonData['apiKey'] = $var_apiKey;
      $jsonData['md5secretString'] = $var_md5secretString;
      //$jsonData['baseurl'] = $var_baseurl;
      $jsonData['demoMode'] = $var_demoMode;
      $jsonData['installFinished'] = true;
      $jsonString = json_encode($jsonData, JSON_PRETTY_PRINT);
      // Write in the file
      $fp = fopen($path, 'w');
      fwrite($fp, $jsonString);
      fclose($fp);
    }
  }
  http_response_code(200);
  print json_encode($rtn);
}

function testDbConnection($var_dbHostName, $var_dbName, $var_dbUserName, $var_dbPassword) {
    global $success_msg, $error_msg, $pdo;
    try {
      $pdo = new PDO("mysql:host=" . $var_dbHostName . ";dbname=" . $var_dbName, $var_dbUserName, $var_dbPassword);
      if ($pdo != null) {
        $success_msg = "Successful connected to Database.";
      }
    }
    catch(PDOException $e)
    {
      if ($e->getCode() == 2002) {
        $error_msg = "Database not found. Correct Hostname?<br/>";
      } else if ($e->getCode() == 1044) {
        $error_msg = "Wrong Database name.<br/>";
      } else if ($e->getCode() == 1045) {
        $error_msg = "Access denied for user. Correct Username and Password?<br/>";
      }
      writeToLogFunction::write_to_log("Admin not inserted successfully.", $_SERVER["SCRIPT_FILENAME"]);
      writeToLogFunction::write_to_log("Error: " . $e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
      //return false;
      return $error_msg;
    }
    return true;
}