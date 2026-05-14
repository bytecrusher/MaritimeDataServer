<?php
require_once dirname(__DIR__) . "/bootstrap/app.php";
mds_start_session();
require_once dirname(__DIR__) . "/app/Application/dbUpdateData.php";

if (! empty($_GET["id"])) {
    $result = dbUpdateData::activateUserStatus($_GET["id"]);

    if ($result) {
        $message = mds_current_language() === 'de' ? "Dein Account ist aktiviert." : "Your account is activated.";
        $type = "success";
    } else {
        $message = mds_current_language() === 'de' ? "Problem bei der Account-Aktivierung (ist der Account bereits aktiv?)." : "Problem in account activation (is it already active?).";
        $type = "error";
    }
}
?>
<html>
<head>
<?php
		require_once dirname(__DIR__) . "/app/Application/myFunctions.func.php";
		include(dirname(__DIR__) . "/app/Presentation/Common/header.inc.php");
?>
<div class="container main-container registration-form">
<h1><?php echo htmlspecialchars(mds_t('register.submit'), ENT_QUOTES, 'UTF-8'); ?></h1>

<title><?php echo htmlspecialchars(mds_t('register.heading'), ENT_QUOTES, 'UTF-8'); ?></title>
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
