<?php
/**
 *
 * @author: Guntmar Höche
 */

session_start();
require_once("func/dbConfig.func.php");
require_once("func/myFunctions.func.php");
require_once("func/user.class.php");
require_once("func/dbUpdateData.php");
include("common/header.inc.php");
require_once("func/writeToLogFunction.func.php");
$config  = new configuration();
?>
<div class="container small-container-330">
	<h2>Reset password</h2>
	<?php
	$showForm = true;
	if (isset($_GET['send'])) {
		if (!isset($_POST['email']) || empty($_POST['email'])) {
			$error = "<b>Please enter your mail address.</b>";
		} else {
			try {
				$user = new user($_POST['email']);
			} catch (Exception $e) {
				echo '<div class="alert alert-danger" role="alert">Email not found.</div>';
				exit;
			}
			if ($user === false) {
				$error = "<b>Username not found</b>";
			} else if (!$user->isActive()){ 
				echo '<div class="alert alert-danger" role="alert">Your Account is not active.</div>';
				$showForm = false;
			} else {
				$passwordCode = myFunctions::random_string();
				//$securityToken = dbUpdateData::insertSecurityToken($user->getId());
				try {
					$result = dbUpdateData::updateUserPasswordCode($passwordCode, $user->getId());
				} catch (Exception $e) {
					$error_msg = $e->getMessage();
				}
				
				$mailTo = strval($user->getEmail());
				$reference = "New password for your account on " . $config::$applicationName;
				$from = "From: " . $config::$applicationName . " <" . $config::$systemEmailAddress . ">";
				$url_passwordCode = myFunctions::getSiteURL() . 'resetPassword.php?userId=' . $user->getId() . '&code=' . $passwordCode . "&action=reset";
				$text = "Hi " . $user->getFirstName() . ",\r\n";
				$text .= "you requested a new password for your account on " . $config::$applicationName . "\r\n \r\n";
				$text .= "To enter a new password open the following link within the next 24h: " . $url_passwordCode . "\r\n \r\n";
				$text .= "You can ignore this mail, if remember your password again, or didn't requested a new password.\r\n \r\n";
				$text .= "best regards,\r\n";
				$text .= "your " . $config::$applicationName . " Team\r\n";
				if (mail($mailTo, $reference, $text, $from)) {
					echo "A link to reset your password was send.";
				} else {
					writeToLogFunction::write_to_log("Error: Unable to send email to: " . $mailTo, $_SERVER["SCRIPT_FILENAME"]);
					echo "Error: Unable to send email.";
				}
				$showForm = false;
			}
		}
	}
	?>

	<?php
		$error = "";
		if (isset($_GET["code"]) && isset($_GET["userId"]) && isset($_GET["action"]) && ($_GET["action"]=="reset") && !isset($_POST["action"])){
			$key = $_GET["code"];
			$userId = $_GET["userId"];
			$curDate = date("Y-m-d H:i:s");
			$userObj = dbUpdateData::readUserPasswordCode($_GET["code"], $_GET["userId"]);

			if ($userObj==""){
				$error .= '<h2>Invalid Link</h2>
				<p>The link is invalid/expired. Either you did not copy the correct link
				from the email, or you have already used the key in which case it is 
				deactivated.</p>';
			}else{
				$expDate = $userObj['passwordCodeTime'];
				if ($expDate >= $curDate){
					?>
					<br />
					<form method="post" action="" name="update">
					<input type="hidden" name="action" value="update" />
					<br /><br />
					<label><strong>Enter New Password:</strong></label><br />
					<input type="password" name="pass1" maxlength="15" required />
					<br /><br />
					<label><strong>Re-Enter New Password:</strong></label><br />
					<input type="password" name="pass2" maxlength="15" required/>
					<br /><br />
					<input type="hidden" name="userId" value="<?php echo $userId;?>"/>
					<input type="submit" value="Reset Password" />
					</form>
					<?php
				}else{
					$error .= "<h2>Link Expired</h2>
					<p>The link is expired. You are trying to use the expired link which 
					as valid only 24 hours (1 days after request).<br /><br /></p>";
				}
			}
			if($error!=""){
				echo "<div class='error'>".$error."</div><br />";
			}
			$showForm = false;
		} // isset userId key validate end

		if(isset($_POST["userId"]) && isset($_POST["action"]) && ($_POST["action"]=="update")){
			$error="";
			$userObj_temp = dbGetData::getUserById($_POST["userId"]);
			$userObj = new user($userObj_temp["email"]);

			$pass1 = trim($_POST['pass1']);
			$pass2 = trim($_POST['pass2']);
			$curDate = date("Y-m-d H:i:s");
			if ($pass1!=$pass2){
				$error.= "<p>Password do not match, both password should be same.<br /><br /></p>";
			}
			if($error!=""){
				echo "<div class='error'>".$error."</div><br />";
			}else{
				$password_hash = password_hash($pass1, PASSWORD_DEFAULT);
				$userObj->setUserPassword($password_hash);
				dbUpdateData::updateUserPasswordCode("", $userObj->getId());
				echo '<div class="error"><p>Congratulations! Your password has been updated successfully.</p></div><br />';
			}
			$showForm = false;
		}
	?>
	
	<?php
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
