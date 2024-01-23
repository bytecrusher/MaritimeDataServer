<!DOCTYPE html>
<html lang="de">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>MDS - Maritime Data Server</title>
    <?php
      $config = new configuration();
      include(__DIR__ . "/includes.php");
      if (!$config::$config_exist) {
        header("Location: /../../install/index.php");
        exit();
      }
    ?>
    <style>
      .filter-green {
        filter: invert(66%) sepia(16%) saturate(1367%) hue-rotate(71deg) brightness(93%) contrast(89%);
      }
      </style>
  </head>

<body>
  <nav class="navbar navbar-expand-sm navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">
      <img src="./img/MDS_Logo_black.png" class="filter-green me-2" height="40px" />
      Mausel Industries
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar" aria-controls="navbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon">.</span>
    </button>

    <?php if ((!myFunctions::is_checked_in()) && (basename($_SERVER['PHP_SELF']) != "login.php")) : ?>
      <div id="navbar" class="navbar-collapse collapse">
        <form class="navbar-form navbar-right" action="login.php" method="post">
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
                <td><a class="btn btn-primary" href="register.php" role="button">Register now</a></td>
              </tr>
              <tr>
                <td><label style="margin-bottom: 0px; font-weight: normal;"><input type="checkbox" name="angemeldet_bleiben" value="remember-me" title="Angemeldet bleiben" checked="checked" style="margin: 0; vertical-align: middle;" /> <small>remember login </small></label></td>
                <td><small><a href="resetPassword.php">Reset Password</a></small></td>
                <td></td>
              </tr>
            </tbody>
          </table>
        </form>
      </div>

    <?php elseif (basename($_SERVER['PHP_SELF']) != "login.php") : ?>
      <div id="navbar" class="navbar-collapse collapse">
        <ul class="nav navbar-nav mr-auto navbar-right">
          <li class="nav-item"><a class="nav-link" href="internal.php">My Sensors</a></li>
          <li class="nav-item"><a class="nav-link" href="settings.php">Settings</a></li>
          <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
          <li class="nav-item container" style="max-width: 10%; position: absolute; right: 20px; background-color: var(--bs-body-color)">
            <?php
              if ($config::$ShowQrCode == "1") {
            ?>
                <figure class="mb-0" >
                  <img src="./img/qr-code.png" class="img-fluid" alt="derguntmar.de">
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