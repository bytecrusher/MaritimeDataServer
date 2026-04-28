<!DOCTYPE html>
<html lang="de">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars(configuration::$applicationName ?: 'MDS - Maritime Data Server', ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars(mds_route_path('favicon.ico'), ENT_QUOTES, 'UTF-8'); ?>">
    <?php
      $config = new configuration();
      include(__DIR__ . "/includes.php");

      if (isset($_SERVER['HTTPS']) &&
            ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
            isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
            $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
            $prefix = 'https://';
        }
        else {
            $prefix = 'http://';
        }

      $actualLink = $prefix . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/');
      $publicInstallPath = mds_route_path('install/index.php');

      if (!$config::$config_exist) {
        if (is_dir(dirname(__DIR__, 3) . "/public/install")) {
          header("Location: " . $publicInstallPath);
          exit();
        } else {
          echo ("Config file and install folder not found. Please reinstall.");
          exit();
        }
      }
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
      .navbar.mds-navbar .navbar-toggler {
        border: 1px solid rgba(255, 255, 255, 0.15);
        box-shadow: none;
      }
      .navbar.mds-navbar .navbar-toggler-icon {
        width: 1.15rem;
        height: 1.15rem;
        background-image: none;
        position: relative;
      }
      .navbar.mds-navbar .navbar-toggler-icon::before,
      .navbar.mds-navbar .navbar-toggler-icon::after,
      .navbar.mds-navbar .navbar-toggler-icon {
        display: block;
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
        height: 2px;
        margin-top: 0.45rem;
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

<body>
  <nav class="navbar navbar-expand-sm navbar-dark bg-dark mds-navbar">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?php echo htmlspecialchars(mds_route_path('index.php'), ENT_QUOTES, 'UTF-8'); ?>">
      <img src="<?php echo htmlspecialchars(mds_asset_path('img/MDS_Logo_black.png'), ENT_QUOTES, 'UTF-8'); ?>" class="filter-green me-2" height="40px" />
      Mausel Industries
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar" aria-controls="navbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon">.</span>
    </button>

    <?php if ((!myFunctions::is_checked_in()) && (basename($_SERVER['PHP_SELF']) != "login.php")) : ?>
      <div id="navbar" class="navbar-collapse collapse">
        <form class="navbar-form navbar-right" action="<?php echo htmlspecialchars(mds_route_path('login.php'), ENT_QUOTES, 'UTF-8'); ?>" method="post">
          <?php echo mds_csrf_input(); ?>
          <table class="login" role="presentation">
            <tbody>
              <tr>
                <td>
                  <div class="input-group">
                    <input class="form-control" placeholder="E-Mail" name="email" type="email" required>
                  </div>
                </td>
                <td><input class="form-control" placeholder="Password" name="password" type="password" value="" required></td>
                <td><button type="submit" class="btn btn-success">Login</button></td>
                <td><a class="btn btn-primary" href="<?php echo htmlspecialchars(mds_route_path('register.php'), ENT_QUOTES, 'UTF-8'); ?>" role="button">Register now</a></td>
              </tr>
              <tr>
                <td><label style="margin-bottom: 0px; font-weight: normal;"><input type="checkbox" name="angemeldet_bleiben" value="remember-me" title="Angemeldet bleiben" style="margin: 0; vertical-align: middle;" /> <small>remember login for 30 days</small></label></td>
                <td><small><a href="<?php echo htmlspecialchars(mds_route_path('resetPassword.php'), ENT_QUOTES, 'UTF-8'); ?>">Reset Password</a></small></td>
                <td></td>
              </tr>
            </tbody>
          </table>
        </form>
      </div>

    <?php elseif (basename($_SERVER['PHP_SELF']) != "login.php") : ?>
      <div id="navbar" class="navbar-collapse collapse">
        <ul class="nav navbar-nav mr-auto navbar-right">
          <li class="nav-item"><a class="nav-link" href="<?php echo htmlspecialchars(mds_route_path('internal.php'), ENT_QUOTES, 'UTF-8'); ?>">My Sensors</a></li>
          <li class="nav-item"><a class="nav-link" href="<?php echo htmlspecialchars(mds_route_path('settings.php'), ENT_QUOTES, 'UTF-8'); ?>">Settings</a></li>
          <li class="nav-item"><a class="nav-link" href="<?php echo htmlspecialchars(mds_route_path('privacy.php'), ENT_QUOTES, 'UTF-8'); ?>">Privacy</a></li>
          <li class="nav-item"><a class="nav-link" href="<?php echo htmlspecialchars(mds_route_path('imprint.php'), ENT_QUOTES, 'UTF-8'); ?>">Imprint</a></li>
          <li class="nav-item"><a class="nav-link" href="<?php echo htmlspecialchars(mds_route_path('logout.php'), ENT_QUOTES, 'UTF-8'); ?>">Logout</a></li>
          <li class="nav-item container mds-qr-slot">
            <?php
              if ($config::$ShowQrCode == "1") {
            ?>
                <figure class="mb-0" >
                  <img src="<?php echo htmlspecialchars(mds_asset_path('img/qr-code.png'), ENT_QUOTES, 'UTF-8'); ?>" class="img-fluid" alt="derguntmar.de">
                  <figcaption style="color: white; font-size: 0.8rem">derguntmar.de</figcaption>
                </figure>
            <?php
            }
            ?>
          </li>
        </ul>
      </div>
    <?php endif; ?>
  </div>
  </nav>
