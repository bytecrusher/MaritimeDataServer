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
require_once dirname(__DIR__) . "/app/Application/myFunctions.func.php";
include(dirname(__DIR__) . "/app/Presentation/Common/header.inc.php");
?>
<div class="container main-container registration-form">
  <h1><?php echo htmlspecialchars(mds_t('register.submit'), ENT_QUOTES, 'UTF-8'); ?></h1>
  <?php if(isset($message)) { ?>
    <div class="message <?php echo mds_h($type); ?>"><?php echo mds_h($message); ?></div>
  <?php } ?>
</div>
<?php
include(dirname(__DIR__) . "/app/Presentation/Common/footer.inc.php")
?>
