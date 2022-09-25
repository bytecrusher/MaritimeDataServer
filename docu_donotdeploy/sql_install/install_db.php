<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Install script</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
  <link href="../../frontend/css/style.css" rel="stylesheet">
</head>

<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="index.php"><i class="bi bi-speedometer logo"></i> Mausel Industries</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar" aria-controls="navbar" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div id="navbar" class="navbar-collapse collapse">

    </div>
  </nav>

  <div class="container main-container">
    <div class="mb-2">
    With this installer the DB will be prepared for operating on the server.<br>
    First create a database and a user with write privileges on this db.
    </div>
    <div>
      <form class="navbar-form navbar-right" action="install_db.php" method="post">
        <div class="form-group row">
          <label for="hostname" class="col-sm-2 col-form-label">Hostname</label>
          <div class="col-sm-4">
            <input type="text" class="form-control" id="hostname" value="localhost">
          </div>
        </div>
        <div class="form-group row">
          <label for="sqlusername" class="col-sm-2 col-form-label">mysql username</label>
          <div class="col-sm-4">
            <input type="text" class="form-control" id="sqlusername">
          </div>
        </div>
        <div class="form-group row">
          <label for="mysqlpassword" class="col-sm-2 col-form-label">mysql password</label>
          <div class="col-sm-4">
            <input type="text" class="form-control" id="mysqlpassword">
          </div>
        </div>
        <div class="form-group row">
          <label for="mysqldbname" class="col-sm-2 col-form-label">mysql database name</label>
          <div class="col-sm-4">
            <input type="text" class="form-control" id="mysqldbname">
          </div>
        </div>
        <button name="foo" value="upvote">next</button>
      </form>
    </div>
  </div>

  <div class="container">
    <hr>
    <footer>
      <p>Powered by <a href="http://www.derguntmar.de" target="_blank">derguntmar.de</a></p>
    </footer>
  </div>

  <script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>