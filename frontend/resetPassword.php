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
			// TODO change to new user class.
			$user = dbGetData::getUserOld($_POST['email']);
			if ($user === false) {
				$error = "<b>Username not found</b>";
			} else {
				$passwordcode = myFunctions::random_string();
				$result = dbUpdateData::updateUserPasswordcode($passwordcode, $user);
				$empfaenger = $user['email'];
				$betreff = "New password for your account on www.derguntmar.de"; //Ersetzt hier den Domain-Namen
				$from = "From: Guntmar <info@derguntmar.de>"; //Ersetzt hier euren Name und E-Mail-Adresse
				$url_passwordcode = myFunctions::getSiteURL() . 'resetPassword.php?userid=' . $user['id'] . '&code=' . $passwordcode; //Setzt hier eure richtige Domain ein
				$text = 'Hi ' . $user['firstname'] . ',
					for your account on www.derguntmar.de a new password was requested. To enter a new password open the following link:
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
