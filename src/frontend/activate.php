<?php
require_once __DIR__ . '/register/DataSource.php';
$database = new DataSource();
if (! empty($_GET["id"])) {
    $query = "UPDATE users set active = '1' WHERE id='" . $_GET["id"] . "'";
    $query = "UPDATE users set active = ? WHERE id = ?";
    $paramType = 'si';
    $status = '1';
    $paramValue = array(
        $status,
        $_GET["id"]
    );
    $result = $database->update($query, $paramType, $paramValue);
    if (! empty($result)) {
        $message = "Your account is activated.";
        $type = "success";
    } else {
        $message = "problem in account activation.";
        $type = "error";
    }
}
?>
<html>
<head>
<?php
	session_start();
	//require_once("func/dbConfig.func.php");
	require_once(__DIR__ . "/func/myFunctions.func.php");
	//require_once("func/user.class.php");
	//require_once("func/dbUpdateData.php");
	include(__DIR__ . "/common/header.inc.php");
?>
<div class="container main-container registration-form">
<h1>Register</h1>

<title>User Activation</title>
<link rel="stylesheet" type="text/css" href="css/style.css" />
</head>
<body>
<?php if(isset($message)) { ?>
    <div class="message <?php echo $type; ?>"><?php echo $message; ?></div>
    <?php } ?>
<?php
include("./common/footer.inc.php")
?>
</body>
</html>

