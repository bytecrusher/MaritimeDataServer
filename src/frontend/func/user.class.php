<?php
/**
 * create an object of the current user as an object and provide some getter and setter functions
 * 
 * @author: Guntmar Hoeche
 * @license: TBD
 */
include_once("dbConfig.func.php");
include_once("dbGetData.php");
include_once("password.func.php");
require_once("writeToLogFunction.func.php");

class user implements JsonSerializable
{
  private $user = null;
  private $userobj = null;
  private $object;
  private static $pdo;
  private $error;

  /**
  * Method for construct the class.
  * @param email adress of the user
  */
  public function __construct($email)
  {
    try {
      self::$pdo = dbConfig::getInstance();
      $statement = self::$pdo->prepare("SELECT * FROM users WHERE email = :email");
      $result = $statement->execute(array('email' => $email));
      $this->userobj = $statement->fetch(PDO::FETCH_OBJ);
    }
    catch (PDOException $err) {
      //Handling query/error
      writeToLogFunction::write_to_log("errorcode: " . $err->getCode(), $_SERVER["SCRIPT_FILENAME"]);
      $this->error = $err->getCode();
    }
  }

  /**
   * Checks that the user is logged in.
   * @return Returns the row of the logged in user
   */
  public static function check_user()
  {
    if (!isset($_SESSION['userid']) && isset($_COOKIE['identifier']) && isset($_COOKIE['securitytoken'])) {
      $identifier = $_COOKIE['identifier'];
      $securitytoken = $_COOKIE['securitytoken'];
      $statement = self::$pdo->prepare("SELECT * FROM securitytokens WHERE identifier = ?");
      $result = $statement->execute(array($identifier));
      $securitytoken_row = $statement->fetch();

      if (sha1($securitytoken) !== $securitytoken_row['securitytoken']) {
        //The security token was probably stolen
        //If necessary, display a warning or similar here
      } else { //Token was correct
        //Set new token
        $neuer_securitytoken = myFunctions::random_string();
        $insert = self::$pdo->prepare("UPDATE securitytokens SET securitytoken = :securitytoken WHERE identifier = :identifier");
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
    // TODO change, to object oriented.
    $user = dbGetData::getUserById($_SESSION['userid']);
    return $user;
  }

/**
  * Returns true, if user is exist.
  * @return active as bool
  */
  public function userExist()
  {
    if ($this->userobj == false) {
      return false;
    } else {
      return true;
    }
  }

/**
  * Returns true, if user is active.
  * @return active as bool
  */
  public function isActive()
  {
    if ($this->userobj == false) {
      return false;
    } else {
      return $this->userobj->active;
    }
  }

  /**
  * Returns the user of the emailadress.
  * @param email adress of the user.
  * @return user as object
  */
  public function getUser($email)
  {
    trigger_error('Method ' . __METHOD__ . ' is deprecated', E_USER_DEPRECATED);
    if ($this->userobj === null) {
      $this->object = new self($email);
    }
    return $this->userobj;
  }

  /**
  * Returns the Id of the current User.
  * @return id of the user
  */
  public function getId()
  {
    return $this->userobj->id;
  }

  /**
  * Returns the Email of the current User.
  * @return email of the user
  */
  public function getEmail()
  {
    return $this->userobj->email;
  }

  /**
  * Returns the Firstname of the current User.
  * @return firstname of the user
  */
  public function getFirstname()
  {
    return $this->userobj->firstname;
  }

  /**
  * Returns the password of the current User.
  * @return password of the user
  */
  public function getPassword()
  {
    return $this->userobj->password;
  }

  /**
  * Set the Firstname and the Lastname of the current User.
  * @return firstname and Lastname of the user
  */
  public function setName($post)
  {
    try {
      $updateUserDataStatus = dbUpdateData::updateUserData($post, $this->userobj->id);
      $this->userobj->firstname = trim($post['firstname']);
      $this->userobj->lastname = trim($post['lastname']);
      return $updateUserDataStatus;
    } catch (Exception $e) {
      throw new Exception('User Data not saved.');
    }
  }

  /**
  * Set the setUserTimeZone of the current User.
  * @return Timezone of the user
  */
  public function setUserTimeZone($post)
  {
    try {
      $updateUserReturn = dbUpdateData::updateUserTimeZoneData($post, $this->userobj->id);
      $this->userobj->Timezone = $post['Timezone'];
      return $updateUserReturn;
    } catch (Exception $e) {
      throw new Exception('Timezone not saved.');
    }
  }

  /**
  * Set the Password of the current User.
  * @return Password of the user
  */
  public function setUserPassword($password_hash)
  {
    try {
      $updateUserReturn = dbUpdateData::updateUserPassword($password_hash, $this->userobj->id);
      $this->userobj->password = $password_hash;
      return $updateUserReturn;
    } catch (Exception $e) {
      throw new Exception('Password not saved.');
    }
  }

  /**
  * Set the Email of the current User.
  * @return firstname and Lastname of the user
  */
  public function setEmail($post)
  {
    try {
      $updateUserEmailReturn = dbUpdateData::updateUserMail($post, $this->userobj->id);
      $this->userobj->email = trim($post['email']);
      return $updateUserEmailReturn;
    } catch (Exception $e) {
      throw new Exception('Email not saved.');
    }
  }

  /**
  * Returns the Lastname of the current User.
  * @return lastname of the user
  */
  public function getLastname()
  {
    return $this->userobj->lastname;
  }

  /**
  * Returns the Lastname of the current User.
  * @return lastname of the user
  */
  public function getTimezone()
  {
    return $this->userobj->Timezone;
  }

  /**
  * Returns the UserGroupAdmin as Boolean of the User.
  * @return usergroup_admin of the user
  */
  public function getUserGroupAdmin()
  {
    return $this->userobj->usergroup_admin;
  }

  /**
  * Returns the Error.
  * @return Error
  */
  public function getError()
  {
    return $this->error;
  }

  /*
  * Get all of my Board by user id.
  * rename from getMyBoards to getMyBoardsId
  */
  public function getMyBoardsId() {
    if (!$this->userobj->id == null) {
      $pdo = dbConfig::getInstance();
      $myboards = $pdo->prepare("SELECT id FROM boardconfig WHERE owner_userid = " . $this->userobj->id . " ORDER BY id");
      $result = $myboards->execute();
      $myboards2 = $myboards->fetchAll(PDO::FETCH_ASSOC);
      return $myboards2;
    }
  }

  /*
  * Get all of my Board by user id.
  */
  public function getMyBoardsAll() {
    if (!$this->userobj->id == null) {
      $pdo = dbConfig::getInstance();
      $myboards = $pdo->prepare("SELECT * FROM boardconfig WHERE owner_userid = " . $this->userobj->id . " ORDER BY id");
      $result = $myboards->execute();
      $myboards2 = $myboards->fetchAll(PDO::FETCH_ASSOC);
      return $myboards2;
    }
  }

  /*
  * Get all Board (only for admin).
  */
  public function getAllBoardsAdmin() {
    //if (!$this->userobj->id == null) {
    if(($this->userobj->usergroup_admin == 1) ) {
      $pdo = dbConfig::getInstance();
      $myboards = $pdo->prepare("SELECT * FROM boardconfig WHERE 1 ORDER BY id");
      $result = $myboards->execute();
      $myboards2 = $myboards->fetchAll(PDO::FETCH_ASSOC);
      return $myboards2;
    }
  }

  public function jsonSerialize(): mixed
  {
    return 
    [
      'userId'   => $this->getId(),
      'email' => $this->getEmail(),
      'Firstname' => $this->getFirstname(),
      'Lastname' => $this->getLastname(),
      'Timezone' => $this->getTimezone()
    ];
  }

  public function getDashboardUpdateInterval() {
    return $this->userobj->dashboardUpdateInterval;
  }

  public function setDashboardUpdateInterval($post) {
    $this->userobj->dashboardUpdateInterval = $post['updateInterval'];
    $updateUserReturn = dbUpdateData::updateUserDashboardupdateInterval($post, $this->userobj->id);
 	 	if (!$updateUserReturn) {
 			$error_msg = "Please enter first and last name.";
      return "Please enter first and last name.";
 	 	} else {
 		 	$success_msg = $updateUserReturn;
      return $updateUserReturn;
 	 	}
  }
}