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
<div class="container main-container">
	<?php
	if(isset($success_msg) && !empty($success_msg)):
	?>
		<div class="alert alert-success">
			<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
			<?php echo $success_msg; ?>
		</div>
	<?php endif; ?>

	<?php
	if(isset($error_msg) && !empty($error_msg)):
	?>
		<div class="alert alert-danger">
			<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
			<?php echo $error_msg; ?>
		</div>
	<?php endif; ?>
	<div>
		<!-- Nav tabs -->
		<ul class="nav nav-tabs" role="tablist">
			<li class="nav-item" role="presentation"><a class="nav-link active" href="#data" role="tab" data-toggle="tab">Personal data</a></li>
			<li class="nav-item" role="presentation"><a class="nav-link" href="#email" role="tab" data-toggle="tab">E-Mail</a></li>
			<li class="nav-item" role="presentation"><a class="nav-link" href="#password" role="tab" data-toggle="tab">Password</a></li>
			<li class="nav-item" role="presentation"><a class="nav-link" href="#confBoards" role="tab" data-toggle="tab">Boards</a></li>
			<li class="nav-item" role="presentation"><a class="nav-link" href="#confDashboard" role="tab" data-toggle="tab">Dashboard</a></li>
			<?php
				if(($userobj->getUserGroupAdmin() == 1) ) {
					echo "<li class='nav-item' role='presentation'><a class='nav-link' href='#users' role='tab' data-toggle='tab'>Users</a></li>";
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
								<input class="form-control" id="inputFirstname" name="firstname" type="text" value="<?php echo htmlentities($user->getFirstname()); ?>" required>
							</div>
							</div>
					</div>

					<div class="form-group">
						<div class="row">
							<label for="inputLastname" class="col-sm-2 control-label">Last name</label>
							<div class="col-sm-4">
								<input class="form-control" id="inputLastname" name="lastname" type="text" value="<?php echo htmlentities($user->getLastname()); ?>" required>
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
								<input class="form-control" id="inputEmail" name="email" type="email" value="<?php echo htmlentities($user->getEmail()); ?>" required>
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
			<div role="tabpanel" class="tab-pane" id="confDashboard">
				<div class="panel panel-default">
					<br>
					<form action="?save=dashboard_data" method="post" class="form-horizontal">
						<div class="form-group">
							<div class="row">
								<label for="inputUpdateInterval" class="col-sm-2 control-label">Update interval (in Minutes)</label>
								<div class="col-sm-4">
									<input class="form-control" id="inputUpdateInterval" name="updateInterval" type="number" value="<?php echo htmlentities($user->getDashboardUpdateInterval()); ?>" required>
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
						<div class="col-sm-offset-2 col-sm-10">
						<button type="submit" class="btn btn-primary">Save</button>
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
