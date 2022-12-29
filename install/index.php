<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Install script</title>

  <link rel="stylesheet" href="../node_modules/bootstrap/dist/css/bootstrap.min.css">
  <link href="../frontend/css/style.css" rel="stylesheet">
  <link rel="stylesheet" href="../node_modules/bootstrap-icons/font/bootstrap-icons.css">
  <link rel="stylesheet" href="../node_modules/jquery-ui/dist/themes/base/jquery-ui.css">
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
    $var_dbname = $var_dbusername = $var_dbpassword = $var_apikey = $var_md5secretstring = null;
    $var_dbhostname = "localhost";

    require_once(__DIR__ . '/../configuration.php');

    $config = new configuration();
    $vardb_host = $config::$db_host;
    $var_dbhostname = $config::$db_host;
    $var_dbname = $config::$db_name;
    $var_dbusername = $config::$db_user;
    $var_dbpassword = $config::$db_password;
    $var_apikey = $config::$api_key;
    $var_md5secretstring = $config::$md5secretstring;
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
        <div class="nav-link" data-bs-toggle="tab" href="#Config" disabled style="color:black" id="adminuser">Config</div>
      </li>
      <li class="nav-item">
        <div class="nav-link" data-bs-toggle="tab" href="#Done" disabled style="color:black" id="done">Done</div>
      </li>
    </ul>

    <!-- Tab panes -->
    <div class="tab-content">
      <div class="tab-pane container active" id="Intro">
        <div class="mb-2 mt-2">
          This Script will help you to install MDS on your Server.<br>
          Please follow the next steps and remove the "install" dir after finishing all steps.
        </div>
        <div class="form-group row justify-content-evenly">
          <a class="col-sm-3 me-3 btn btn-primary btnNext">Next</a>
        </div>

      </div>
      <div class="tab-pane container fade" id="Database">
        <div class="mb-2 mt-2">
          With this installer the DB will be prepared for operating on the server.<br>
          First create a database and a user with write privileges on this db.
          <div>
            <form class="navbar-form navbar-right" action="install_db.php" method="post">
              <div class="form-group row">
                <label for="dbhostname" class="col-sm-4 col-form-label">DB Hostname</label>
                <div class="col-sm-4">
                  <input type="text" class="form-control" id="dbhostname" name="dbhostname" value="<?php echo $var_dbhostname;?>"  required>
                </div>
              </div>
              <div class="form-group row">
                <label for="dbname" class="col-sm-4 col-form-label">Database name</label>
                <div class="col-sm-4">
                  <input type="text" class="form-control" id="dbname" name="dbname" value="<?php echo $var_dbname;?>" required>
                </div>
              </div>
              <div class="form-group row">
                <label for="dbusername" class="col-sm-4 col-form-label">Database Username</label>
                <div class="col-sm-4">
                  <input type="text" class="form-control" id="dbusername" name="dbusername" value="<?php echo $var_dbusername;?>" required>
                </div>
              </div>
              <div class="form-group row">
                <label for="dbpassword" class="col-sm-4 col-form-label">mysql password</label>
                <div class="col-sm-4">
                  <input type="password" class="form-control" id="dbpassword" name="dbpassword" required>
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
        <div class="form-group row">
          <label for="firstname" class="col-sm-4 col-form-label">Firstname</label>
          <div class="col-sm-4">
            <input type="text" class="form-control" id="firstname" name="firstname" required>
          </div>
        </div>
        
        <div class="form-group row">
          <label for="lastname" class="col-sm-4 col-form-label">Lastname</label>
          <div class="col-sm-4">
            <input type="text" class="form-control" id="lastname" name="lastname" required>
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
          <label for="apikey" class="col-sm-4 col-form-label">API key</label>
          <div class="col-sm-4">
            <input type="text" class="form-control" id="apikey" name="apikey" value="<?php echo $var_apikey;?>" required>
          </div>
        </div>

        <div class="form-group row">
          <label for="md5secretstring" class="col-sm-4 col-form-label">Your Secret String (Replace your_secret_string with a string of your choice (>12 characters))</label>
          <div class="col-sm-4">
            <input type="text" class="form-control" id="md5secretstring" name="md5secretstring" value="<?php echo $var_md5secretstring;?>" required>
          </div>
        </div>

        <div class="form-group row justify-content-evenly">
          <a class="col-sm-3 me-3 btn btn-primary btnNext">Next</a>
        </div>
      </div>

      <div class="tab-pane container" id="Done">
        <div class="mb-2 mt-2">
          Installation Done.<br>
          Remember to remove the dir named "install".
        </div>
      </div>

    </div>
  </div>

  <div class="container mt-3">
    <hr style="color: lightgrey; opacity: 100">
    <footer>
      <p>Powered by <a href="http://www.derguntmar.de" target="_blank">derguntmar.de</a></p>
    </footer>
  </div>
  <script src="../node_modules/jquery/dist/jquery.js"></script>
  <script src="../node_modules/@popperjs/core/dist/umd/popper.min.js"></script>
  <script src="../node_modules/bootstrap/dist/js/bootstrap.min.js"></script>
  <script src="../node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>
<script>
  $('.btnNext').click(function() {
  const currentTab = $('.nav-tabs .active').attr('id');
  if (currentTab === "database") {
    returnval = write_db();
    //returnval = check_db();
    if (returnval) {
      const nextTabLinkEl = $('.nav-tabs .active').closest('li').next('li').find('div')[0];
      const nextTab = new bootstrap.Tab(nextTabLinkEl);
      nextTab.show();
    }
    
  } else if (currentTab === "adminuser") {
    returnval = api_post_createadmin();
    if (returnval) {
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

const nameField = document.getElementById("dbname");

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
  myreturn = api_post_db(action);
  return myreturn;
}

function write_db() { 
  action = "savedb";
  return api_post_db(action);
}

function api_post_db(action) {
  if ( ($("#dbhostname").val() != "") && ($("#dbname").val() != "") && ($("#dbusername").val() != "") && ($("#dbpassword").val() != "") ) {
    console.log("dbname nicht leer");
    dbhostname = $("#dbhostname").val();
    dbname = $("#dbname").val();
    dbusername = $("#dbusername").val();
    dbpassword = $("#dbpassword").val();
    let text;
    var obj;
    myreturnval = null;

    $.ajax({
      method: "POST",
      async: false,
      url: "api_check_db_connection.php",
      data: { action: action, dbhostname: dbhostname, dbname: dbname, dbusername: dbusername, dbpassword: dbpassword }
    })
    .done(function( response ) {
      text = response;
      obj = JSON.parse(text);
      if(obj["error"] === "true"){
        createAlert('Something went wrong',obj["error_text"],'danger',true,true,'pageMessages');
        myreturnval = false;
      } else if (obj["error"] === "false"){
        createAlert('Nice Work!',obj["success_text"],'success',true,true,'pageMessages');
        myreturnval = true;
      }
    });
  } else {
    createAlert('At least one input missing.','Please fill out all necessary fields.','danger',true,true,'pageMessages');
    myreturnval = false;
  }
  return myreturnval;
}

function api_post_createadmin() {
  action = "createadmin";
  myreturnval = null;
  if ( ($("#firstname").val() != "") && ($("#lastname").val() != "") && ($("#email").val() != "") && ($("#password").val() != "") && ($("#password2").val() != "") ) {
    firstname = $("#firstname").val();
    lastname = $("#lastname").val();
    email = $("#email").val();
    password = $("#password").val();
    password2 = $("#password2").val();
    apikey = $("#apikey").val();
    // TODO add user to DB
    $.ajax({
      method: "POST",
      async: false,
      url: "api_createadmin.php",
      data: { action: action, firstname: firstname, lastname: lastname, email: email, password: password, password2: password2, apikey: apikey }
    })
    .done(function( response ) {
      text = response;
      obj = JSON.parse(text);
      if(obj["error"] === "true"){
        createAlert('Something went wrong',obj["error_text"],'danger',true,true,'pageMessages');
        myreturnval = false;
      } else if (obj["error"] === "false"){
        createAlert('Nice Work!',obj["success_text"],'success',true,true,'pageMessages');
        myreturnval = true;
      }
    });
    createAlert('Great.','Great.','success',true,true,'pageMessages');
  } else {
    createAlert('At least one input missing.','Please fill out all necessary fields.','danger',true,true,'pageMessages');
    myreturnval = false;
  }
  return myreturnval;
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
</script>
</body>
</html>