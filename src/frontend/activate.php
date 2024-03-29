<?php
require_once("func/dbUpdateData.php");

if (! empty($_GET["id"])) {
    $result = dbUpdateData::activateUserStatus($_GET["id"]);

    if ($result) {
        $message = "Your account is activated.";
        $type = "success";
    } else {
        $message = "problem in account activation (is already active?).";
        $type = "error";
    }
}
?>
<html>
<head>
<?php
	session_start();
	require_once(__DIR__ . "/func/myFunctions.func.php");
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

