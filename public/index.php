<?php
require_once dirname(__DIR__) . "/bootstrap/app.php";
mds_start_session();
require_once dirname(__DIR__) . "/app/Infrastructure/Database/dbConfig.func.php";
require_once dirname(__DIR__) . "/app/Application/myFunctions.func.php";
require_once dirname(__DIR__) . "/app/Application/InternalPageService.php";
require_once dirname(__DIR__) . "/app/Domain/User/user.class.php";
$userObj = InternalPageService::resolveCurrentUserFromSession();
include_once dirname(__DIR__) . "/app/Presentation/Common/header.inc.php";    // NOSONAR - Legacy Template-Einbindung

?>
<div class="jumbotron" style="padding: 1rem 1rem; margin-bottom: 1rem;">
  <div class="container">
    <h1><?php echo htmlspecialchars(mds_t('home.title'), ENT_QUOTES, 'UTF-8'); ?></h1>
  </div>
</div>

<div class="container">
  <div class="row">
    <div class="col-md-6">
      <h2><?php echo htmlspecialchars(mds_t('home.about'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(mds_t('home.about_text'), ENT_QUOTES, 'UTF-8'); ?></p>
      <p><?php echo htmlspecialchars(mds_t('home.collector_text'), ENT_QUOTES, 'UTF-8'); ?></p>
      <p><?php echo htmlspecialchars(mds_t('home.transfer_text'), ENT_QUOTES, 'UTF-8'); ?></p>
      <ul>
        <li><?php echo htmlspecialchars(mds_t('home.feature_dashboard'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(mds_t('home.feature_charts'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(mds_t('home.feature_alerts'), ENT_QUOTES, 'UTF-8'); ?></li>
        <li><?php echo htmlspecialchars(mds_t('home.feature_responsive'), ENT_QUOTES, 'UTF-8'); ?></li>
      </ul>
    </div>
    <div class="col-md-6">
      <h2><?php echo htmlspecialchars(mds_t('home.documentation'), ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars(mds_t('home.documentation_text'), ENT_QUOTES, 'UTF-8'); ?></p>
      <p><?php echo htmlspecialchars(mds_t('home.configuration_text'), ENT_QUOTES, 'UTF-8'); ?></p>
   </div>
  </div>
</div>

<?php
include_once dirname(__DIR__) . "/app/Presentation/Common/footer.inc.php";    // NOSONAR - Legacy Template-Einbindung
?>
