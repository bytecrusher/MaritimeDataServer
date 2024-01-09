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
require_once("func/dbUpdateData.php");
require_once("func/writeToLogFunction.func.php");

$config  = new configuration();
$varDemoMode = $config::$demoMode;
$varShowQrCode = $config::$ShowQrCode;
$varSend_emails = $config::$send_emails;


//Check that the user is logged in
if (isset($_SESSION['userobj'])) {
	$userobj = unserialize($_SESSION['userobj']);
} else {
	$userobj = false;
	header("Location: ./index.php");
}
include("common/header.inc.php");

function fixObject (&$object)
{
  if (!is_object ($object) && gettype ($object) == 'object')
    return ($object = unserialize (serialize ($object)));
  return $object;
}

if(isset($_GET['save'])) {
	$save = $_GET['save'];
	if($save == 'personal_data') {
		try {
			$userobj->setName($_POST);
		} catch (Exception $e) {
			$error_msg = $e->getMessage();
		}
		try {
			$userobj->setUserTimeZone($_POST);
		} catch (Exception $e) {
			$error_msg = $e->getMessage();
		}
		try {
			$userobj->setReceiveNotifications($_POST);
		} catch (Exception $e) {
			$error_msg = $e->getMessage();
		}
		$_SESSION['userobj'] = serialize($userobj);
		if (!isset($error_msg)) {
			$success_msg = "User Data sucessfully saved.";
		}
	} else if($save == 'email') {
		$password = $_POST['password'];
		$email = trim($_POST['email']);
		$email2 = trim($_POST['email2']);

		if($email != $email2) {
			$error_msg = "The entered email adresses are not the same.";
		} else if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$error_msg = "The entered email adress are not valid.";
		} else if(!password_verify($password, $userobj->getPassword())) {
			$error_msg = "Wrong password.";
		} else {
			try {
				$userobj->setEmail($_POST);
			} catch (Exception $e) {
				//$error_msg = $e->getMessage();
				$error_msg = "Email address not successully saved.";
			}
			$_SESSION['userobj'] = serialize($userobj);
			if (!isset($error_msg)) {
				$success_msg = "E-Mail address successfully saved.";
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
			try {
				$userobj->setUserPassword($password_hash);
			} catch (Exception $e) {
				$error_msg = "Password reset not successfully.";
			}
			$_SESSION['userobj'] = serialize($userobj);
			if (!isset($error_msg)) {
				$success_msg = "Password successfully saved.";
			}
		}
	} else if($save == 'dashboard_data') {
		try {
			$updateUserReturn = $userobj->setDashboardUpdateInterval($_POST);
		} catch (Exception $e) {
			$error_msg = "Dashboard Update Interval not saved.";
		}
		$_SESSION['userobj'] = serialize($userobj);
		if (!isset($error_msg)) {
			$success_msg = "Dashboard Update Interval successfully saved.";
		}
	} else if ($save == 'allBoards') {

	} else if($save == 'users') {
		try {
			dbUpdateData::updateUserStatus($_POST);
			$success_msg = "User Status updated.";
		} catch (Exception $e) {
			$error_msg = "Error on update User Status.";
		}
	} else if($save == 'addNewUserToBorad') {
		try {
			$addNewBoardToUserReturn = dbUpdateData::addNewBoardToUser($_POST, $userobj->getId());
			if ($addNewBoardToUserReturn) {
				$success_msg = "Board added successfully.<br>Wait for the next Lora update.";
			} else {
				$error_msg = "Board not added.";
			}
		} catch (Exception $e) {
			//$error_msg = "Error on add new board.";
			$error_msg = $e->getMessage();
		}
	}
	else if($save == 'serverSetting') {
		try {
			$config->saveServerSettings($_POST);
			$varDemoMode = $config::$demoMode;
			//$apikey
			$success_msg = "DemoMode saved.";
		} catch (Exception $e) {
			//$error_msg = "Error on add new board.";
			$error_msg = $e->getMessage();
		}
	}
}

// write passed data back to the database
 if (isset($_POST['submit_inputmask_boards']))	// Submit-Button of the input mask was pressed
 {
	try {
		$updateBoardReturn = dbUpdateData::updateBoard($_POST);
		$success_msg = "Board successfully updated.";
	} catch (Exception $e) {
		$error_msg = "Error while saving board changes.";
	}
 } elseif (isset($_POST['submit_inputmask_boards_remove']))	// remove-Button of the input mask was pressed
 {
	try {
		$updateBoardReturn = dbUpdateData::removeBoardOwner($_POST);
		$success_msg = "Board successfully removed.";
	} catch (Exception $e) {
		$error_msg = "Error while removing board.";
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

<!--link href="../node_modules/bootstrap5-toggle/css/bootstrap5-toggle.min.css" rel="stylesheet"-->
<link href="https://cdn.jsdelivr.net/npm/bootstrap5-toggle@5.0.4/css/bootstrap5-toggle.min.css" rel="stylesheet">

<!--script src="../node_modules/bootstrap5-toggle/js/bootstrap5-toggle.min.js"></script-->
<script src="https://cdn.jsdelivr.net/npm/bootstrap5-toggle@5.0.4/js/bootstrap5-toggle.jquery.min.js"></script>

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
			<li class="nav-item" role="presentation"><a class="nav-link" href="#confBoards" role="tab" data-bs-toggle="tab">My Boards</a></li>
			<li class="nav-item" role="presentation"><a class="nav-link" href="#confDashboard" role="tab" data-bs-toggle="tab">Dashboard</a></li>
			<?php
				if(($userobj->getUserGroupAdmin() == 1) ) {
				?>
					<li class='nav-item' role='presentation'><a class='nav-link' href='#allBoards' role='tab' data-bs-toggle='tab'>All Boards</a></li>
					<li class='nav-item' role='presentation'><a class='nav-link' href='#users' role='tab' data-bs-toggle='tab'>Users</a></li>
					<li class='nav-item' role='presentation'><a class='nav-link' href='#serverSetting' role='tab' data-bs-toggle='tab'>Server Setting</a></li>
					<li class='nav-item' role='presentation'><a class='nav-link' href='#log' role='tab' data-bs-toggle='tab'>Log</a></li>
				<?php
				}
			?>
		</ul>

		<!-- Personal data -->
		<div class="tab-content" style="background: white">
			<div role="tabpanel" class="tab-pane active" id="data">
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

					<?php
						function time_zonelist() {
						$allzones = array();
						$timestamp = time();
						foreach(timezone_identifiers_list() as $key => $live_zone) {
							date_default_timezone_set($live_zone);
							$allzones[$key]['zone'] = $live_zone;
							$allzones[$key]['diff_from_GMT'] = 'UTC/GMT ' . date('P', $timestamp);
						}
							return $allzones;
						}
					?>
					<div class="form-group">
						<div class="row">
							<label for="inputTimezone" class="col-sm-2 control-label">Timezone</label>
							<?php $userTimezone = htmlentities($userobj->getTimezone() ?? ""); ?>
							<div class="col-sm-4">
							<select class="form-select" aria-label="Default select example" id="inputTimezone" name="Timezone">
								<option value="0">Please, select your timezone</option>
								<?php foreach(time_zonelist() as $t) { ?>
									<?php if($t['zone'] == $userTimezone ) { ?>
										<option value="<?php print $t['zone'] ?>" selected>
											<?php print $t['zone'] . ' - ' . $t['diff_from_GMT'] ?>
										</option>
									<?php } else { ?>
										<option value="<?php print $t['zone'] ?>">
											<?php print $t['zone'] . ' - ' . $t['diff_from_GMT'] ?>
										</option>
									<?php } ?>
								<?php } ?>
							</select>
							</div>
						</div>
					</div>

					<div class="form-group">
						<div class="row">
							<?php
								if ($userobj->getReceiveNotifications()) {
								?>
									<div class="col col-sm-2">Receive notifications?</div>
									<label class="col col-sm-4">
											<input type='hidden' class='form-check-input' id='receiveNotifications' name='receiveNotifications' value='0'>
											<input type='checkbox' class='form-check-input' id='receiveNotifications' name='receiveNotifications' checked=true value='1'>
									</label>
								<?php
								} else {
								?>
									<div class="col col-sm-2">Receive notifications?</div>
									<label class="col col-sm-4">
											<input type='hidden' class='form-check-input' id='receiveNotifications' name='receiveNotifications' checked=true value='0'>
											<input type='checkbox' class='form-check-input' id='receiveNotifications' name='receiveNotifications' value='1'>
									</label>
								<?php
								}
							?>
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
				<p style="margin-bottom: 0px; margin-top: 1rem;">To change your email adress, please enter your current password and the new email adress.</p>
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
				<p style="margin-bottom: 0px; margin-top: 1rem;">To change your password, please enter your current password and the new password.</p>
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
					<div class="form-check form-switch d-inline-block pe-4">
						<label for="inlineCheckbox1" class="form-check-label">id</label>
						<input id="inlineCheckbox1" value="toggleDisplayid" class="form-check-input mytogglebutton" type="checkbox" checked data-size="small">
					</div>
					<div class="form-check form-switch d-inline-block pe-4">
						<label for="inlineCheckbox2" class="form-check-label">Mac address</label>
						<input id="inlineCheckbox2" value="toggleDisplayMacaddress" class="form-check-input mytogglebutton" type="checkbox" checked data-size="small">
					</div>

					<div class="form-check form-switch d-inline-block pe-4">
						<label for="inlineCheckbox3" class="form-check-label">Location</label>
						<input id="inlineCheckbox3" value="toggleDisplayLocation" class="form-check-input mytogglebutton" type="checkbox" checked data-size="small">
					</div>

					<div class="form-check form-switch d-inline-block pe-4">
						<label for="inlineCheckbox4" class="form-check-label">TTN id</label>
						<input id="inlineCheckbox4" value="toggleDisplayTtnDevId" class="form-check-input mytogglebutton" type="checkbox" checked data-size="small">
					</div>

					<div class="form-check form-check-inline">
						<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">Add new Board</button>
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
				<!-- Modal -->
				<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<form class="row g-3" action="?save=addNewUserToBorad" method="post" class="form-horizontal">
							<div class="modal-header">
								<h5 class="modal-title" id="exampleModalLabel">Add new Board</h5>
								<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
							</div>
							<div class="modal-body">
								<div class="input-group mb-3">
									<select class="form-select input-group-text" aria-label="Default select example" id="valueType" name="valueType">
										<option value="ttn" selected>TTN dev id</option>
										<option value="mac">Mac Adress</option>
									</select>
									<input type="text" class="form-control" placeholder="Enter Value" aria-label="Value" aria-describedby="macAdress" name="inputValue" id="inputValue" required>
								</div>
							</div>
							<div class="modal-footer">
								<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
								<button type="submit" class="btn btn-primary">Save changes</button>
							</div>
						</form>
					</div>
				</div>
				</div>
			</div>

			<!-- Configure the user's dashboard -->
			<div role="tabpanel" class="tab-pane" id="confDashboard">
				<div class="panel panel-default">
					<form action="?save=dashboard_data" method="post" class="form-horizontal">
						<div class="form-group">
							<div class="row">
								<label for="inputUpdateInterval" class="col-sm-2 control-label">Update interval (in Minutes) (tbd)</label>
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

			


			<div role="tabpanel" class="tab-pane" id="allBoards">
			<form action="?save=allBoards" method="post" class="form-horizontal">
				<div class="container-fluid border mb-2">
				<span>toggle collums:</span>
					<div class="form-check form-switch d-inline-block pe-4">
						<label for="inlineCheckbox1" class="form-check-label">id</label>
						<input id="inlineCheckbox1" value="toggleDisplayid" class="form-check-input mytogglebutton" type="checkbox" checked data-size="small">
					</div>
					<div class="form-check form-switch d-inline-block pe-4">
						<label for="inlineCheckbox2" class="form-check-label">Mac address</label>
						<input id="inlineCheckbox2" value="toggleDisplayMacaddress" class="form-check-input mytogglebutton" type="checkbox" checked data-size="small">
					</div>

					<div class="form-check form-switch d-inline-block pe-4">
						<label for="inlineCheckbox3" class="form-check-label">Location</label>
						<input id="inlineCheckbox3" value="toggleDisplayLocation" class="form-check-input mytogglebutton" type="checkbox" checked data-size="small">
					</div>

					<div class="form-check form-switch d-inline-block pe-4">
						<label for="inlineCheckbox4" class="form-check-label">TTN App id</label>
						<input id="inlineCheckbox4" value="toggleDisplayTtnAppId" class="form-check-input mytogglebutton" type="checkbox" checked data-size="small">
					</div>
					<div class="form-check form-switch d-inline-block pe-4">
						<label for="inlineCheckbox5" class="form-check-label">TTN Dev id</label>
						<input id="inlineCheckbox5" value="toggleDisplayTtnDevId" class="form-check-input mytogglebutton" type="checkbox" checked data-size="small">
					</div>

				</div>
				<div class="panel panel-default table-responsive">
					<table class="table table-bordered">
					<thead>
					<tr>
						<th class='toggleDisplayid'>id</th>
						<th class='toggleDisplayMacaddress '><div><span>Mac address</span></div></th>
						<th><div><span>owner User</span></div></th>
						<th>Name</th>
						<th class="toggleDisplayLocation">Location</th>
						<th>Description</th>
						<th class="toggleDisplayTtnAppId">TTN App id</th>
						<th class="toggleDisplayTtnDevId">TTN dev id</th>
						<th class='rotated-text'><div><span>Sensors</span></div></th>
						<th class='rotated-text'><div><span>Alarm</span></div></th>
						<th class='rotated-text'><div><span>Details</span></div></th>
					</tr>
					</thead>
					<tbody>
					<?php
					$myboards = $userobj->getAllBoardsAdmin();
					$countBoardRow = 1;
					foreach($myboards as $singleRowMyboard) {
					?>
						<tr>
						<td class='toggleDisplayid'> <?php echo $singleRowMyboard['id'] ?></td>
						<td class='toggleDisplayMacaddress' style='word-wrap: break-word;min-width: 160px;max-width: 160px;'><?php echo $singleRowMyboard['macaddress'] ?></td>
						<td><?php echo $singleRowMyboard['owner_userid'] ?></td>

						<td><?php echo $singleRowMyboard['name'] ?></td>
						<td class='toggleDisplayLocation'><?php echo $singleRowMyboard['location'] ?></td>
						<td><?php echo $singleRowMyboard['description'] ?></td>
						<td class='toggleDisplayTtnAppId' style='word-wrap: break-word;min-width: 160px;max-width: 160px;'><?php echo $singleRowMyboard['ttn_app_id'] ?></td>
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
				<div class="form-group">
						<div class="col-sm-offset-2 col-sm-10">
						<button type="submit" class="btn btn-primary">Save</button>
						</div>
					</div>
				</form>
			</div>


			<!-- Modification of other users -->
			<div role="tabpanel" class="tab-pane" id="users">
				<p style="margin-bottom: 0px; margin-top: 1rem;">To change and activate Users.</p>
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
					<div class="panel panel-default">
						<div class="form-group">
							<div class="row">
						<?php
							if ($varDemoMode) {
							?>
								<div class="col col-sm-2">
									Demo mode</div>
								<label class="col col-sm-4">
										<input type='hidden' class='form-check-input' id='demoMode' name='demoMode' value='0'>
										<input type='checkbox' class='form-check-input' id='demoMode' name='demoMode' checked=true value='1'>
								</label>
							<?php
							} else {
							?>
								<div class="col col-sm-2">
									Demo mode</div>
								<label class="col col-sm-4">
										<input type='hidden' class='form-check-input' id='demoMode' name='demoMode' checked=true value='0'>
										<input type='checkbox' class='form-check-input' id='demoMode' name='demoMode' value='1'>
								</label>
							<?php
							}
						?>
						</div>
						</div>
					</div>
					<div class="panel panel-default">
						<div class="form-group">
							<div class="row">
						<?php
							if ($varShowQrCode) {
							?>
								<div class="col col-sm-2">
									Show QR Code
								</div>
								<label class="col col-sm-4">
										<input type='hidden' class='form-check-input' id='ShowQrCode' name='ShowQrCode' value='0'>
										<input type='checkbox' class='form-check-input' id='ShowQrCode' name='ShowQrCode' checked=true value='1'>
								</label>
							<?php
							} else {
							?>
								<div class="col col-sm-2">
									Show QR Code
								</div>
								<label class="col col-sm-4">
										<input type='hidden' class='form-check-input' id='ShowQrCode' name='ShowQrCode' checked=true value='0'>
										<input type='checkbox' class='form-check-input' id='ShowQrCode' name='ShowQrCode' value='1'>
								</label>
							<?php
							}
						?>
						</div>
						</div>
					</div>

					<div class="panel panel-default">
						<div class="form-group">
							<div class="row">
								<label for="apiKey" class="col col-sm-2 control-label">API Key:</label>
								<div class="col col-sm-4">
									<input class="form-control" id="apiKey" name="apikey" type="text" value="<?php echo $config::$api_key; ?>" required>
								</div>
							</div>
						</div>
					</div>

					<div class="panel panel-default">
						<div class="form-group">
							<div class="row">

							<?php
							if ($varSend_emails) {
							?>
								<div class="col col-sm-2">
								Send emails:
								</div>
								<label class="col col-sm-4">
										<input type='hidden' class='form-check-input' id='send_emails' name='send_emails' value='0'>
										<input type='checkbox' class='form-check-input' id='send_emails' name='send_emails' checked=true value='1'>
								</label>
							<?php
							} else {
							?>
								<div class="col col-sm-2">
								Send emails:
								</div>
								<label class="col col-sm-4">
										<input type='hidden' class='form-check-input' id='send_emails' name='send_emails' checked=true value='0'>
										<input type='checkbox' class='form-check-input' id='send_emails' name='send_emails' value='1'>
								</label>
							<?php
							}
						?>
						</div>
						</div>
					</div>

					<div class="form-group">
						<div class="col col-sm-offset-2 col-sm-10">
							<button type="submit" class="btn btn-primary">Save</button>
						</div>
					</div>
				</form>
			</div>

			<!-- Modification of Log -->
			<div role="tabpanel" class="tab-pane" id="log">
				<div class="panel panel-default p-2">The log file of the current month will be displayed.</div>
				<div class="panel panel-default p-2">
					<?php
						$format = "log"; // Possibilities: csv and txt
						date_default_timezone_set('Europe/Berlin');
						$datum_zeit = date("d.m.Y H:i:s");
						$monate = array(1 => "Januar", 2 => "Februar", 3 => "Maerz", 4 => "April", 5 => "Mai", 6 => "Juni", 7 => "Juli", 8 => "August", 9 => "September", 10 => "Oktober", 11 => "November", 12 => "Dezember");
						$monat = date("n");
						$jahr = date("Y");
						$dateiname = dirname(__FILE__) . "/../logs/log_" . $monate[$monat] . "_$jahr.$format";
						$data = false;

						if (file_exists($dateiname)) {
							$data = file_get_contents($dateiname);
							if ($data != false) {
								echo '<textarea style="height: 400px; width: 100%;" readonly>' . htmlspecialchars($data). '</textarea>';
							} else {
								echo '<textarea style="height: 400px; width: 100%;" readonly>Error while opening Log file.</textarea>';
							}
						} else {
							echo '<textarea style="height: 400px; width: 100%;" readonly>Log file not found.</textarea>';
						}
					?>
				</div>
			</div>
		</div>
	</div>
</div>
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

<?php
include("common/footer.inc.php");
?>