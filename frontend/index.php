<?php
  session_start();
  require_once("func/dbConfig.func.php");
  require_once("func/myFunctions.func.php");
  require_once("func/user.class.php");
  include("common/header.inc.php");

  if (isset($_SESSION['userobj'])) {
    $userobj = unserialize($_SESSION['userobj']);
  } else {
    $userobj = false;
  }

?>
    <div class="jumbotron" style="padding: 1rem 1rem; margin-bottom: 1rem;">
      <div class="container">
        <h1>Welcome to MDS (MarineDataServer)</h1>
      </div>
    </div>

    <div class="container">
      <div class="row">
        <div class="col-md-4">
          <h2>About</h2>
          <p>MDS (MarineDataServer) is for visualize sensor data.</p>
          <p>My first data collector is a ESP32 with serveral sensors.</p>
          <p>The ESP32 sends the data via Wifi to this server.</p>
          <ul>
          	<li>Accessing personal data after login.</li>
          	<li>Graphical display of sensor data.</li>
          	<li>Sends messages via email and telegram on over- or under hitting a defindet limit value (function comes later).</li>
          	<li>Responsive web design perfect for PC, tablet and mobile devices.</li>
          </ul>
        </div>
        <div class="col-md-4">
          <h2>Documentation</h2>
          <p>After your account registratoin, your MDC (MarineDataCollector) module must be linked to your account.</p>
          <p>The module can then be configured.</p>
       </div>
      </div>
	</div>

<?php
include("common/footer.inc.php")
?>
