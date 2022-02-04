
<?php
/**
 * create an object of the current user and provide some getter and setter functions
 * 
 * @author: Guntmar Hoeche
 * @license: TBD
 */
include_once("dbConfig.func.php");
include_once("dbGetData.php");
include_once("password.func.php");

class user implements JsonSerializable
{
  private $user = null;
  private $object;
  private $id;
  private $email;
  private $firstname;
  private $lastname;
  private $usergroup_admin;
  private $dashboardUpdateInterval;

  /**
  * Method for construct the class.
  * @param email adress of the user
  */
  public function __construct($email)
  {
    $pdo = dbConfig::getInstance();
    $statement = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $result = $statement->execute(array('email' => $email));
    $this->user = $statement->fetch(PDO::FETCH_ASSOC);
    $this->id = $this->user['id'];
    $this->email = $this->user['email'];
    $this->firstname = $this->user['firstname'];
    $this->lastname = $this->user['lastname'];
    $this->usergroup_admin = $this->user['usergroup_admin'];
    $this->dashboardUpdateInterval = $this->user['dashboardUpdateInterval'];
  }

  /**
   * Checks that the user is logged in.
   * @return Returns the row of the logged in user
   */
  public static function check_user()
  {
    $pdo = dbConfig::getInstance();
    if (!isset($_SESSION['userid']) && isset($_COOKIE['identifier']) && isset($_COOKIE['securitytoken'])) {
      $identifier = $_COOKIE['identifier'];
      $securitytoken = $_COOKIE['securitytoken'];
      $statement = $pdo->prepare("SELECT * FROM securitytokens WHERE identifier = ?");
      $result = $statement->execute(array($identifier));
      $securitytoken_row = $statement->fetch();

      if (sha1($securitytoken) !== $securitytoken_row['securitytoken']) {
        //The security token was probably stolen
        //If necessary, display a warning or similar here
      } else { //Token was correct
        //Set new token
        $neuer_securitytoken = myFunctions::random_string();
        $insert = $pdo->prepare("UPDATE securitytokens SET securitytoken = :securitytoken WHERE identifier = :identifier");
        $insert->execute(array('securitytoken' => sha1($neuer_securitytoken), 'identifier' => $identifier));
        setcookie("identifier", $identifier, time() + (3600 * 24 * 365));
        setcookie("securitytoken", $neuer_securitytoken, time() + (3600 * 24 * 365));
        //Log in the user
        $_SESSION['userid'] = $securitytoken_row['user_id'];
      }
    }
    if (!isset($_SESSION['userid'])) {
      return false;
    }
    $user = dbGetData::getUserById($_SESSION['userid']);
    return $user;
  }

  /**
  * Returns the user of the emailadress.
  * @param email adress of the user.
  * @return user as object
  */
  public function getUser($email)
  {
    if ($this->user === null) {
      $this->object = new self($email);
    }
    return $this->user;
  }

  /**
  * Returns the Id of the User.
  * @return id of the user
  */
  public function getId()
  {
    return $this->id;
  }

  /**
  * Returns the Email of the User.
  * @return email of the user
  */
  public function getEmail()
  {
    return $this->email;
  }

  /**
  * Returns the Firstname of the User.
  * @return firstname of the user
  */
  public function getFirstname()
  {
    return $this->firstname;
  }

  /**
  * Set the Firstname and the Lastname of the User.
  * @return firstname and Lastname of the user
  */
  public function setName($post)
  {
    $this->firstname = trim($post['firstname']);
    $this->lastname = trim($post['lastname']);

    $updateUserReturn = dbUpdateData::updateUserData($post, $this->id);
 	 	if (!$updateUserReturn) {
 			$error_msg = "Please enter first and last name.";
      return "Please enter first and last name.";
 	 	} else {
 		 	$success_msg = $updateUserReturn;
      return $updateUserReturn;
 	 	}
  }

  /**
  * Returns the Lastname of the User.
  * @return lastname of the user
  */
  public function getLastname()
  {
    return $this->lastname;
  }

  /**
  * Returns the UserGroupAdmin as Boolean of the User.
  * @return usergroup_admin of the user
  */
  public function getUserGroupAdmin()
  {
    return $this->usergroup_admin;
  }

  public function jsonSerialize()
  {
    return 
    [
      'userId'   => $this->getId(),
      'email' => $this->getEmail(),
      'Firstname' => $this->getFirstname(),
      'Lastname' => $this->getLastname()
    ];
  }

  public function getDashboardUpdateInterval() {
    return $this->dashboardUpdateInterval;
  }

  public function setDashboardUpdateInterval($post) {
    $this->dashboardUpdateInterval = $post['updateInterval'];
    $updateUserReturn = dbUpdateData::updateUserDashboardupdateInterval($post, $this->id);
 	 	if (!$updateUserReturn) {
 			$error_msg = "Please enter first and last name.";
      return "Please enter first and last name.";
 	 	} else {
 		 	$success_msg = $updateUserReturn;
      return $updateUserReturn;
 	 	}
  }
}
