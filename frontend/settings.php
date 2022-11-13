<?php
/**
 *
 * @author: Guntmar Höche
 * @license: TBD
 */

session_start();
require_once("func/dbConfig.func.php");
require_once("func/myFunctions.func.php");
require_once("func/user.class.php");
include("common/header.inc.php");
require_once("func/dbUpdateData.php");

$config  = new configuration();
$varDemoMode = $config::$demoMode;

//Check that the user is logged in
if (isset($_SESSION['userobj'])) {
	$userobj = unserialize($_SESSION['userobj']);
} else {
	$userobj = false;
	header("Location: ./index.php");
}

function fixObject (&$object)
{
  if (!is_object ($object) && gettype ($object) == 'object')
    return ($object = unserialize (serialize ($object)));
  return $object;
}

if(isset($_GET['save'])) {
	$save = $_GET['save'];
	if($save == 'personal_data') {
		$updateUserReturn = $userobj->setName($_POST);
		if (!$updateUserReturn) {
			$error_msg = "Please enter first and last name.";
		 } else {
			 $success_msg = $updateUserReturn;
		 }
	} else if($save == 'email') {
		$password = $_POST['password'];
		$email = trim($_POST['email']);
		$email2 = trim($_POST['email2']);

		if($email != $email2) {
			$error_msg = "The entered email adresses are not the same.";
		} else if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$error_msg = "The entered email adress are not valid.";
		} else if(!password_verify($password, $userobj['password'])) {
			$error_msg = "Wrong password.";
		} else {
			$updateUserPasswordReturn = dbUpdateData::updateUserMail($_POST, $userobj->getId);
	 	 	if (!$updateUserPasswordReturn) {
	 		 	$error_msg = "Error on update User Mail.";
	 	 	} else {
	 		 	$success_msg = $updateUserPasswordReturn;
	 	 	}
		}
	} else if($save == 'password') {
		$passwordAlt = $_POST['passwordOld'];
		$passwordNew = trim($_POST['passwordNew']);
		$passwordNew2 = trim($_POST['passwordNew2']);

		if($passwordNew != $passwordNew2) {
			$error_msg = "The entered passwords are not the same.";
		} else if($passwordNew == "") {
			$error_msg = "Empty password is not allowed.";
		} else if(!password_verify($passwordAlt, $userobj->getPassword())) {
			$error_msg = "Please enter correct password.";
		} else {
			$password_hash = password_hash($passwordNew, PASSWORD_DEFAULT);

			$updateUserPasswordReturn = dbUpdateData::updateUserPassword($password_hash, $userobj->getId());
	 	 	if (!$updateUserPasswordReturn) {
	 		 	$error_msg = "Error on update User Password.";
	 	 	} else {
	 		 	$success_msg = $updateUserPasswordReturn;
	 	 	}

		}
	} else if($save == 'dashboard_data') {
		$updateUserReturn = $userobj->setDashboardUpdateInterval($_POST);
 	 	if (!$updateUserReturn) {
 			$error_msg = "Please enter first and last name.";
 	 	} else {
 		 	$success_msg = $updateUserReturn;
 	 	}
	}
	else if($save == 'users') {
		$updateUserReturn = dbUpdateData::updateUserStatus($_POST);
 	 	if (!$updateUserReturn) {
 		 	$error_msg = "Error on update User Status.";
 	 	} else {
 		 	$success_msg = $updateUserReturn;
 	 	}
	}
	//else if($save == 'serverSetting') {
		// save config file.
		//var_dump($_POST);
		//echo("save config");
	//}
}

// write passed data back to the database
 if (isset($_POST['submit_inputmask_boards']))	// Submit-Button of the input mask was pressed
 {
	 $updateBoardReturn = dbUpdateData::updateBoard($_POST);
	 if (!$updateBoardReturn) {
		 $error_msg = "Error while saving board changes.";
	 } else {
		 $success_msg = $updateBoardReturn;
	 }

 }
?>

<style>
th.rotated-text {
    height: 140px;
    white-space: nowrap;
    padding: 0 !important;
}

th.rotated-text > div {
    transform:
        translate(0px, 0px)
        rotate(270deg);
    width: 30px;
}

th.rotated-text > div > span {
    padding: 5px 10px;
}
</style>

<!--link href="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/css/bootstrap4-toggle.min.css" rel="stylesheet"-->
<link href="../node_modules/bootstrap5-toggle/css/bootstrap5-toggle.min.css" rel="stylesheet">
<!--script src="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/js/bootstrap4-toggle.min.js"></script-->
<script src="../node_modules/bootstrap5-toggle/js/bootstrap5-toggle.min.js"></script>

<!--div class="jumbotron" style="padding: 1rem 1rem; margin-bottom: 1rem;"-->
<div class="jumbotron" style="padding: 1rem 1rem;">
	<div class="container">
		<h1>Settings</h1>

	</div>
</div>
<div class="container-xl main-container">
	<?php
	if(isset($success_msg) && !empty($success_msg)):
	?>
		<div class="alert alert-success">
			<a href="#" class="close" data-bs-dismiss="alert" aria-label="close">&times;</a>
			<?php echo $success_msg; ?>
		</div>
	<?php endif; ?>

	<?php
	if(isset($error_msg) && !empty($error_msg)):
	?>
		<div class="alert alert-danger">
			<a href="#" class="close" data-bs-dismiss="alert" aria-label="close">&times;</a>
			<?php echo $error_msg; ?>
		</div>
	<?php endif; ?>
	<div>
		<!-- Nav tabs -->
		<ul class="nav nav-tabs" role="tablist">
			<li class="nav-item" role="presentation"><a class="nav-link active" href="#data" role="tab" data-bs-toggle="tab">Personal data</a></li>
			<li class="nav-item" role="presentation"><a class="nav-link" href="#email" role="tab" data-bs-toggle="tab">E-Mail</a></li>
			<li class="nav-item" role="presentation"><a class="nav-link" href="#password" role="tab" data-bs-toggle="tab">Password</a></li>
			<li class="nav-item" role="presentation"><a class="nav-link" href="#confBoards" role="tab" data-bs-toggle="tab">Boards</a></li>
			<li class="nav-item" role="presentation"><a class="nav-link" href="#confDashboard" role="tab" data-bs-toggle="tab">Dashboard</a></li>
			<?php
				if(($userobj->getUserGroupAdmin() == 1) ) {
				?>
					<li class='nav-item' role='presentation'><a class='nav-link' href='#users' role='tab' data-bs-toggle='tab'>Users</a></li>
					<li class='nav-item' role='presentation'><a class='nav-link' href='#serverSetting' role='tab' data-bs-toggle='tab'>Server Setting</a></li>
				<?php
				}
			?>
		</ul>

		<!-- Personal data -->
		<div class="tab-content">
			<div role="tabpanel" class="tab-pane active" id="data">
				<br>
				<form action="?save=personal_data" method="post" class="form-horizontal">
					<div class="form-group">
						<div class="row">
							<label for="inputFirstname" class="col-sm-2 control-label">First name</label>
							<div class="col-sm-4">
								<input class="form-control" id="inputFirstname" name="firstname" type="text" value="<?php echo htmlentities($userobj->getFirstname()); ?>" required>
							</div>
						</div>
					</div>

					<div class="form-group">
						<div class="row">
							<label for="inputLastname" class="col-sm-2 control-label">Last name</label>
							<div class="col-sm-4">
								<input class="form-control" id="inputLastname" name="lastname" type="text" value="<?php echo htmlentities($userobj->getLastname()); ?>" required>
							</div>
						</div>
					</div>

					<div class="form-group">
						<div class="row">
							<div class="col-sm-offset-2 col-sm-10">
							<button type="submit" class="btn btn-primary">Save</button>
							</div>
						</div>
					</div>
				</form>
			</div>

			<!-- change of email address -->
			<div role="tabpanel" class="tab-pane" id="email">
				<br>
				<p>To change your email adress, please enter your current password and the new email adress.</p>
				<form action="?save=email" method="post" class="form-horizontal">
					<div class="form-group">
						<div class="row">
							<label for="inputPasswordForValidation" class="col-sm-2 control-label">Password</label>
							<div class="col-sm-4">
								<input class="form-control" id="inputPasswordForValidation" name="password" type="password" required>
							</div>
						</div>
					</div>

					<div class="form-group">
						<div class="row">
							<label for="inputEmail" class="col-sm-2 control-label">E-Mail</label>
							<div class="col-sm-4">
								<input class="form-control" id="inputEmail" name="email" type="email" value="<?php echo htmlentities($userobj->getEmail()); ?>" required>
							</div>
						</div>
					</div>

					<div class="form-group">
						<div class="row">
							<label for="inputEmail2" class="col-sm-2 control-label">E-Mail (repeat)</label>
							<div class="col-sm-4">
								<input class="form-control" id="inputEmail2" name="email2" type="email"  required>
							</div>
						</div>
					</div>

					<div class="form-group">
						<div class="row">
							<div class="col-sm-offset-2 col-sm-10">
							<button type="submit" class="btn btn-primary">Save</button>
							</div>
						</div>
					</div>
				</form>
			</div>

			<!-- change password -->
			<div role="tabpanel" class="tab-pane" id="password">
				<br>
				<p>To change your password, please enter your current password and the new password.</p>
				<form action="?save=password" method="post" class="form-horizontal">
					<div class="form-group">
						<div class="row">
							<label for="inputPassword" class="col-sm-2 control-label">Old Password</label>
							<div class="col-sm-4">
								<input class="form-control" id="inputPasswordOld" name="passwordOld" type="password" required>
							</div>
						</div>
					</div>

					<div class="form-group">
						<div class="row">
							<label for="inputPasswordNew" class="col-sm-2 control-label">New password</label>
							<div class="col-sm-4">
								<input class="form-control" id="inputPasswordNew" name="passwordNew" type="password" required>
							</div>
						</div>
					</div>

					<div class="form-group">
						<div class="row">
							<label for="inputPasswordNew2" class="col-sm-2 control-label">New password (repeat)</label>
							<div class="col-sm-4">
								<input class="form-control" id="inputPasswordNew" name="passwordNew2" type="password"  required>
							</div>
						</div>
					</div>

					<div class="form-group">
						<div class="row">
							<div class="col-sm-offset-2 col-sm-10">
							<button type="submit" class="btn btn-primary">Save</button>
							</div>
						</div>
					</div>
				</form>
			</div>

			<!-- Configure the user's boards -->
			<div role="tabpanel" class="tab-pane" id="confBoards">
				<div class="container-fluid border mb-2">
				<span>toggle collums:</span>
					<div class="form-check form-check-inline mt-1">
						<label for="inlineCheckbox1" class="form-check-label">id</label>
						<input id="inlineCheckbox1" value="toggleDisplayid" class="form-check-input mytogglebutton" type="checkbox" data-toggle="toggle" checked data-size="small">
					</div>
					<div class="form-check form-check-inline">
						<label for="inlineCheckbox2" class="form-check-label">Mac address</label>
						<input id="inlineCheckbox2" value="toggleDisplayMacaddress" class="form-check-input mytogglebutton" type="checkbox" data-toggle="toggle" checked data-size="small">
					</div>

					<div class="form-check form-check-inline">
						<label for="inlineCheckbox3" class="form-check-label">Location</label>
						<input id="inlineCheckbox3" value="toggleDisplayLocation" class="form-check-input mytogglebutton" type="checkbox" data-toggle="toggle" checked data-size="small">
					</div>

					<div class="form-check form-check-inline">
						<label for="inlineCheckbox4" class="form-check-label">TTN id</label>
						<input id="inlineCheckbox4" value="toggleDisplayTtnDevId" class="form-check-input mytogglebutton" type="checkbox" data-toggle="toggle" checked data-size="small">
					</div>

				</div>
				<div class="panel panel-default table-responsive">
					<table class="table table-bordered">
					<thead>
					<tr>
						<th class='toggleDisplayid'>id</th>
						<th class='toggleDisplayMacaddress '><div><span>Mac address</span></div></th>
						<th>Name</th>
						<th class="toggleDisplayLocation">Location</th>
						<th>Description</th>
						<th class="toggleDisplayTtnDevId">TTN dev id</th>
						<th class=' rotated-text'><div><span>Sensors</span></div></th>
						<th class=' rotated-text'><div><span>Alarm</span></div></th>
						<th class=' rotated-text'><div><span>Details</span></div></th>
					</tr>
					</thead>
					<tbody>
					<?php
					$myboards = $userobj->getMyBoardsAll();
					$countBoardRow = 1;
					foreach($myboards as $singleRowMyboard) {
					?>
						<tr>
						<td class='toggleDisplayid'> <?php echo $singleRowMyboard['id'] ?></td>
						<td class='toggleDisplayMacaddress' style='word-wrap: break-word;min-width: 160px;max-width: 160px;'><?php echo $singleRowMyboard['macaddress'] ?></td>
						<td><?php echo $singleRowMyboard['name'] ?></td>
						<td class='toggleDisplayLocation'><?php echo $singleRowMyboard['location'] ?></td>
						<td><?php echo $singleRowMyboard['description'] ?></td>
						<td class='toggleDisplayTtnDevId' style='word-wrap: break-word;min-width: 160px;max-width: 160px;'><?php echo $singleRowMyboard['ttn_dev_id'] ?></td>
					<?php
						$sensorsOfBoard = myFunctions::getAllSensorsOfBoardold($singleRowMyboard['id']);
						echo "<td>".count($sensorsOfBoard)."</td>";

						if(isset($singleRowMyboard['alarmOnUnavailable']) && $singleRowMyboard['alarmOnUnavailable'] == '1') {
						?>
							<td><input type='checkbox' class='form-check-input' id='alarmOnUnavailable<?php echo$singleRowMyboard['id']?> "' disabled name='alarmOnUnavailable' value=" <?php echo $singleRowMyboard['alarmOnUnavailable'] ?> " checked=" <?php echo $singleRowMyboard['alarmOnUnavailable'] ?> "></td>
						<?php
						} else {
						?>
							<td><input type='checkbox' class='form-check-input' id='alarmOnUnavailable" <?php echo$singleRowMyboard['id'] ?> "' disabled name='alarmOnUnavailable' value='1'></td>
						<?php
						}
						?>
							<td><a href="inputmask_boards.php?id=<?php echo $singleRowMyboard['id'] ?>"><i class='bi bi-pencil-fill'> </i></td>
						</tr>
						<?php
					}
					?>
					</tbody></table>
				</div>
			</div>

			<!-- Configure the user's dashboard -->
			<!-- TODO add function, for adding a board (with MAC or TTN ID to the user) -->
			<div role="tabpanel" class="tab-pane" id="confDashboard">
				<div class="panel panel-default">
					<br>
					<form action="?save=dashboard_data" method="post" class="form-horizontal">
						<div class="form-group">
							<div class="row">
								<label for="inputUpdateInterval" class="col-sm-2 control-label">Update interval (in Minutes)</label>
								<div class="col-sm-4">
									<input class="form-control" id="inputUpdateInterval" name="updateInterval" type="number" value="<?php echo htmlentities($userobj->getDashboardUpdateInterval()); ?>" required>
								</div>
								</div>
						</div>

						<div class="form-group">
							<div class="row">
								<div class="col-sm-offset-2 col-sm-10">
								<button type="submit" class="btn btn-primary">Save</button>
								</div>
							</div>
						</div>
					</form>	
				</div>
			</div>

			<!-- Modification of other users -->
			<div role="tabpanel" class="tab-pane" id="users">
				<br>
				<p>To change and activate Users.</p>
				<form action="?save=users" method="post" class="form-horizontal">
					<div class="panel panel-default">
					<table class="table">
					<tr>
						<th>#</th><th>Active</th><th>First name</th><th>Last name</th><th>E-Mail</th><th>Admin</th>
					</tr>
					<?php
					if(($userobj->getUserGroupAdmin() != false) ) {
						$count = 1;
						$statement = myFunctions::getAllUsers();

						foreach($statement as $singleRowUser) {
							?>
							<tr>
								<td><?php echo $count++ ?></td>
								<?php
								if(isset($singleRowUser['active']) && $singleRowUser['active'] == '1')
								{
								?>
									<td><input type='hidden' class='form-check-input' id='active<?php echo $singleRowUser['id'] ?>' name='active[<?php echo $singleRowUser['id'] ?>]' value='0' checked=<?php echo $singleRowUser['active'] ?>>
									<input type='checkbox' class='form-check-input' id='active<?php echo $singleRowUser['id'] ?>' name='active[<?php echo $singleRowUser['id'] ?>]' value='1' checked=<?php echo $singleRowUser['active'] ?>></td>
								<?php
								}
								else
								{
								?>
									<td><input type='hidden' class='form-check-input' id='active<?php echo $singleRowUser['id'] ?>' name='active[<?php echo $singleRowUser['id'] ?>]' value='0'>
									<input type='checkbox' class='form-check-input' id='active<?php echo $singleRowUser['id'] ?>' name='active[<?php echo $singleRowUser['id'] ?>]' value='1'></td>
								<?php
								}
								?>
								<td><?php echo $singleRowUser['firstname'] ?></td>
								<td><?php echo $singleRowUser['lastname'] ?></td>
								<td><a href="mailto:<?php echo $singleRowUser['email'] ?>"><?php echo $singleRowUser['email'] ?></a></td>
								<?php
								if(isset($singleRowUser['usergroup_admin']) && $singleRowUser['usergroup_admin'] == '1')
								{
								?>
									<td><input type='hidden' class='form-check-input' id='usergroup_admin<?php echo $singleRowUser['id'] ?>' name='usergroup_admin[<?php echo $singleRowUser['id'] ?>]' value='0' checked=<?php echo $singleRowUser['usergroup_admin'] ?>>
									<input type='checkbox' class='form-check-input' id='usergroup_admin<?php echo $singleRowUser['id'] ?>' name='usergroup_admin[<?php echo $singleRowUser['id'] ?>]' value='1' checked=<?php echo $singleRowUser['usergroup_admin'] ?>></td>
								<?php
								}
								else
								{
								?>
									<td><input type='hidden' class='form-check-input' id='usergroup_admin<?php echo $singleRowUser['id'] ?>' name='usergroup_admin[<?php echo $singleRowUser['id'] ?>]' value='0'>
									<input type='checkbox' class='form-check-input' id='usergroup_admin<?php echo $singleRowUser['id'] ?>' name='usergroup_admin[<?php echo $singleRowUser['id'] ?>]' value='1'></td>
								<?php
								}
								?>
							</tr>
							<?php
						}
					}
					?>
					</table>
					</div>
					<div class="form-group">
						<div class="col-sm-offset-2 col-sm-10">
						<button type="submit" class="btn btn-primary">Save</button>
						</div>
					</div>
				</form>
			</div>

			<!-- Modification of Server Setting -->
			<div role="tabpanel" class="tab-pane" id="serverSetting">
				<form action="?save=serverSetting" method="post" class="form-horizontal">
					<div class="panel panel-default p-2">
						<?php
							if ($varDemoMode == true) {
							?>
								<input type='hidden' class='form-check-input' id='demoMode' name='demoMode' checked=true value='0'>
								<input type='checkbox' class='form-check-input' id='demoMode' name='demoMode' checked=true value='1' disabled>   <label for="demoMode">Demo mode (tbd)</label>
							<?php
							} else {
							?>
								<input type='hidden' class='form-check-input' id='demoMode' name='demoMode' value='0'>
								<input type='checkbox' class='form-check-input' id='demoMode' name='demoMode' value='1' disabled>   <label for="demoMode">Demo mode (tbd)</label>
							<?php
							}
						?>
					</div>
					<div class="form-group">
						<div class="col-sm-offset-2 col-sm-10">
							<button type="submit" class="btn btn-primary" disabled>Save</button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
<?php
include("common/footer.inc.php");
?>
<script>
	$(function() {
		var hash = document.location.hash;
		if (hash.match('#confBoards')) {
			$('.nav-tabs a[href="#' + hash.split('#')[1] + '"]').tab('show');
		} else if (hash.match('#confSensors')) {
			$('.nav-tabs a[href="#' + hash.split('#')[1] + '"]').tab('show');
		}
	});

$(function() {
	$('.mytogglebutton').change(function() {	
      $('#console-event').html('Toggle: ' + $(this).prop('checked'))
	  if ($(this).prop('checked') == true) {
		$(".table ." + $(this).attr("value")).show();
	  } else {
		$(".table ." + $(this).attr("value")).hide();
	  }
    })
  })
</script>
