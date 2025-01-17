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
$var_apiKey = $config::$apiKey;
$varSend_emails = $config::$sendEmails;


//Check that the user is logged in
if (isset($_SESSION['userObj'])) {
	$userObj = unserialize($_SESSION['userObj']);
} else {
	$userObj = false;
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
		try {
			$userObj->setName($_POST);
		} catch (Exception $e) {
			$error_msg = $e->getMessage();
			writeToLogFunction::write_to_log("setName not saved.", $_SERVER["SCRIPT_FILENAME"]);
			writeToLogFunction::write_to_log($e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
		}
		try {
			$userObj->setUserTimeZone($_POST);
		} catch (Exception $e) {
			$error_msg = $e->getMessage();
			writeToLogFunction::write_to_log("setUserTimeZone not saved.", $_SERVER["SCRIPT_FILENAME"]);
			writeToLogFunction::write_to_log($e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
		}
		try {
			$userObj->setReceiveNotifications($_POST);
		} catch (Exception $e) {
			$error_msg = $e->getMessage();
			writeToLogFunction::write_to_log("setReceiveNotifications not saved.", $_SERVER["SCRIPT_FILENAME"]);
			writeToLogFunction::write_to_log($e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
		}
		$_SESSION['userObj'] = serialize($userObj);
		if (!isset($error_msg)) {
			$success_msg = "User Data successfully saved.";
		}
	} else if($save == 'email') {
		$password = $_POST['password'];
		$email = trim($_POST['email']);
		$email2 = trim($_POST['email2']);

		if($email != $email2) {
			$error_msg = "The entered email addresses are not the same.";
		} else if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$error_msg = "The entered email address are not valid.";
		} else if(!password_verify($password, $userObj->getPassword())) {
			$error_msg = "Wrong password.";
		} else {
			try {
				$userObj->setEmail($_POST);
			} catch (Exception $e) {
				//$error_msg = $e->getMessage();
				$error_msg = "Email address not successfully saved.";
				writeToLogFunction::write_to_log($error_msg, $_SERVER["SCRIPT_FILENAME"]);
				writeToLogFunction::write_to_log($e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
			}
			$_SESSION['userObj'] = serialize($userObj);
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
		} else if(!password_verify($passwordAlt, $userObj->getPassword())) {
			$error_msg = "Please enter correct password.";
		} else {
			$password_hash = password_hash($passwordNew, PASSWORD_DEFAULT);
			try {
				$userObj->setUserPassword($password_hash);
			} catch (Exception $e) {
				$error_msg = "Password reset not successfully.";
				writeToLogFunction::write_to_log($error_msg, $_SERVER["SCRIPT_FILENAME"]);
				writeToLogFunction::write_to_log($e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
			}
			$_SESSION['userObj'] = serialize($userObj);
			if (!isset($error_msg)) {
				$success_msg = "Password successfully saved.";
			}
		}
	} else if($save == 'dashboard_data') {
		try {
			$updateUserReturn = $userObj->setDashboardUpdateInterval($_POST);
		} catch (Exception $e) {
			$error_msg = "Dashboard Update Interval not saved.";
			writeToLogFunction::write_to_log($error_msg, $_SERVER["SCRIPT_FILENAME"]);
			writeToLogFunction::write_to_log($e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
		}
		$_SESSION['userObj'] = serialize($userObj);
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
			writeToLogFunction::write_to_log($error_msg, $_SERVER["SCRIPT_FILENAME"]);
			writeToLogFunction::write_to_log($e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
		}
	} else if($save == 'addNewUserToBoard') {
		try {
			$addNewBoardToUserReturn = dbUpdateData::addNewBoardToUser($_POST, $userObj->getId());
			if ($addNewBoardToUserReturn) {
				$success_msg = "Board added successfully.<br>Wait for the next Lora update.";
			} else {
				writeToLogFunction::write_to_log("Board not added.", $_SERVER["SCRIPT_FILENAME"]);
				//writeToLogFunction::write_to_log($e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
				$error_msg = "Board not added.";
			}
		} catch (Exception $e) {
			$error_msg = $e->getMessage();
			writeToLogFunction::write_to_log($error_msg, $_SERVER["SCRIPT_FILENAME"]);
			writeToLogFunction::write_to_log($e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
		}
	}
	else if($save == 'serverSetting') {
		try {
			$config->saveServerSettings($_POST);
			$varDemoMode = $config::$demoMode;
			$varShowQrCode = $config::$ShowQrCode;
			$var_apiKey = $config::$apiKey;
			$varSend_emails = $config::$sendEmails;
			$success_msg = "Server settings saved.";
			//header("Refresh:0; url=settings.php");
		} catch (Exception $e) {
			writeToLogFunction::write_to_log("Server settings not saved.", $_SERVER["SCRIPT_FILENAME"]);
			writeToLogFunction::write_to_log($e->getMessage(), $_SERVER["SCRIPT_FILENAME"]);
			$error_msg = $e->getMessage();
		}
	}
}

// write passed data back to the database
if (isset($_POST['submit_formBoards']))	// Submit-Button of the input mask was pressed
{
	try {
		$updateBoardReturn = dbUpdateData::updateBoard($_POST);
		$success_msg = "Board successfully updated.";
	} catch (Exception $e) {
		$error_msg = "Error while saving board changes.";
	}
} elseif (isset($_POST['submit_formBoards_remove']))	// remove-Button of the input mask was pressed
{
	try {
		$updateBoardReturn = dbUpdateData::removeBoardOwner($_POST);
		$success_msg = "Board successfully removed.";
	} catch (Exception $e) {
		$error_msg = "Error while removing board.";
	}
}

include("common/header.inc.php");
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

<link href="https://cdn.jsdelivr.net/npm/bootstrap5-toggle@5.0.4/css/bootstrap5-toggle.min.css" rel="stylesheet">

<script src="https://cdn.jsdelivr.net/npm/bootstrap5-toggle@5.0.4/js/bootstrap5-toggle.jquery.min.js"></script>

<div class="jumbotron" style="padding: 1rem 1rem;">
	<div class="container">
		<h1>Settings</h1>
	</div>
</div>
<div class="container-xl main-container">
	<div id="alert-container"></div>
	<?php
	if(isset($success_msg) && !empty($success_msg)):
	?>	<script>
			window.onload = function () {
				//on success
				g = document.createElement('div');
                g.setAttribute("class", "alert alert-success alert-dismissible bg-opacity-70 bg-gray bg-opacity-20 shadow-risen");
                g.setAttribute("role", "alert");
                g.innerHTML = "<?php echo $success_msg; ?><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
                const bsAlert = new bootstrap.Alert(g);
                // Dismiss time out
                setTimeout(() => {
            	    bsAlert.close();
                }, 5000);
                $("#alert-container").append(g);
			}
		</script>
	<?php endif; ?>

	<?php
	if(isset($error_msg) && !empty($error_msg)):
	?>
		<script>
			window.onload = function () {
				//on error
				g = document.createElement('div');
				g.setAttribute("class", "alert alert-danger alert-dismissible bg-opacity-70 bg-gray bg-opacity-20 shadow-risen");
				g.setAttribute("role", "alert");
				g.innerHTML = "<?php echo $error_msg; ?><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>";
				const bsAlert = new bootstrap.Alert(g);
				// Dismiss time out
				setTimeout(() => {
					bsAlert.close();
				}, 5000);
				$("#alert-container").append(g);
			}
		</script>
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
				if(($userObj->getUserGroupAdmin() == 1) ) {
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
							<label for="inputFirstName" class="col-sm-2 control-label">First name</label>
							<div class="col-sm-4">
								<input class="form-control" id="inputFirstName" name="firstName" type="text" value="<?php echo htmlentities($userObj->getFirstName()); ?>" required>
							</div>
						</div>
					</div>

					<div class="form-group">
						<div class="row">
							<label for="inputLastname" class="col-sm-2 control-label">Last name</label>
							<div class="col-sm-4">
								<input class="form-control" id="inputLastname" name="lastName" type="text" value="<?php echo htmlentities($userObj->getLastName()); ?>" required>
							</div>
						</div>
					</div>

					<?php
						function timeZoneList() {
							$allZones = array();
							$timestamp = time();
							foreach(timezone_identifiers_list() as $key => $live_zone) {
								date_default_timezone_set($live_zone);
								$allZones[$key]['zone'] = $live_zone;
								$allZones[$key]['diff_from_GMT'] = 'UTC/GMT ' . date('P', $timestamp);
							}
							return $allZones;
						}
					?>
					<div class="form-group">
						<div class="row">
							<label for="inputTimezone" class="col-sm-2 control-label">Timezone</label>
							<?php $userTimezone = htmlentities($userObj->getTimezone() ?? ""); ?>
							<div class="col-sm-4">
							<select class="form-select" aria-label="Default select example" id="inputTimezone" name="Timezone">
								<option value="0">Please, select your timezone</option>
								<?php foreach(timeZoneList() as $t) { ?>
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
								if ($userObj->getReceiveNotifications()) {
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
				<p style="margin-bottom: 0px; margin-top: 1rem;">To change your email address, please enter your current password and the new email address.</p>
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
								<input class="form-control" id="inputEmail" name="email" type="email" value="<?php echo htmlentities($userObj->getEmail()); ?>" required>
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
				<span>toggle columns:</span>
					<div class="form-check form-switch d-inline-block pe-4">
						<label for="inlineCheckbox1" class="form-check-label">id</label>
						<input id="inlineCheckbox1" value="toggleDisplayId" class="form-check-input myToggleButton" type="checkbox" checked data-size="small">
					</div>
					<div class="form-check form-switch d-inline-block pe-4">
						<label for="inlineCheckbox2" class="form-check-label">Mac address</label>
						<input id="inlineCheckbox2" value="toggleDisplayMacAddress" class="form-check-input myToggleButton" type="checkbox" checked data-size="small">
					</div>

					<div class="form-check form-switch d-inline-block pe-4">
						<label for="inlineCheckbox3" class="form-check-label">Location</label>
						<input id="inlineCheckbox3" value="toggleDisplayLocation" class="form-check-input myToggleButton" type="checkbox" checked data-size="small">
					</div>

					<div class="form-check form-switch d-inline-block pe-4">
						<label for="inlineCheckbox4" class="form-check-label">TTN id</label>
						<input id="inlineCheckbox4" value="toggleDisplayTtnDevId" class="form-check-input myToggleButton" type="checkbox" checked data-size="small">
					</div>

					<div class="form-check form-check-inline">
						<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">Add new Board</button>
					</div>

				</div>
				<div class="panel panel-default table-responsive">
					<table class="table table-bordered">
					<thead>
					<tr>
						<th class='toggleDisplayId'>id</th>
						<th class='toggleDisplayMacAddress '><div><span>Mac address</span></div></th>
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
					$myBoards = $userObj->getMyBoardsAll();
					$countBoardRow = 1;
					foreach($myBoards as $singleRowMyBoard) {
					?>
						<tr>
						<td class='toggleDisplayId'> <?php echo $singleRowMyBoard['id'] ?></td>
						<td class='toggleDisplayMacAddress' style='word-wrap: break-word;min-width: 160px;max-width: 160px;'><?php echo $singleRowMyBoard['macAddress'] ?></td>
						<td><?php echo $singleRowMyBoard['name'] ?></td>
						<td class='toggleDisplayLocation'><?php echo $singleRowMyBoard['location'] ?></td>
						<td><?php echo $singleRowMyBoard['description'] ?></td>
						<td class='toggleDisplayTtnDevId' style='word-wrap: break-word;min-width: 160px;max-width: 160px;'><?php echo $singleRowMyBoard['ttnDevId'] ?></td>
					<?php
						$sensorsOfBoard = myFunctions::getAllSensorsOfBoardOld($singleRowMyBoard['id']);
						echo "<td>".count($sensorsOfBoard)."</td>";

						if(isset($singleRowMyBoard['alarmOnUnavailable']) && $singleRowMyBoard['alarmOnUnavailable'] == '1') {
						?>
							<td><input type='checkbox' class='form-check-input' id='alarmOnUnavailable<?php echo$singleRowMyBoard['id']?> "' disabled name='alarmOnUnavailable' value=" <?php echo $singleRowMyBoard['alarmOnUnavailable'] ?> " checked=" <?php echo $singleRowMyBoard['alarmOnUnavailable'] ?> "></td>
						<?php
						} else {
						?>
							<td><input type='checkbox' class='form-check-input' id='alarmOnUnavailable" <?php echo$singleRowMyBoard['id'] ?> "' disabled name='alarmOnUnavailable' value='1'></td>
						<?php
						}
						?>
							<td><a href="formBoards.php?id=<?php echo $singleRowMyBoard['id'] ?>"><i class='bi bi-pencil-fill'> </i></td>
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
						<form class="row g-3" action="?save=addNewUserToBoard" method="post" class="form-horizontal">
							<div class="modal-header">
								<h5 class="modal-title" id="exampleModalLabel">Add new Board</h5>
								<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
							</div>
							<div class="modal-body">
								<div class="input-group mb-3">
									<select class="form-select input-group-text" aria-label="Default select example" id="valueType" name="valueType">
										<option value="ttn" selected>TTN dev id</option>
										<option value="mac">Mac Address</option>
									</select>
									<input type="text" class="form-control" placeholder="Enter Value" aria-label="Value" aria-describedby="macAddress" name="inputValue" id="inputValue" required>
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
									<input class="form-control" id="inputUpdateInterval" name="updateInterval" type="number" value="<?php echo htmlentities($userObj->getDashboardUpdateInterval()); ?>" required>
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
				<span>toggle columns:</span>
					<div class="form-check form-switch d-inline-block pe-4">
						<label for="inlineCheckbox1" class="form-check-label">id</label>
						<input id="inlineCheckbox1" value="toggleDisplayId" class="form-check-input myToggleButton" type="checkbox" checked data-size="small">
					</div>
					<div class="form-check form-switch d-inline-block pe-4">
						<label for="inlineCheckbox2" class="form-check-label">Mac address</label>
						<input id="inlineCheckbox2" value="toggleDisplayMacAddress" class="form-check-input myToggleButton" type="checkbox" checked data-size="small">
					</div>

					<div class="form-check form-switch d-inline-block pe-4">
						<label for="inlineCheckbox3" class="form-check-label">Location</label>
						<input id="inlineCheckbox3" value="toggleDisplayLocation" class="form-check-input myToggleButton" type="checkbox" checked data-size="small">
					</div>

					<div class="form-check form-switch d-inline-block pe-4">
						<label for="inlineCheckbox4" class="form-check-label">TTN App id</label>
						<input id="inlineCheckbox4" value="toggleDisplayTtnAppId" class="form-check-input myToggleButton" type="checkbox" checked data-size="small">
					</div>
					<div class="form-check form-switch d-inline-block pe-4">
						<label for="inlineCheckbox5" class="form-check-label">TTN Dev id</label>
						<input id="inlineCheckbox5" value="toggleDisplayTtnDevId" class="form-check-input myToggleButton" type="checkbox" checked data-size="small">
					</div>

				</div>
				<div class="panel panel-default table-responsive">
					<table class="table table-bordered">
					<thead>
					<tr>
						<th class='toggleDisplayId'>id</th>
						<th class='toggleDisplayMacAddress '><div><span>Mac address</span></div></th>
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
					$myBoards = $userObj->getAllBoardsAdmin();
					$countBoardRow = 1;
					foreach($myBoards as $singleRowMyBoard) {
					?>
						<tr>
						<td class='toggleDisplayId'> <?php echo $singleRowMyBoard['id'] ?></td>
						<td class='toggleDisplayMacAddress' style='word-wrap: break-word;min-width: 160px;max-width: 160px;'><?php echo $singleRowMyBoard['macAddress'] ?></td>
						<td><?php echo $singleRowMyBoard['ownerUserId'] ?></td>

						<td><?php echo $singleRowMyBoard['name'] ?></td>
						<td class='toggleDisplayLocation'><?php echo $singleRowMyBoard['location'] ?></td>
						<td><?php echo $singleRowMyBoard['description'] ?></td>
						<td class='toggleDisplayTtnAppId' style='word-wrap: break-word;min-width: 160px;max-width: 160px;'><?php echo $singleRowMyBoard['ttnAppId'] ?></td>
						<td class='toggleDisplayTtnDevId' style='word-wrap: break-word;min-width: 160px;max-width: 160px;'><?php echo $singleRowMyBoard['ttnDevId'] ?></td>
					<?php
						$sensorsOfBoard = myFunctions::getAllSensorsOfBoardOld($singleRowMyBoard['id']);
						echo "<td>".count($sensorsOfBoard)."</td>";

						if(isset($singleRowMyBoard['alarmOnUnavailable']) && $singleRowMyBoard['alarmOnUnavailable'] == '1') {
						?>
							<td><input type='checkbox' class='form-check-input' id='alarmOnUnavailable<?php echo$singleRowMyBoard['id']?> "' disabled name='alarmOnUnavailable' value=" <?php echo $singleRowMyBoard['alarmOnUnavailable'] ?> " checked=" <?php echo $singleRowMyBoard['alarmOnUnavailable'] ?> "></td>
						<?php
						} else {
						?>
							<td><input type='checkbox' class='form-check-input' id='alarmOnUnavailable" <?php echo$singleRowMyBoard['id'] ?> "' disabled name='alarmOnUnavailable' value='1'></td>
						<?php
						}
						?>
							<td><a href="formBoards.php?id=<?php echo $singleRowMyBoard['id'] ?>"><i class='bi bi-pencil-fill'> </i></td>
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
					if(($userObj->getUserGroupAdmin() != false) ) {
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
								<td><?php echo $singleRowUser['firstName'] ?></td>
								<td><?php echo $singleRowUser['lastName'] ?></td>
								<td><a href="mailto:<?php echo $singleRowUser['email'] ?>"><?php echo $singleRowUser['email'] ?></a></td>
								<?php
								if(isset($singleRowUser['userGroupAdmin']) && $singleRowUser['userGroupAdmin'] == '1')
								{
								?>
									<td><input type='hidden' class='form-check-input' id='userGroupAdmin<?php echo $singleRowUser['id'] ?>' name='userGroupAdmin[<?php echo $singleRowUser['id'] ?>]' value='0' checked=<?php echo $singleRowUser['userGroupAdmin'] ?>>
									<input type='checkbox' class='form-check-input' id='userGroupAdmin<?php echo $singleRowUser['id'] ?>' name='userGroupAdmin[<?php echo $singleRowUser['id'] ?>]' value='1' checked=<?php echo $singleRowUser['userGroupAdmin'] ?>></td>
								<?php
								}
								else
								{
								?>
									<td><input type='hidden' class='form-check-input' id='userGroupAdmin<?php echo $singleRowUser['id'] ?>' name='userGroupAdmin[<?php echo $singleRowUser['id'] ?>]' value='0'>
									<input type='checkbox' class='form-check-input' id='userGroupAdmin<?php echo $singleRowUser['id'] ?>' name='userGroupAdmin[<?php echo $singleRowUser['id'] ?>]' value='1'></td>
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
									<input class="form-control" id="apiKey" name="apiKey" type="text" value="<?php echo $config::$apiKey; ?>" required>
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
										<input type='hidden' class='form-check-input' id='sendEmails' name='sendEmails' value='0'>
										<input type='checkbox' class='form-check-input' id='sendEmails' name='sendEmails' checked=true value='1'>
								</label>
							<?php
							} else {
							?>
								<div class="col col-sm-2">
								Send emails:
								</div>
								<label class="col col-sm-4">
										<input type='hidden' class='form-check-input' id='sendEmails' name='sendEmails' checked=true value='0'>
										<input type='checkbox' class='form-check-input' id='sendEmails' name='sendEmails' value='1'>
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
								<label for="systemEmailAddress" class="col col-sm-2 control-label">System Email Address (sender):</label>
								<div class="col col-sm-4">
									<input class="form-control" id="systemEmailAddress" name="systemEmailAddress" type="text" value="<?php echo $config::$systemEmailAddress; ?>" required>
								</div>
							</div>
						</div>
					</div>

					<div class="panel panel-default">
						<div class="form-group">
							<div class="row">
								<label for="applicationName" class="col col-sm-2 control-label">Application Name:</label>
								<div class="col col-sm-4">
									<input class="form-control" id="applicationName" name="applicationName" type="text" value="<?php echo $config::$applicationName; ?>" required>
								</div>
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
						$months = array(1 => "Januar", 2 => "Februar", 3 => "Maerz", 4 => "April", 5 => "Mai", 6 => "Juni", 7 => "Juli", 8 => "August", 9 => "September", 10 => "Oktober", 11 => "November", 12 => "Dezember");
						$month = date("n");
						$year = date("Y");
						$filename = dirname(__FILE__) . "/../logs/log_" . $months[$month] . "_$year.$format";
						$data = false;

						if (file_exists($filename)) {
							$data = file_get_contents($filename);
							if ($data != false) {
								echo '<textarea style="height: 400px; width: 100%; font-family: revert;" readonly>' . htmlspecialchars($data). '</textarea>';
							} else {
								echo '<textarea style="height: 400px; width: 100%; font-family: revert;" readonly>Error while opening Log file.</textarea>';
							}
						} else {
							echo '<textarea style="height: 400px; width: 100%; font-family: revert;" readonly>Log file not found.</textarea>';
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
		$('.myToggleButton').change(function() {	
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