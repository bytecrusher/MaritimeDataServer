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
include("common/header.inc.php");
?>
<div class="container small-container-330">
	<h2>Reset password</h2>
	<?php
	$showForm = true;
	if (isset($_GET['send'])) {
		if (!isset($_POST['email']) || empty($_POST['email'])) {
			$error = "<b>Please enter mailaddress</b>";
		} else {
			$user = new user($_POST['email']);
			if ($user === false) {
				$error = "<b>Username not found</b>";
			} else if (!$user->isActive()){ 
				echo '<div class="alert alert-danger" role="alert">Your Account is not active.</div>';
				$showForm = false;
			} else {
				$passwordcode = myFunctions::random_string();
				$result = dbUpdateData::updateUserPasswordcode($passwordcode, $user->getId());
				$empfaenger = strval($user->getEmail()); //['email'];
				$betreff = "New password for your account on " . myFunctions::getSiteURL();
				$from = "From: Guntmar <info@derguntmar.de>"; // TODO: Ersetzt hier euren Name und E-Mail-Adresse
				$url_passwordcode = myFunctions::getSiteURL() . 'resetPassword.php?userid=' . $user->getId() /*['id']*/ . '&code=' . $passwordcode;
				$text = 'Hi ' . $user->getFirstname() /*['firstname']*/ . ',
					for your account on ' . myFunctions::getSiteURL() . ' a new password was requested. To enter a new password open the following link:
					' . $url_passwordcode . '
					You can ignore this mail, if remeber your password again, or didnt requested a new password.
					best regards,
					your derguntmar.de-Team';
				mail($empfaenger, $betreff, $text, $from);
				echo "A link to reset your password was send.";
				$showForm = false;
			}
		}
	}

	if ($showForm) :
	?>
		Enter your email address to receive an new password.<br><br>
		<?php
		if (isset($error) && !empty($error)) {
			echo $error;
		}

		?>
		<form action="?send=1" method="post">
			<label for="inputEmail">E-Mail</label>
			<input class="form-control" placeholder="E-Mail" name="email" type="email" value="<?php echo isset($_POST['email']) ? htmlentities($_POST['email']) : ''; ?>" required>
			<br>
			<input class="btn btn-lg btn-primary btn-block" type="submit" value="new password">
		</form>
	<?php
	endif; //Endif von if($showForm)
	?>
</div>

<?php
include("common/footer.inc.php")
?>
