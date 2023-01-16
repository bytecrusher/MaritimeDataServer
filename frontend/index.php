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
        <h1>Welcome to MDS (Maritime Data Server)</h1>
      </div>
    </div>

    <div class="container">
      <div class="row">
        <div class="col-md-6">
          <h2>About</h2>
          <p>MDS (Maritime Data Server) is used to store and visualize sensor data (temperature, voltage,...) that was send from a smal devices.</p>
          <p>My first data collector is an ESP32 with several sensors (DS18B20).</p>
          <p>The ESP32 sends the data via Wifi and/or LoRa to the MDS.</p>
          <ul>
          	<li>Accessing to personal dashboard after registration.</li>
          	<li>Graphical representation of your sensor data.</li>
          	<li>Sends messages via e-mail and telegram if a defined limit value is exceeded or not reached (function comes later).</li>
          	<li>Responsive web design perfect for PC, tablet and mobile devices.</li>
          </ul>
        </div>
        <div class="col-md-6">
          <h2>Documentation</h2>
          <p>After your account registration, your MDC module (Maritime Data Collector, LoRa boat monitor or other ESP32 device) needs to be linked to your account.</p>
          <p>The module can then be configured.</p>
       </div>
      </div>
	</div>

<?php
include("common/footer.inc.php")
?>
