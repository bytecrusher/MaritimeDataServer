<?php

require_once dirname(__DIR__) . "/bootstrap/app.php";
mds_start_session();

$config = new configuration();
$companyName = trim((string)($config::$imprintCompanyName ?: $config::$applicationName));
$address = trim((string)$config::$imprintAddress);
$email = trim((string)$config::$imprintEmail);
$phone = trim((string)$config::$imprintPhone);

include_once dirname(__DIR__) . "/app/Presentation/Common/header.inc.php";
?>

<div class="container-xl main-container">
  <section class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4 p-lg-5">
      <h1 class="mb-4">Impressum</h1>

      <div class="row g-4">
        <div class="col-lg-6">
          <h3 class="h5">Angaben gemaess § 5 TMG</h3>
          <p class="mb-0">
            <?php echo htmlspecialchars($companyName !== '' ? $companyName : 'Bitte im Betrieb hinterlegen', ENT_QUOTES, 'UTF-8'); ?><br>
            <?php if ($address !== '') { ?>
              <?php echo nl2br(htmlspecialchars($address, ENT_QUOTES, 'UTF-8')); ?>
            <?php } else { ?>
              Bitte Anschrift im Betrieb hinterlegen.
            <?php } ?>
          </p>
        </div>

        <div class="col-lg-6">
          <h3 class="h5">Kontakt</h3>
          <p class="mb-0">
            <?php if ($email !== '') { ?>
              E-Mail:
              <a href="mailto:<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?></a><br>
            <?php } else { ?>
              E-Mail bitte im Betrieb hinterlegen.<br>
            <?php } ?>
            <?php if ($phone !== '') { ?>
              Telefon: <?php echo htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'); ?>
            <?php } ?>
          </p>
        </div>
      </div>

      <hr class="my-4">

      <h3 class="h5">Hinweis</h3>
      <p class="mb-0 text-muted">
        Diese Seite stellt nur die technisch eingebundenen Impressumsdaten bereit. Je nach Einsatzszenario koennen weitere Pflichtangaben notwendig sein.
      </p>
    </div>
  </section>
</div>

<?php
include_once dirname(__DIR__) . "/app/Presentation/Common/footer.inc.php";
?>
