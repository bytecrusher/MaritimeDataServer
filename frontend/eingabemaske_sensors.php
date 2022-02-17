<?php
  session_start();
  require_once("func/dbConfig.func.php");
  require_once("func/myFunctions.func.php");
  require_once("func/user.class.php");
  include("common/header.inc.php");

  // ToDo nach dem Speichern gelangt man derzeit auf die settings seite, und nicht die board übersicht.
  $user = user::check_user();
?>
<div class="jumbotron" style="padding: 1rem 1rem; margin-bottom: 1rem;">
	<div class="container">
    <h1>Edit sensor</h1>
  </div>
</div>
<div class="container main-container">
  <form method='post' action='settings.php#confSensors' class='form-horizontal'>
    <?php
      $pdo = dbConfig::getInstance();
      $statement = $pdo->prepare("SELECT * FROM sensorconfig WHERE id=$_GET[id]");
      $result = $statement->execute();
      // Get sensor type data
      $sensortyps = $pdo->prepare("SELECT * FROM sensortypes ORDER BY id ");
      $resultsensortyps = $sensortyps->execute();

      while($row = $statement->fetch()) {
    ?>
          <div class="row mb-2">
            <label for='id' class='col col-sm-4 col-form-label'>Id</label>
            <div class='col col-sm-4'>
            <input type='text' readonly class='form-control' id='id' name='id' value='<?=$row['id'];?>'>
            </div>
          </div>

          <div class="row mb-2">
            <label for='macaddress' class='col col-sm-4 col-form-label'>ID Macaddress</label>
            <div class='col col-sm-4'>
              <input type='text' readonly class='form-control' id='macaddress' name='macaddress' value='<?=$row['boardid'];?>'>
            </div>
          </div>

          <div class="row mb-2">
            <label for='sensorid' class='col col-sm-4 col-form-label'>Sensor Address</label>
            <div class='col col-sm-4'>
              <input type='text' class='form-control' id='sensorid' name='sensorid' value='<?=$row['sensorAddress'];?>'>
            </div>
          </div>

          <div class="row mb-2">
            <label for='name' class='col col-sm-4 col-form-label'>Name</label>
            <div class='col col-sm-4'>
              <input type='text' class='form-control' id='name' name='name' value='<?=$row['name'];?>'>
            </div>
          </div>

          <div class="row mb-2">
            <label for='description' class='col col-sm-4 col-form-label'>Description</label>
            <div class='col col-sm-4'>
              <input type='text' class='form-control' id='description' name='description' value='<?=$row['description'];?>'>
            </div>
          </div>

          <div class="row mb-2">
            <label for='typ' class='col col-sm-4 col-form-label'>Typ id</label>
            <div class='col col-sm-4'>
              <select class='form-select' aria-label='Default select example' name='typid'>
          <!--/div-->

          <!--div class="row"-->
              <?php
              while($singleRowsensortyps = $sensortyps->fetch()) {
                if ($row['typid'] == $singleRowsensortyps['id']) {
                  echo "<option selected value='" . $singleRowsensortyps['id'] . "'>" . $singleRowsensortyps['name'] . "</option>";
                } else {
                  echo "<option value='" . $singleRowsensortyps['id'] . "'>" . $singleRowsensortyps['name'] . "</option>";
                }
              }
              ?>
              </select>
            </div>
          </div>

          <div class="row mb-2">
            <label for='messOrt' class='col col-sm-4 col-form-label'>Location</label>
            <div class='col col-sm-4'>
              <input type='text' class='form-control' id='messOrt' name='messOrt' value='<?=$row['messOrt'];?>'>
            </div>
          </div>

          <div class="row mb-2">
            <label for='nameValue1' class='col col-sm-4 col-form-label'>Name value 1</label>
            <div class='col col-sm-4'>
              <input type='text' class='form-control' id='nameValue1' name='nameValue1' value='<?=$row['nameValue1'];?>'>
            </div>
          </div>

          <div class="row mb-2">
            <label for='nameValue2' class='col col-sm-4 col-form-label'>Name value 2</label>
            <div class='col col-sm-4'>
              <input type='text' class='form-control' id='nameValue2' name='nameValue2' value='<?=$row['nameValue2'];?>'>
            </div>
          </div>

          <div class="row mb-2">
            <label for='nameValue3' class='col-sm-4 col-form-label'>Name value 3</label>
            <div class='col col-sm-4'>
              <input type='text' class='form-control' id='nameValue3' name='nameValue3' value='<?=$row['nameValue3'];?>'>
            </div>
          </div>

          <div class="row mb-2">
            <label for='nameValue4' class='col-sm-4 col-form-label'>Name value 4</label>
            <div class='col col-sm-4'>
              <input type='text' class='form-control' id='nameValue4' name='nameValue4' value='<?=$row['nameValue4'];?>'>
            </div>
          </div>

          <div class="row mb-2">
            <label for='onDashboard' class='col-sm-4 form-check-label'>on Dashboard </label>
            <div class='col col-sm-4'>
            <?php
            if(isset($row['onDashboard']) && $row['onDashboard'] == '1')
            {
              echo "<input type='checkbox' id='onDashboard' name='onDashboard' value=" . $row['onDashboard'] . " checked=" . $row['onDashboard'] . ">";
            }
            else
            {
              echo "<input type='checkbox' id='onDashboard' name='onDashboard' value='1'>";
            }
            ?>
            </div>
          </div>

          <div class="row mb-2">
            <div class='col col-sm-4'>
            <a class='form-control btn btn-primary' href='eingabemaske_boards.php?id=<?php echo "$_GET[boardid]" ?>' role='button'>Back</a>
            <!--input type='submit' class='col col-sm-2 m-1 form-control btn-primary' id='submit_eingabemaske_sensors' name='submit_eingabemaske_sensors' value='Save'-->
            </div>
            
            <div class='col col-sm-4'>
              <input type='submit' class='form-control btn-primary' id='submit_eingabemaske_sensors' name='submit_eingabemaske_sensors' value='Save'>
            </div>
          </div>
  </form>
    <?php
      }
    ?>
</div>
<?php
  include("common/footer.inc.php");
?>
