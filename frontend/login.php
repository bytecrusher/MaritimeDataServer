<?php
session_start();
require_once("func/dbConfig.func.php");
require_once("func/myFunctions.func.php");
require_once("func/user.class.php");
require_once("func/dbUpdateData.php");

$error_msg = "";
if(isset($_POST['email']) && isset($_POST['password'])) {
	$email = $_POST['email'];
	$password = $_POST['password'];
	$userobj = new user($email);
	
	if ($userobj->userExist() != false) {
		$_SESSION['userobj'] = serialize($userobj);
		if ($userobj->isActive() == true) {
			//Check Password
			if ($userobj !== false && password_verify($password, $userobj->getPassword()) && $userobj->isActive() != false) {
				$_SESSION['userid'] = $userobj->getId();
	
				//Does the user want to stay logged in?
				if(isset($_POST['angemeldet_bleiben'])) {
					dbUpdateData::insertSecuritytoken($userobj->getId());
				}
				header("location: internal.php");
				exit;
			} else {
				$error_msg =  "<div class='alert alert-danger' role='alert'>E-Mail or Password wrong.</div>";
			}
		} else {
			$error_msg =  "<div class='alert alert-danger' role='alert'>Account not activated yet.</div>";
		}
	} else {
		$error_msg =  "<div class='alert alert-danger' role='alert'>User does not exist.</div>";
	}
	
}

$email_value = "";
if(isset($_POST['email']))
	$email_value = htmlentities($_POST['email']);
include("common/header.inc.php");
?>
<div class="container small-container-330 form-signin">
  <form action="login.php" method="post">
	<h2 class="form-signin-heading">Login</h2>

	<?php
	if(isset($error_msg) && !empty($error_msg)) {
		echo $error_msg;
	}
	?>
	<label for="inputEmail" class="sr-only">E-Mail</label>
	<input type="email" name="email" id="inputEmail" class="form-control" placeholder="E-Mail" value="<?php echo $email_value; ?>" required autofocus>
	<label for="inputPassword" class="sr-only">Password</label>
	<input type="password" name="password" id="inputPassword" class="form-control" placeholder="Password" required>
	<div class="checkbox">
	  <label>
		<input type="checkbox" value="remember-me" name="angemeldet_bleiben" value="1" checked> remember login
	  </label>
	</div>
	<button class="btn btn-lg btn-primary btn-block" type="submit">Login</button>
	<br>
	<a href="resetPassword.php">Reset Password</a>
  </form>

</div>

<?php
include("common/footer.inc.php")
?>