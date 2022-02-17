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

//Check that the user is logged in
if (isset($_SESSION['userobj'])) {
	$user = unserialize($_SESSION['userobj']);
} else {
	$user = false;
}

function fixObject (&$object)
{
  if (!is_object ($object) && gettype ($object) == 'object')
    return ($object = unserialize (serialize ($object)));
  return $object;
}

$userobj = unserialize($_SESSION['userobj']);

if(isset($_GET['save'])) {
	$save = $_GET['save'];
	if($save == 'personal_data') {
		$updateUserReturn = $user->setName($_POST);
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
		} else if(!password_verify($password, $user['password'])) {
			$error_msg = "Wrong password.";
		} else {
			$updateUserPasswordReturn = dbUpdateData::updateUserMail($_POST, $user->getId);
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
		} else if(!password_verify($passwordAlt, $user['password'])) {
			$error_msg = "Please enter correct password.";
		} else {
			$password_hash = password_hash($passwordNew, PASSWORD_DEFAULT);

			$updateUserPasswordReturn = dbUpdateData::updateUserPassword($password_hash, $user->getId);
	 	 	if (!$updateUserPasswordReturn) {
	 		 	$error_msg = "Error on update User Password.";
	 	 	} else {
	 		 	$success_msg = $updateUserPasswordReturn;
	 	 	}

		}
	} else if($save == 'dashboard_data') {
		$updateUserReturn = $user->setDashboardUpdateInterval($_POST);
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
}

// write passed data back to the database
 if (isset($_POST['submit_eingabemaske_boards']))	// Submit-Button of the input mask was pressed
 {
	 $updateBoardReturn = dbUpdateData::updateBoard($_POST);
	 if (!$updateBoardReturn) {
		 $error_msg = "Error while saving board changes.";
	 } else {
		 $success_msg = $updateBoardReturn;
	 }

 } elseif (isset($_POST['submit_eingabemaske_sensors'])) {
	$updateBoardReturn = dbUpdateData::updateSensor($_POST);
	if (!$updateBoardReturn) {
		$error_msg = "Error while saving changes to sensors.";
	} else {
		$success_msg = $updateBoardReturn;
	}
 }
?>
<div class="jumbotron" style="padding: 1rem 1rem; margin-bottom: 1rem;">
	<div class="container">
		<h1>Settings</h1>

	</div>
</div>

<svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
  <symbol id="check-circle-fill" fill="currentColor" viewBox="0 0 16 16">
    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
  </symbol>
  <symbol id="info-fill" fill="currentColor" viewBox="0 0 16 16">
    <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z"/>
  </symbol>
  <symbol id="exclamation-triangle-fill" fill="currentColor" viewBox="0 0 16 16">
    <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
  </symbol>
</svg>

<div class="container main-container">
	<?php
	if(isset($success_msg) && !empty($success_msg)):
	?>
		<div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert">
			<svg class="bi flex-shrink-0 me-2" width="24" height="24" role="img" aria-label="Success:"><use xlink:href="#check-circle-fill"/></svg>
			<div>
				<?php echo $success_msg; ?>
			</div>
			<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
			
		</div>
	<?php endif; ?>

	<?php
	if(isset($error_msg) && !empty($error_msg)):
	?>
		<div class="alert alert-danger alert-dismissible fade show d-flex align-items-center" role="alert">
			<svg class="bi flex-shrink-0 me-2" width="24" height="24" role="img" aria-label="Danger:"><use xlink:href="#exclamation-triangle-fill"/></svg>
			<div>
			<?php echo $error_msg; ?>
			</div>
			<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
		</div>
	<?php endif; ?>
	<div>
		<!-- Nav tabs -->
		<ul class="nav nav-tabs" role="tablist">
			<li class="nav-item" role="presentation">
				<!--a class="nav-link active" href="#data" role="tab" data-toggle="tab">Personal data</a-->
				<button class="nav-link active" id="data-tab" data-bs-toggle="tab" data-bs-target="#data" type="button" role="tab" aria-controls="data" aria-selected="true">Personal data</button>
			</li>
			<li class="nav-item" role="presentation">
				<!--a class="nav-link" href="#email" role="tab" data-toggle="tab">E-Mail</a-->
				<button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button" role="tab" aria-controls="email" aria-selected="false">E-Mail</button>
			</li>
			<li class="nav-item" role="presentation">
				<!--a class="nav-link" href="#password" role="tab" data-toggle="tab">Password</a-->
				<button class="nav-link" id="password-tab" data-bs-toggle="tab" data-bs-target="#password" type="button" role="tab" aria-controls="password" aria-selected="false">Password</button>
			</li>
			<li class="nav-item" role="presentation">
				<!--a class="nav-link" href="#confBoards" role="tab" data-toggle="tab">Boards</a-->
				<button class="nav-link" id="confBoards-tab" data-bs-toggle="tab" data-bs-target="#confBoards" type="button" role="tab" aria-controls="confBoards" aria-selected="false">Boards</button>
			</li>
			<li class="nav-item" role="presentation">
				<!--a class="nav-link" href="#confDashboard" role="tab" data-toggle="tab">Dashboard</a-->
				<button class="nav-link" id="confDashboard-tab" data-bs-toggle="tab" data-bs-target="#confDashboard" type="button" role="tab" aria-controls="confDashboard" aria-selected="false">Dashboard</button>
			</li>
			<?php
				if(($userobj->getUserGroupAdmin() == 1) ) {
					echo "<li class='nav-item' role='presentation'><!--a class='nav-link' href='#users' role='tab' data-toggle='tab'>Users</a-->
					<button class='nav-link' id='users-tab' data-bs-toggle='tab' data-bs-target='#users' type='button' role='tab' aria-controls='users' aria-selected='false'>Users</button>
					</li>";
				}
			?>
		</ul>

		<!-- Personal data -->
		<div class="tab-content">
			<div role="tabpanel" class="tab-pane active" id="data" role="tabpanel" aria-labelledby="data-tab">
				<br>
				<form action="?save=personal_data" method="post" class="form-horizontal">
					<div class="form-group">
						<div class="row mb-2">
							<label for="inputFirstname" class="col-sm-2 control-label">First name</label>
							<div class="col-sm-4">
								<input class="form-control" id="inputFirstname" name="firstname" type="text" value="<?php echo htmlentities($user->getFirstname()); ?>" required>
							</div>
							</div>
					</div>

					<div class="form-group">
						<div class="row mb-2">
							<label for="inputLastname" class="col-sm-2 control-label">Last name</label>
							<div class="col-sm-4">
								<input class="form-control" id="inputLastname" name="lastname" type="text" value="<?php echo htmlentities($user->getLastname()); ?>" required>
							</div>
						</div>
					</div>

					<div class="form-group">
						<div class="row mb-2">
							<div class="col-sm-4">
							<button type="submit" class="form-control btn btn-primary">Save</button>
							</div>
						</div>
					</div>
				</form>
			</div>

			<!-- change of email address -->
			<div role="tabpanel" class="tab-pane" id="email" role="tabpanel" aria-labelledby="email-tab">
				<br>
				<p>To change your email adress, please enter your current password and the new email adress.</p>
				<form action="?save=email" method="post" class="form-horizontal">
					<div class="form-group">
						<div class="row mb-2">
							<label for="inputPasswordForValidation" class="col-sm-2 control-label">Password</label>
							<div class="col-sm-4">
								<input class="form-control" id="inputPasswordForValidation" name="password" type="password" required>
							</div>
						</div>
					</div>

					<div class="form-group">
						<div class="row mb-2">
							<label for="inputEmail" class="col-sm-2 control-label">E-Mail</label>
							<div class="col-sm-4">
								<input class="form-control" id="inputEmail" name="email" type="email" value="<?php echo htmlentities($user->getEmail()); ?>" required>
							</div>
						</div>
					</div>

					<div class="form-group">
						<div class="row mb-2">
							<label for="inputEmail2" class="col-sm-2 control-label">E-Mail (repeat)</label>
							<div class="col-sm-4">
								<input class="form-control" id="inputEmail2" name="email2" type="email"  required>
							</div>
						</div>
					</div>

					<div class="form-group">
						<div class="row mb-2">
							<div class="col-sm-4">
							<button type="submit" class="form-control btn btn-primary">Save</button>
							</div>
						</div>
					</div>
				</form>
			</div>

			<!-- change password -->
			<div role="tabpanel" class="tab-pane" id="password" role="tabpanel" aria-labelledby="password-tab">
				<br>
				<p>To change your password, please enter your current password and the new password.</p>
				<form action="?save=password" method="post" class="form-horizontal">
					<div class="form-group">
						<div class="row mb-2">
							<label for="inputPassword" class="col-sm-3 control-label">Old Password</label>
							<div class="col-sm-4">
								<input class="form-control" id="inputPasswordOld" name="passwordOld" type="password" required>
							</div>
						</div>
					</div>

					<div class="form-group">
						<div class="row mb-2">
							<label for="inputPasswordNew" class="col-sm-3 control-label">New password</label>
							<div class="col-sm-4">
								<input class="form-control" id="inputPasswordNew" name="passwordNew" type="password" required>
							</div>
						</div>
					</div>

					<div class="form-group">
						<div class="row mb-2">
							<label for="inputPasswordNew2" class="col-sm-3 control-label">New password (repeat)</label>
							<div class="col-sm-4">
								<input class="form-control" id="inputPasswordNew" name="passwordNew2" type="password"  required>
							</div>
						</div>
					</div>

					<div class="form-group">
						<div class="row mb-2">
							<div class="col-sm-4">
							<button type="submit" class="form-control btn btn-primary">Save</button>
							</div>
						</div>
					</div>
				</form>
			</div>


			<!-- Configure the user's boards -->
			<div role="tabpanel" class="tab-pane" id="confBoards" role="tabpanel" aria-labelledby="confBoards-tab">
				<div class="panel panel-default">
					<table class="table">
					<thead>
					<tr>
						<th>id</th><th>Mac Addresse</th><th>Name</th><th>Location</th><th>Description</th><th>Sensors</th><th>Alarm</th><th>Details</th>
					</tr>
					</thead>
					<tbody>
					<?php
					$myboards = myFunctions::getMyBoards($user->getId());
					$countBoardRow = 1;
					foreach($myboards as $singleRowMyboard) {
						echo "<tr>";
						echo "<td>".$singleRowMyboard['id']."</td>";
						echo "<td>".$singleRowMyboard['macaddress']."</td>";
						echo "<td>".$singleRowMyboard['name']."</td>";
						echo "<td>".$singleRowMyboard['location']."</td>";
						echo "<td>".$singleRowMyboard['description']."</td>";

						$sensorsOfBoard = myFunctions::getAllSensorsOfBoard($singleRowMyboard['id']);
						echo "<td>".count($sensorsOfBoard)."</td>";

						if(isset($singleRowMyboard['alarmOnUnavailable']) && $singleRowMyboard['alarmOnUnavailable'] == '1') {
							echo "<td><input type='checkbox' class='form-check-input' id='alarmOnUnavailable" .$singleRowMyboard['id']. "' disabled name='alarmOnUnavailable' value=" . $singleRowMyboard['alarmOnUnavailable'] . " checked=" . $singleRowMyboard['alarmOnUnavailable'] . " style='margin-left: 1.25rem'></td>";
						} else {
							echo "<td><input type='checkbox' class='form-check-input' id='alarmOnUnavailable" .$singleRowMyboard['id']. "' disabled name='alarmOnUnavailable' value='1' style='margin-left: 1.25rem'></td>";
						}
						echo "<td><a href=\"eingabemaske_boards.php?id=" . $singleRowMyboard['id'] . "\"><i class='fas fa-pencil-alt'> </i></td>";
						echo "</tr>";
					}
					?>
					</tbody></table>
				</div>
			</div>

			<!-- Configure the user's dashboard -->
			<div role="tabpanel" class="tab-pane" id="confDashboard" role="tabpanel" aria-labelledby="confDashboard-tab">
				<div class="panel panel-default">
					<br>
					<form action="?save=dashboard_data" method="post" class="form-horizontal">
						<div class="form-group">
							<div class="row mb-2">
								<label for="inputUpdateInterval" class="col-sm-4 control-label">Update interval (in Minutes)</label>
								<div class="col-sm-4">
									<input class="form-control" id="inputUpdateInterval" name="updateInterval" type="number" value="<?php echo htmlentities($user->getDashboardUpdateInterval()); ?>" required>
								</div>
								</div>
						</div>


						<div class="form-group">
							<div class="row mb-2">
								<div class="col-sm-4">
								<button type="submit" class="form-control btn btn-primary">Save</button>
								</div>
							</div>
						</div>
					</form>	
				</div>
			</div>


			<!-- Modification of other users -->
			<div role="tabpanel" class="tab-pane" id="users" role="tabpanel" aria-labelledby="users-tab">
				<br>
				<p>To change and activate Users.</p>
				<form action="?save=users" method="post" class="form-horizontal">
					<div class="panel panel-default">
					<table class="table">
					<tr>
						<th>#</th><th>Active</th><th>First name</th><th>Last name</th><th>E-Mail</th>
					</tr>
					<?php
					if(($user->getUserGroupAdmin() != false) ) {
						$count = 1;
						$statement = myFunctions::getAllUsers();

						foreach($statement as $singleRowUser) {
							echo "<tr>";
							echo "<td>".$count++."</td>";
							if(isset($singleRowUser['active']) && $singleRowUser['active'] == '1')
							{
								echo "<td><input type='hidden' class='form-check-input' id='active" . $singleRowUser['id'] . "' name='active[" . $singleRowUser['id'] . "]' value='0' checked=" . $singleRowUser['active'] . ">";
								echo "<input type='checkbox' class='form-check-input' id='active" . $singleRowUser['id'] . "' name='active[" . $singleRowUser['id'] . "]' value='1' checked=" . $singleRowUser['active'] . "></td>";
							}
							else
							{
								echo "<td><input type='hidden' class='form-check-input' id='active" . $singleRowUser['id'] . "' name='active[" . $singleRowUser['id'] . "]' value='0'>";
								echo "<input type='checkbox' class='form-check-input' id='active" . $singleRowUser['id'] . "' name='active[" . $singleRowUser['id'] . "]' value='1'></td>";
							}
							echo "<td>".$singleRowUser['firstname']."</td>";
							echo "<td>".$singleRowUser['lastname']."</td>";
							echo '<td><a href="mailto:'.$singleRowUser['email'].'">'.$singleRowUser['email'].'</a></td>';
							echo "</tr>";
						}
					}
					?>
					</table>
					</div>
					<div class="form-group">
						<div class="col-sm-4">
						<button type="submit" class="form-control btn btn-primary">Save</button>
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
</script>
