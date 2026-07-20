<?php
  $config = new configuration();
  $publicInstallPath = mds_route_path('install/index.php');

  if (!$config::$config_exist) {
    if (is_dir(dirname(__DIR__, 3) . '/public/install')) {
      header('Location: ' . $publicInstallPath);
      exit();
    }

    echo htmlspecialchars(mds_t('header.install_missing'), ENT_QUOTES, 'UTF-8');
    exit();
  }

  $seoMetadata = mds_seo_metadata($_SERVER['SCRIPT_NAME'] ?? null, mds_current_language());
  $seoStructuredData = mds_seo_website_structured_data($seoMetadata);
  $seoPageKey = $seoMetadata['pageKey'] ?? null;
  $publicHomePath = $seoPageKey !== null
    ? mds_route_path(mds_seo_route('home', mds_current_language()))
    : mds_route_path('index.php');
  $mdsPageSlug = strtolower((string)pathinfo(basename($_SERVER['PHP_SELF'] ?? 'page'), PATHINFO_FILENAME));
  $mdsPageSlug = preg_replace('/[^a-z0-9-]+/', '-', $mdsPageSlug) ?: 'page';
  $mdsBodyClasses = array('mds-app-shell', 'mds-page-' . $mdsPageSlug);
  if (!empty($mdsBodyClass)) {
    $mdsBodyClasses = array_merge($mdsBodyClasses, preg_split('/\s+/', trim((string)$mdsBodyClass)) ?: array());
  }
  $mdsBodyClasses = array_values(array_unique(array_filter($mdsBodyClasses)));
  $mdsCurrentPage = basename($_SERVER['PHP_SELF'] ?? '');
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(mds_current_language(), ENT_QUOTES, 'UTF-8'); ?>">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($seoMetadata['title'], ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($seoMetadata['description'], ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="robots" content="<?php echo htmlspecialchars($seoMetadata['robots'], ENT_QUOTES, 'UTF-8'); ?>">
    <?php if (!empty($seoMetadata['canonical'])) { ?>
      <link rel="canonical" href="<?php echo htmlspecialchars($seoMetadata['canonical'], ENT_QUOTES, 'UTF-8'); ?>">
      <?php foreach (($seoMetadata['alternates'] ?? array()) as $alternateLanguage => $alternateUrl) { ?>
        <link rel="alternate" hreflang="<?php echo mds_h($alternateLanguage); ?>" href="<?php echo mds_h($alternateUrl); ?>">
      <?php } ?>
      <meta property="og:type" content="website">
      <meta property="og:site_name" content="<?php echo htmlspecialchars($seoMetadata['siteName'], ENT_QUOTES, 'UTF-8'); ?>">
      <meta property="og:title" content="<?php echo htmlspecialchars($seoMetadata['title'], ENT_QUOTES, 'UTF-8'); ?>">
      <meta property="og:description" content="<?php echo htmlspecialchars($seoMetadata['description'], ENT_QUOTES, 'UTF-8'); ?>">
      <meta property="og:url" content="<?php echo htmlspecialchars($seoMetadata['canonical'], ENT_QUOTES, 'UTF-8'); ?>">
      <meta property="og:locale" content="<?php echo mds_current_language() === 'de' ? 'de_DE' : 'en_US'; ?>">
      <?php if (!empty($seoMetadata['image'])) { ?>
        <meta property="og:image" content="<?php echo mds_h($seoMetadata['image']); ?>">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:image:alt" content="Maritime Data Server telemetry platform">
      <?php } ?>
      <meta name="twitter:card" content="summary_large_image">
      <meta name="twitter:title" content="<?php echo htmlspecialchars($seoMetadata['title'], ENT_QUOTES, 'UTF-8'); ?>">
      <meta name="twitter:description" content="<?php echo htmlspecialchars($seoMetadata['description'], ENT_QUOTES, 'UTF-8'); ?>">
      <?php if (!empty($seoMetadata['image'])) { ?><meta name="twitter:image" content="<?php echo mds_h($seoMetadata['image']); ?>"><?php } ?>
    <?php } ?>
    <?php if (!empty(configuration::$googleSiteVerification)) { ?>
      <meta name="google-site-verification" content="<?php echo mds_h(configuration::$googleSiteVerification); ?>">
    <?php } ?>
    <?php if (is_array($seoStructuredData)) { ?>
      <script type="application/ld+json"><?php echo json_encode($seoStructuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
    <?php } ?>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars(mds_route_path('favicon.ico'), ENT_QUOTES, 'UTF-8'); ?>">
    <?php
      include(__DIR__ . "/includes.php");
    ?>
    <style>
      :root {
        --mds-surface: #ffffff;
        --mds-surface-muted: #f8fafc;
        --mds-ink: #0f172a;
        --mds-ink-soft: #475569;
        --mds-border: rgba(15, 23, 42, 0.08);
        --mds-shadow: 0 18px 40px rgba(15, 23, 42, 0.10);
        --mds-radius: 1.1rem;
      }
      .filter-green {
        filter: invert(66%) sepia(16%) saturate(1367%) hue-rotate(71deg) brightness(93%) contrast(89%);
      }
      body {
        background:
          radial-gradient(circle at top right, rgba(59, 130, 246, 0.09), transparent 28%),
          linear-gradient(180deg, #f3f6fb 0%, #eef2f7 100%);
        color: var(--mds-ink);
        -webkit-font-smoothing: antialiased;
        text-rendering: optimizeLegibility;
      }
      .main-container {
        width: min(1200px, calc(100% - 24px));
        margin: 0 auto 1.25rem;
      }
      .navbar.mds-navbar {
        width: min(1200px, calc(100% - 24px));
        margin: 0.7rem auto 0.9rem;
        border-radius: var(--mds-radius);
        border: 1px solid rgba(255, 255, 255, 0.08);
        background: linear-gradient(135deg, rgba(17, 24, 39, 0.96) 0%, rgba(31, 41, 55, 0.96) 100%) !important;
        box-shadow: 0 18px 34px rgba(15, 23, 42, 0.16);
        backdrop-filter: blur(12px);
      }
      .navbar.mds-navbar .container-fluid {
        padding-left: 0.95rem;
        padding-right: 0.95rem;
      }
      .navbar.mds-navbar .navbar-brand {
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        font-weight: 700;
        letter-spacing: -0.02em;
      }
      .navbar.mds-navbar .navbar-brand img {
        height: 42px;
      }
      .navbar.mds-navbar .nav-link {
        color: rgba(255, 255, 255, 0.78) !important;
        font-weight: 600;
        padding: 0.6rem 0.9rem !important;
        border-radius: 999px;
        transition: background-color 160ms ease, color 160ms ease, transform 160ms ease;
      }
      .navbar.mds-navbar .nav-link:hover,
      .navbar.mds-navbar .nav-link:focus {
        color: #fff !important;
        background: rgba(255, 255, 255, 0.08);
        transform: translateY(-1px);
      }
      .navbar.mds-navbar .nav-link.active,
      .navbar.mds-navbar .nav-link[aria-current="page"] {
        color: #fff !important;
        background: rgba(255, 255, 255, 0.11);
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.06);
      }
      .navbar.mds-navbar .navbar-nav {
        align-items: center;
        gap: 0.2rem;
        width: 100%;
      }
      .navbar.mds-navbar .navbar-toggler {
        width: 3rem;
        height: 3rem;
        padding: 0;
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 0.9rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: none;
        position: relative;
        overflow: hidden;
        -webkit-tap-highlight-color: transparent;
        touch-action: manipulation;
      }
      .navbar.mds-navbar .navbar-toggler:hover,
      .navbar.mds-navbar .navbar-toggler:focus-visible {
        background: rgba(255, 255, 255, 0.06);
        border-color: rgba(255, 255, 255, 0.22);
      }
      .navbar.mds-navbar .navbar-toggler-icon {
        width: 1.15rem;
        height: 0.9rem;
        background-image: none;
        position: relative;
        display: block;
      }
      .navbar.mds-navbar .navbar-toggler-icon::before,
      .navbar.mds-navbar .navbar-toggler-icon::after,
      .navbar.mds-navbar .navbar-toggler-icon {
        background-color: #fff;
        border-radius: 999px;
      }
      .navbar.mds-navbar .navbar-toggler-icon::before,
      .navbar.mds-navbar .navbar-toggler-icon::after {
        content: "";
        position: absolute;
        left: 0;
        width: 1.15rem;
        height: 2px;
      }
      .navbar.mds-navbar .navbar-toggler-icon {
        position: absolute;
        top: 50%;
        left: 50%;
        height: 2px;
        transform: translate(-50%, -50%);
      }
      .navbar.mds-navbar .navbar-toggler-icon::before {
        top: -0.35rem;
      }
      .navbar.mds-navbar .navbar-toggler-icon::after {
        top: 0.35rem;
      }
      .navbar.mds-navbar .mds-qr-slot {
        max-width: 104px;
        margin-left: auto;
        padding: 0.3rem;
        border-radius: 0.8rem;
        background: rgba(255, 255, 255, 0.035);
        border: 1px solid rgba(255, 255, 255, 0.06);
      }
      .navbar.mds-navbar .mds-qr-slot figcaption {
        color: rgba(255, 255, 255, 0.72) !important;
        text-align: center;
        font-size: 0.72rem !important;
      }
      @media (min-width: 576px) {
        .navbar.mds-navbar .navbar-toggler {
          display: none !important;
        }
        .navbar.mds-navbar .navbar-collapse {
          display: flex !important;
          flex-basis: auto;
          justify-content: flex-end;
        }
        .navbar.mds-navbar .navbar-nav {
          margin-left: auto;
        }
      }
      @media (max-width: 575.98px) {
        .navbar.mds-navbar .container-fluid {
          flex-wrap: wrap;
          gap: 0.5rem;
        }
        .navbar.mds-navbar .navbar-brand {
          max-width: calc(100% - 3.5rem);
          gap: 0.45rem;
          font-size: 1rem;
          white-space: normal;
          line-height: 1.1;
        }
        .navbar.mds-navbar .navbar-brand img {
          width: 60px;
          height: 32px;
          flex: 0 0 auto;
        }
        .navbar.mds-navbar .navbar-toggler {
          width: 2.75rem;
          height: 2.75rem;
        }
        .navbar.mds-navbar .navbar-collapse {
          flex-basis: 100%;
          padding-top: 0.85rem;
        }
        .navbar.mds-navbar .navbar-nav {
          align-items: stretch;
          gap: 0.35rem;
        }
        .navbar.mds-navbar .mds-qr-slot {
          margin-left: 0;
          max-width: 100%;
        }
      }
      .mds-alert {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.95rem 1rem;
        border-radius: var(--mds-radius);
        border: 1px solid rgba(245, 158, 11, 0.18);
        background: linear-gradient(135deg, rgba(255, 251, 235, 0.98) 0%, rgba(255, 247, 237, 0.94) 100%);
        color: #78350f;
        box-shadow: 0 14px 26px rgba(245, 158, 11, 0.08);
      }
      .mds-alert strong {
        display: block;
        margin-bottom: 0.1rem;
        font-size: 0.98rem;
      }
      .mds-alert p {
        margin: 0;
        color: #92400e;
      }
      .mds-alert .btn-close {
        margin: 0;
      }
    </style>
  </head>

<body class="<?php echo mds_h(implode(' ', $mdsBodyClasses)); ?>">
  <nav class="navbar navbar-expand-sm navbar-dark bg-dark mds-navbar">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?php echo mds_h($publicHomePath); ?>">
      <img src="<?php echo htmlspecialchars(mds_asset_path('img/MDS_Logo_black.png'), ENT_QUOTES, 'UTF-8'); ?>" class="filter-green me-2" width="75" height="40" alt="Maritime Data Server logo">
      Maritime Data Server
    </a>
    <?php if (basename($_SERVER['PHP_SELF']) != "login.php") { ?>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar" aria-controls="navbar" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
    <?php } ?>

    <?php if ((!myFunctions::is_checked_in()) && (basename($_SERVER['PHP_SELF']) != "login.php")) : ?>
      <div id="navbar" class="navbar-collapse collapse">
        <ul class="nav navbar-nav ms-auto">
          <?php if ($seoPageKey !== null) { ?>
            <li class="nav-item"><a class="nav-link" href="<?php echo mds_h(mds_route_path(mds_seo_route('features', mds_current_language()))); ?>"><?php echo mds_h(mds_current_language() === 'de' ? 'Funktionen' : 'Features'); ?></a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo mds_h(mds_route_path(mds_seo_route('ttn', mds_current_language()))); ?>">TTN</a></li>
          <?php } ?>
          <li class="nav-item"><a class="nav-link" href="<?php echo mds_h(mds_route_path('login.php')); ?>"><?php echo mds_h(mds_t('common.login')); ?></a></li>
          <li class="nav-item"><a class="btn btn-success ms-sm-2" href="<?php echo mds_h(mds_route_path('register.php')); ?>"><?php echo mds_h(mds_t('nav.register_now')); ?></a></li>
          <?php if ($seoPageKey !== null) { $otherLanguage = mds_current_language() === 'de' ? 'en' : 'de'; ?>
            <li class="nav-item"><a class="nav-link mds-language-link" hreflang="<?php echo $otherLanguage; ?>" href="<?php echo mds_h(mds_route_path(mds_seo_route($seoPageKey, $otherLanguage))); ?>"><?php echo strtoupper($otherLanguage); ?></a></li>
          <?php } ?>
        </ul>
      </div>

    <?php elseif (basename($_SERVER['PHP_SELF']) != "login.php") : ?>
      <div id="navbar" class="navbar-collapse collapse">
        <ul class="nav navbar-nav mr-auto navbar-right">
          <li class="nav-item"><a class="nav-link<?php echo $mdsCurrentPage === 'internal.php' ? ' active' : ''; ?>"<?php echo $mdsCurrentPage === 'internal.php' ? ' aria-current="page"' : ''; ?> href="<?php echo htmlspecialchars(mds_route_path('internal.php'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(mds_t('nav.my_sensors'), ENT_QUOTES, 'UTF-8'); ?></a></li>
          <li class="nav-item"><a class="nav-link<?php echo $mdsCurrentPage === 'settings.php' ? ' active' : ''; ?>"<?php echo $mdsCurrentPage === 'settings.php' ? ' aria-current="page"' : ''; ?> href="<?php echo htmlspecialchars(mds_route_path('settings.php'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(mds_t('nav.settings'), ENT_QUOTES, 'UTF-8'); ?></a></li>
          <li class="nav-item"><a class="nav-link" href="<?php echo htmlspecialchars(mds_route_path('logout.php'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(mds_t('nav.logout'), ENT_QUOTES, 'UTF-8'); ?></a></li>
          <?php if ($config::$ShowQrCode == "1") { ?>
          <li class="nav-item mds-qr-slot">
                <figure class="mb-0" >
                  <img src="<?php echo htmlspecialchars(mds_asset_path('img/qr-code.png'), ENT_QUOTES, 'UTF-8'); ?>" class="img-fluid" alt="derguntmar.de">
                  <figcaption style="color: white; font-size: 0.8rem">derguntmar.de</figcaption>
                </figure>
          </li>
          <?php } ?>
        </ul>
      </div>
    <?php endif; ?>
  </div>
  </nav>
