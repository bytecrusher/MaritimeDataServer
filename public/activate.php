<?php
require_once dirname(__DIR__) . "/bootstrap/app.php";
require_once dirname(__DIR__) . "/app/Application/dbUpdateData.php";

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
	require_once dirname(__DIR__) . "/app/Application/myFunctions.func.php";
	include(dirname(__DIR__) . "/app/Presentation/Common/header.inc.php");
?>
<div class="container main-container registration-form">
<h1>Register</h1>

<title>User Activation</title>
<link rel="stylesheet" type="text/css" href="<?php echo htmlspecialchars(mds_asset_path('css/style.css'), ENT_QUOTES, 'UTF-8'); ?>" />
</head>
<body>
<?php if(isset($message)) { ?>
    <div class="message <?php echo $type; ?>"><?php echo $message; ?></div>
    <?php } ?>
<?php
include(dirname(__DIR__) . "/app/Presentation/Common/footer.inc.php")
?>
</body>
</html>
