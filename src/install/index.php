<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Install script</title>

  <?php include(__DIR__ . "/../frontend/common/includes.php"); ?>

  <style>
    #pageMessages {
    position: fixed;
    bottom: 15px;
    right: 15px;
    width: 30%;
  }

  .alert {
    position: relative;
  }

  .alert .close {
    position: absolute;
    top: 5px;
    right: 5px;
    font-size: 1em;
  }

  .alert .fa {
    margin-right:.3em;
  }

  input:invalid {
    border-color: #900;
    background-color: #fdd;
  }
  </style>
</head>

<body>
  <nav class="navbar navbar-expand-sm navbar-dark bg-dark">
    <div class="container-fluid">
      <a class="navbar-brand" href="../index.php"><i class="bi bi-speedometer logo"> </i> Mausel Industries</a>
      <div id="navbar" class="navbar-collapse collapse">
      </div>
    </div>
  </nav>

  <?php
    $var_dbName = $var_dbUserName = $var_dbPassword = $var_apiKey = $var_md5secretString = null;
    $var_dbHostName = "localhost";

    require_once(__DIR__ . '/../config/configuration.php');

    $config = new configuration();
    //$var_dbHost = $config::$dbHost;
    $var_dbHostName = $config::$dbHost;
    $var_dbName = $config::$dbName;
    $var_dbUserName = $config::$dbUser;
    $var_dbPassword = $config::$dbPassword;
    $var_apiKey = $config::$apiKey;
    $var_md5secretString = $config::$md5secretString;
  ?>

  <div class="container mt-3">
    <div id="pageMessages">
      <?php
      if(isset($success_msg) && !empty($success_msg)):
      ?>
        <div class="alert alert-success" id="success-alert">
          <a href="#" class="close" data-bs-dismiss="alert" aria-label="close">&times;</a>
          <?php echo $success_msg; ?><strong>Success!</strong> Your message has been sent successfully.
        </div>
      <?php
      endif; 
      if(isset($error_msg) && !empty($error_msg)):
      ?>
        <div class="alert alert-danger" id="danger-alert">
          <a href="#" class="close" data-bs-dismiss="alert" aria-label="close">&times;</a>
          <?php echo $error_msg; ?>
        </div>
      <?php 
      endif;
      ?>
    </div>
  </div>

  <div class="container main-container">
  <h1>Install MDS</h1>
    
    <!-- Nav tabs -->
    <ul class="nav nav-tabs">
      <li class="nav-item">
        <div class="nav-link active" data-bs-toggle="tab" href="#Intro" disabled style="color:black">Intro</div>
      </li>
      <li class="nav-item">
        <div class="nav-link" data-bs-toggle="tab" href="#Database" disabled style="color:black" id="database">Database</div>
      </li>
      <li class="nav-item">
        <div class="nav-link" data-bs-toggle="tab" href="#Config" disabled style="color:black" id="adminUser">Config</div>
      </li>
      <li class="nav-item">
        <div class="nav-link" data-bs-toggle="tab" href="#Done" disabled style="color:black" id="done">Done</div>
      </li>
    </ul>

    <!-- Tab panes -->
    <div class="tab-content">
      <div class="tab-pane container active" id="Intro">
        <div class="mb-2 mt-2">
          This Script will help you to install and configure MDS on your Server.<br>
          Please follow the next steps and remove the "install" dir after finishing all steps.
        </div>
        <div class="form-group row justify-content-evenly">
          <a class="col-sm-3 me-3 btn btn-primary btnNext">Next</a>
        </div>

      </div>
      <div class="tab-pane container fade" id="Database">
        <div class="mb-2 mt-2">
          In this step the DB will be prepared for operating on the server.<br>
          First create a database and a user with write privileges in your DB admin panel.
          <div>
            <form class="navbar-form navbar-right" action="install_db.php" method="post">
              <div class="form-group row">
                <label for="dbHostName" class="col-sm-4 col-form-label">Database Hostname</label>
                <div class="col-sm-4">
                  <input type="text" class="form-control" id="dbHostName" name="dbHostName" value="<?php echo $var_dbHostName;?>"  required>
                </div>
              </div>
              <div class="form-group row">
                <label for="dbName" class="col-sm-4 col-form-label">Database Name</label>
                <div class="col-sm-4">
                  <input type="text" class="form-control" id="dbName" name="dbName" value="<?php echo $var_dbName;?>" required>
                </div>
              </div>
              <div class="form-group row">
                <label for="dbUserName" class="col-sm-4 col-form-label">Database Username</label>
                <div class="col-sm-4">
                  <input type="text" class="form-control" id="dbUserName" name="dbUserName" value="<?php echo $var_dbUserName;?>" required>
                </div>
              </div>
              <div class="form-group row">
                <label for="dbPassword" class="col-sm-4 col-form-label">Database Password</label>
                <div class="col-sm-4">
                  <input type="password" class="form-control" id="dbPassword" name="dbPassword" required>
                </div>
              </div>
              <div class="form-group row justify-content-evenly">
                <button type="button" name="action" class="col-sm-3 me-3 btn btn-primary" onclick="check_db();">Test db connection</button>
                <a class="col-sm-3 me-3 btn btn-primary btnNext">Next</a>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="tab-pane container fade" id="Config">
        <div class="mb-2 mt-2">
          Enter Admin User information.<br>
        </div>
        <div class="form-group row">
          <label for="firstName" class="col-sm-4 col-form-label">First name</label>
          <div class="col-sm-4">
            <input type="text" class="form-control" id="firstName" name="firstName" required>
          </div>
        </div>
        
        <div class="form-group row">
          <label for="lastName" class="col-sm-4 col-form-label">Last name</label>
          <div class="col-sm-4">
            <input type="text" class="form-control" id="lastName" name="lastName" required>
          </div>
        </div>

        <div class="form-group row">
          <label for="email" class="col-sm-4 col-form-label">E-Mail</label>
          <div class="col-sm-4">
            <input type="text" class="form-control" id="email" name="email" required>
          </div>
        </div>

        <div class="form-group row">
          <label for="password" class="col-sm-4 col-form-label">Password</label>
          <div class="col-sm-4">
            <input type="password" class="form-control" id="password" name="password" required>
          </div>
        </div>

        <div class="form-group row">
          <label for="password2" class="col-sm-4 col-form-label">Password repeat</label>
          <div class="col-sm-4">
            <input type="password" class="form-control" id="password2" name="password2" required>
          </div>
        </div>

        <div class="form-group row">
          <label for="apiKey" class="col-sm-4 col-form-label">API key</label>
          <div class="col-sm-4">
            <input type="text" class="form-control" id="apiKey" name="apiKey" pattern="^[_A-Za-z0-9\-]{16,32}" maxlength="32" title="Mindestens 16, Höchstens 32 Zeichen sowie Groß und/oder Kleinbuchstaben, Zahlen und Bindestriche." value="<?php echo $var_apiKey;?>" required>
          </div>
          <button type="button" name="action" class="col-sm-2 me-3 btn btn-primary" onclick="document.getElementById('apiKey').value = generatePassword(32);">generate</button>
        </div>

        <div class="form-group row">
          <label for="md5secretString" class="col-sm-4 col-form-label">Your Secret String (Replace with a string of your choice (>12 characters))</label>
          <div class="col-sm-4">
            <input type="text" class="form-control" id="md5secretString" name="md5secretString" pattern="^[_A-Za-z0-9\-]{16,32}" maxlength="32" title="Mindestens 16, Höchstens 32 Zeichen sowie Groß und/oder Kleinbuchstaben, Zahlen und Bindestriche." value="<?php echo $var_md5secretString;?>" required>
          </div>
          <button type="button" name="action" class="col-sm-2 me-3 btn btn-primary" onclick="document.getElementById('md5secretString').value = generatePassword(32);">generate</button>
        </div>

        <div class="form-group row justify-content-evenly">
          <a class="col-sm-3 me-3 btn btn-primary btnNext">Next</a>
        </div>
      </div>

      <div class="tab-pane container" id="Done">
        <div class="mb-2 mt-2">
          Installation Done.<br>
          Remember to remove the dir named "install".<br>
          <a href='./../frontend/index.php'>Login</a>
        </div>
      </div>

    </div>
  </div>

<script>
  $('.btnNext').click(function() {
  const currentTab = $('.nav-tabs .active').attr('id');
  if (currentTab === "database") {
    returnVal = write_db();
    const nextTabLinkEl = $('.nav-tabs .active').closest('li').next('li').find('div')[0];
    const nextTab = new bootstrap.Tab(nextTabLinkEl);
    nextTab.show();    
  } else if (currentTab === "adminUser") {
    returnVal = apiPostCreateAdmin();
    if (returnVal) {
      const nextTabLinkEl = $('.nav-tabs .active').closest('li').next('li').find('div')[0];
      const nextTab = new bootstrap.Tab(nextTabLinkEl);
      nextTab.show();
    }
  } else {
    const nextTabLinkEl = $('.nav-tabs .active').closest('li').next('li').find('div')[0];
    const nextTab = new bootstrap.Tab(nextTabLinkEl);
    nextTab.show();
  }
});

$('.btnPrevious').click(function() {
  const prevTabLinkEl = $('.nav-tabs .active').closest('li').prev('li').find('div')[0];
  const prevTab = new bootstrap.Tab(prevTabLinkEl);
  prevTab.show();
});

const nameField = document.getElementById("dbName");

nameField.addEventListener("input", () => {
  nameField.setCustomValidity("");
  nameField.checkValidity();
  console.log(nameField.checkValidity());
});

nameField.addEventListener("invalid", () => {
  nameField.setCustomValidity("Please fill in your First Name.");
});

function check_db() { 
  action = "testdb";
  myReturn = api_post_db(action);
  return myReturn;
}

function write_db() { 
  action = "savedb";
  return api_post_db(action);
}

function api_post_db(action) {
  if ( ($("#dbHostName").val() != "") && ($("#dbName").val() != "") && ($("#dbUserName").val() != "") && ($("#dbPassword").val() != "") ) {
    console.log("dbName nicht leer");
    dbHostName = $("#dbHostName").val();
    dbName = $("#dbName").val();
    dbUserName = $("#dbUserName").val();
    dbPassword = $("#dbPassword").val();
    let text;
    var obj;
    myReturnVal = null;

    $.ajax({
      method: "POST",
      async: false,
      url: "api_checkDbConnection.php",
      data: { action: action, dbHostName: dbHostName, dbName: dbName, dbUserName: dbUserName, dbPassword: dbPassword }
    })
    .done(function( response ) {
      text = response;
      obj = JSON.parse(text);
      if(obj["error"] === "true"){
        createAlert('Something went wrong',obj["error_text"],'danger',true,true,'pageMessages');
        myReturnVal = false;
      } else if (obj["error"] === "false"){
        createAlert('Nice Work!',obj["success_text"],'success',true,true,'pageMessages');
        myReturnVal = true;
      }
    });
  } else {
    createAlert('At least one input missing.','Please fill out all necessary fields.','danger',true,true,'pageMessages');
    myReturnVal = false;
  }
  return myReturnVal;
}

function apiPostCreateAdmin() {
  action = "createadmin";
  myReturnVal = null;
  if ( ($("#firstName").val() != "") && ($("#lastName").val() != "") && ($("#email").val() != "") && ($("#password").val() != "") && ($("#password2").val() != "") && ($("#md5secretString").val() != "") ) {
    firstName = $("#firstName").val();
    lastName = $("#lastName").val();
    email = $("#email").val();
    password = $("#password").val();
    password2 = $("#password2").val();
    apiKey = $("#apiKey").val();
    md5secretString = $("#md5secretString").val();
    $.ajax({
      method: "POST",
      async: false,
      url: "api_createAdmin.php",
      data: { action: action, firstName: firstName, lastName: lastName, email: email, password: password, password2: password2, apiKey: apiKey, md5secretString: md5secretString, demoMode: false }
    })
    .done(function( response ) {
      text = response;
      obj = JSON.parse(text);
      if(obj["error"] === "true"){
        createAlert('Something went wrong',obj["error_text"],'danger',true,true,'pageMessages');
        myReturnVal = false;
      } else if (obj["error"] === "false"){
        createAlert('Nice Work!',obj["success_text"],'success',true,true,'pageMessages');
        myReturnVal = true;
      } else {
        createAlert('Great.','Great.','success',true,true,'pageMessages');
      }
    });
  } else {
    createAlert('At least one input missing.','Please fill out all necessary fields.','danger',true,true,'pageMessages');
    myReturnVal = false;
  }
  return myReturnVal;
}

function createAlert(summary, details, severity, dismissible, autoDismiss, appendToId) {
  var iconMap = {
    info: "bi bi-info-circle me-2",
    success: "bi bi-hand-thumbs-up me-2",
    warning: "bi bi-exclamation-triangle me-2",
    danger: "bi bi-exclamation-circle me-2"
  };
  var iconAdded = false;
  var alertClasses = ["alert", "animated", "flipInX"];
  alertClasses.push("alert-" + severity.toLowerCase());

  if (dismissible) {
    alertClasses.push("alert-dismissible");
  }

  var msgIcon = $(" <i /> ", {
    "class": iconMap[severity] // you need to quote "class" since it's a reserved keyword
  });

  var msg = $("<div />", {
    "class": alertClasses.join(" ") // you need to quote "class" since it's a reserved keyword
  });

  if (summary) {
    var msgSummary = $("<strong />", {
      html: summary
    }).appendTo(msg);
    
    if(!iconAdded){
      msgSummary.prepend(msgIcon);
      iconAdded = true;
    }
  }

  if (details) {
    var msgDetails = $("<p />", {
      html: details
    }).appendTo(msg);
    
    if(!iconAdded){
      msgDetails.prepend(msgIcon);
      iconAdded = true;
    }
  }
  
  if (dismissible) {
    var msgClose = $("<span />", {
      "class": "close", // you need to quote "class" since it's a reserved keyword
      "data-bs-dismiss": "alert",
      "aria-label": "close",
      html: "&times;"
    }).appendTo(msg);
  }
  
  $('#' + appendToId).prepend(msg);
  
  if(autoDismiss){
    setTimeout(function(){
      msg.addClass("flipOutX");
      setTimeout(function(){
        msg.remove();
      },1000);
    }, 5000);
  }
}

function generatePassword(length) {
  let result = '';
  const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-';
  const charactersLength = characters.length;
  let counter = 0;
  while (counter < length) {
    result += characters.charAt(Math.floor(Math.random() * charactersLength));
    counter += 1;
  }
  return result;
}
</script>
<?php include(__DIR__ . "/../frontend/common/footer.inc.php"); ?>
</body>
</html>