<?php
session_start();
require_once("func/dbConfig.func.php");
require_once("func/myFunctions.func.php");
require_once("func/user.class.php");
require_once("func/dbUpdateData.php");
include("common/header.inc.php")
?>
<div class="container main-container registration-form">
<h1>Register</h1>
<?php
$showFormular = true; //Variable whether the registration form should be displayed

if(isset($_GET['register'])) {
	$error = false;
	$vorname = trim($_POST['firstname']);
	$nachname = trim($_POST['lastname']);
	$email = trim($_POST['email']);
	$password = $_POST['password'];
	$password2 = $_POST['password2'];

	if(empty($vorname) || empty($nachname) || empty($email)) {
		echo 'Please enter all fields.<br>';
		$error = true;
	}

	if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		echo 'Please enter a valid email adress.<br>';
		$error = true;
	}
	if(strlen($password) == 0) {
		echo 'Password necessary.<br>';
		$error = true;
	}
	if($password != $password2) {
		echo 'Both password must be the same.<br>';
		$error = true;
	}

	//Check that the email address has not yet been registered
	if(!$error) {
		//$user = user::getUser($email);
		// TODO change to new user class.
		$user = dbGetData::getUserOld($email);
		if($user !== false) {
			echo 'The entered email adress already exist.<br>';
			$error = true;
		}
	}

	//No errors, we can register the user
	if(!$error) {
		$password_hash = password_hash($password, PASSWORD_DEFAULT);
		$result = dbUpdateData::insertUser($email, $password_hash, $vorname, $nachname);
		if($result) {
			echo "<div class='card text-white bg-success mb-3' style='max-width: 18rem;'>
				  <div class='card-header'>Registration successful</div>
				  <div class='card-body'>
				    <p class='card-text'><a href='login.php' class='btn btn-primary'>Login</a></p>
				  </div>
				</div>";
			$showFormular = false;
		} else {
			echo 'An error occurs while saving.<br>';
		}
	}

	// send mail:
		// the message
		//$msg = "A new User is registered on esp-data.derguntmar.de";
		// use wordwrap() if lines are longer than 70 characters
		//$msg = wordwrap($msg,70);
		// send email
		//mail("guntmar.h@gmx.net","new user registered",$msg);


		$to      = 'guntmar.h@gmx.net';
		$subject = 'new user registered';
		$message = 'A new User is registered on esp-data.derguntmar.de';
		$headers = 'From: info@derguntmar.de' . "\r\n" .
			'Reply-To: info@derguntmar.de' . "\r\n" .
			'X-Mailer: PHP/' . phpversion();
		mail($to, $subject, $message, $headers);

}

if($showFormular) {
?>

<form action="?register=1" method="post">

<div class="form-group">
<label for="inputVorname">Firstname:</label>
<input type="text" id="inputVorname" size="40" maxlength="250" name="firstname" class="form-control" required>
</div>

<div class="form-group">
<label for="inputNachname">Lastname:</label>
<input type="text" id="inputNachname" size="40" maxlength="250" name="lastname" class="form-control" required>
</div>

<div class="form-group">
<label for="inputEmail">E-Mail:</label>
<input type="email" id="inputEmail" size="40" maxlength="250" name="email" class="form-control" required>
</div>

<div class="form-group">
<label for="inputPassword">Password:</label>
<input type="password" id="inputPassword" size="40"  maxlength="250" name="password" class="form-control" required>
</div>

<div class="form-group">
<label for="inputPassword2">Password repeat:</label>
<input type="password" id="inputPassword2" size="40" maxlength="250" name="password2" class="form-control" required>
</div>
<button type="submit" class="btn btn-lg btn-primary btn-block">Register</button>
</form>

<?php
} //End of if($showFormular)

?>
</div>
<?php
include("common/footer.inc.php")
?>
