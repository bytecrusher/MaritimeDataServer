<!DOCTYPE html>
<html lang="de">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Loginscript</title>
    <?php
      include("includes.php");
    ?>
  </head>

<body>
  <nav class="navbar navbar-expand-sm navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php"><i class="bi bi-speedometer logo"> </i> Mausel Industries</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar" aria-controls="navbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon">.</span>
    </button>

    <?php if (!myFunctions::is_checked_in()) : ?>
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

    <?php else : ?>
      <div id="navbar" class="navbar-collapse collapse">
        <ul class="nav navbar-nav mr-auto navbar-right">
          <li class="nav-item"><a class="nav-link" href="internal.php">My Sensors</a></li>
          <li class="nav-item"><a class="nav-link" href="settings.php">Settings</a></li>
          <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
        </ul>
      </div>
    <?php endif; ?>
  </div>
  </nav>
