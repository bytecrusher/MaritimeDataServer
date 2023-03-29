<?php

require_once("../frontend/func/myFunctions.func.php");
require_once("../frontend/func/dbUpdateData.php");

$path = __DIR__ . '/../config.json';
$jsonString = file_get_contents($path);
$jsonData = json_decode($jsonString, true);

$var_dbhostname = $jsonData['db_host'];
$var_dbname = $jsonData['db_name'];
$var_dbusername = $jsonData['db_user'];
$var_dbpassword = $jsonData['db_password'];

$pdo = $rtn = null;
if (isset($_POST["action"])) {
  $error = false;
  $var_firstname = trim($_POST["firstname"]);
  $var_lastname = trim($_POST["lastname"]);
  $var_email = trim($_POST["email"]);
  $var_password = $_POST["password"];
  $var_password2 = $_POST["password2"];
  $var_apikey = trim($_POST["apikey"]);
  $var_md5secretstring = trim($_POST["md5secretstring"]);
  $var_baseurl = trim($_POST["baseurl"]);
  
  if ($_POST["action"] == "createadmin") {
    if(empty($var_firstname) || empty($var_lastname) || empty($var_email)) {
      //echo '<div class="alert alert-danger" role="alert">Please enter all fields.</div>';
      $rtn = array("error"=>"true", "error_text"=>"Please enter all fields.");
      $error = true;
    }
  
    if(!filter_var($var_email, FILTER_VALIDATE_EMAIL)) {
      //echo '<div class="alert alert-danger" role="alert">Please enter a valid email adress.</div>';
      $rtn = array("error"=>"true", "error_text"=>"Please enter a valid email adress.");
      $error = true;
    }
    if(strlen($var_password) == 0) {
      //echo '<div class="alert alert-danger" role="alert">Password necessary.</div>';
      $rtn = array("error"=>"true", "error_text"=>"Password necessary.");
      $error = true;
    }
    if($var_password != $var_password2) {
      //echo '<div class="alert alert-danger" role="alert">Both passwords must be the same.</div>';
      $rtn = array("error"=>"true", "error_text"=>"Both passwords must be the same.");
      $error = true;
    }

    if(!$error) {
      $testDbConnectionreturn = testDbConnection($var_dbhostname, $var_dbname, $var_dbusername, $var_dbpassword);
      if($testDbConnectionreturn) {
        $rtn = array("error"=>"false", "success_text"=>$success_msg);
        //Check that the email address has not yet been registered
        $error = false;
        if(!$error) {
          if(myFunctions::isUserRegistred($var_email)) {
            //echo '<div class="alert alert-danger" role="alert">The entered email adress already exist.</div>';
            $error = true;
            $rtn = array("error"=>"true", "error_text"=>"Email already exist in db.");
          } else {
            $password_hash = password_hash($var_password, PASSWORD_DEFAULT);
            try {
              $result = dbUpdateData::insertAdmin($var_email, $password_hash, $var_firstname, $var_lastname);
            } catch (Exception $e) {
              $error_msg = "Admin not inserted successfully.";
              $rtn = array("error"=>"true", "error_text"=>"An error occurs while saving.");
            }
            
            if($result) {
              //echo "<div class='alert alert-success' role='alert'>Registration successful.<br>
              //The Admin now needs to activate your account.</div>
              //<a href='login.php' class='btn btn-primary'>Login</a>";
              $rtn = array("error"=>"false", "success_text"=>"Registration successful. User created.");
              $showFormular = false;
            } else {
              //echo 'An error occurs while saving.<br>';
              $rtn = array("error"=>"true", "error_text"=>"An error occurs while saving.");
            }
            
          }
        }
      } else {
        $rtn = array("error"=>"true", "error_text"=>$testDbConnectionreturn);
      }

      $jsonData['api_key'] = $var_apikey;
      $jsonData['md5secretstring'] = $var_md5secretstring;
      $jsonData['baseurl'] = $var_baseurl;
      //$jsonData['db_name'] = $var_dbname;
      //$jsonData['db_user'] = $var_dbusername;
      //$jsonData['db_password'] = $var_dbpassword;
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
      //return false;
      return $error_msg;
    }
    return true;
} 

?>