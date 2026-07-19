<?php

require_once dirname(__DIR__) . "/bootstrap/app.php";
mds_set_request_language($_GET['lang'] ?? 'en');
mds_start_session_if_present();
mds_apply_public_cache_headers();
$mdsPageNeedsJquery = false;
$mdsPageNeedsBootstrapIcons = false;

$config = new configuration();
$companyName = trim((string)($config::$imprintCompanyName ?: $config::$applicationName));
$address = trim((string)$config::$imprintAddress);
$email = trim((string)$config::$imprintEmail);
$phone = trim((string)$config::$imprintPhone);
$isGerman = mds_current_language() === 'de';

include_once dirname(__DIR__) . "/app/Presentation/Common/header.inc.php";
?>

<div class="container-xl main-container">
  <section class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4 p-lg-5">
      <h1 class="mb-4"><?php echo htmlspecialchars(mds_t('imprint.title'), ENT_QUOTES, 'UTF-8'); ?></h1>

      <div class="row g-4">
        <div class="col-lg-6">
          <h3 class="h5"><?php echo htmlspecialchars($isGerman ? 'Angaben gemaess § 5 TMG' : 'Legal information', ENT_QUOTES, 'UTF-8'); ?></h3>
          <p class="mb-0">
            <?php echo htmlspecialchars($companyName !== '' ? $companyName : ($isGerman ? 'Bitte im Betrieb hinterlegen' : 'Please configure for production use'), ENT_QUOTES, 'UTF-8'); ?><br>
            <?php if ($address !== '') { ?>
              <?php echo nl2br(htmlspecialchars($address, ENT_QUOTES, 'UTF-8')); ?>
            <?php } else { ?>
              <?php echo htmlspecialchars($isGerman ? 'Bitte Anschrift im Betrieb hinterlegen.' : 'Please configure the address for production use.', ENT_QUOTES, 'UTF-8'); ?>
            <?php } ?>
          </p>
        </div>

        <div class="col-lg-6">
          <h3 class="h5"><?php echo htmlspecialchars($isGerman ? 'Kontakt' : 'Contact', ENT_QUOTES, 'UTF-8'); ?></h3>
          <p class="mb-0">
            <?php if ($email !== '') { ?>
              E-Mail:
              <a href="mailto:<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></a><br>
            <?php } else { ?>
              <?php echo htmlspecialchars($isGerman ? 'E-Mail bitte im Betrieb hinterlegen.' : 'Please configure the e-mail address for production use.', ENT_QUOTES, 'UTF-8'); ?><br>
            <?php } ?>
            <?php if ($phone !== '') { ?>
              <?php echo htmlspecialchars($isGerman ? 'Telefon' : 'Phone', ENT_QUOTES, 'UTF-8'); ?>: <?php echo htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'); ?>
            <?php } ?>
          </p>
        </div>
      </div>

      <hr class="my-4">

      <h3 class="h5"><?php echo htmlspecialchars($isGerman ? 'Hinweis' : 'Note', ENT_QUOTES, 'UTF-8'); ?></h3>
      <p class="mb-0 text-muted">
        <?php echo htmlspecialchars($isGerman
          ? 'Diese Seite stellt nur die technisch eingebundenen Impressumsdaten bereit. Je nach Einsatzszenario koennen weitere Pflichtangaben notwendig sein.'
          : 'This page displays the legal information configured for this installation. Depending on the deployment scenario, additional mandatory information may be required.', ENT_QUOTES, 'UTF-8'); ?>
      </p>
    </div>
  </section>
</div>

<?php
include_once dirname(__DIR__) . "/app/Presentation/Common/footer.inc.php";
?>
