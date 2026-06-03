<?php
/**
 * create an object of the current user as an object and provide some getter and setter functions
 * 
 * @author: Guntmar Hoeche
 * @license: TBD
 */
require_once(__DIR__ . "/../../Infrastructure/Database/dbConfig.func.php");
require_once(__DIR__ . "/../../Infrastructure/Database/dbGetData.php");
require_once(__DIR__ . "/../../Support/password.func.php");
require_once(__DIR__ . "/../../Infrastructure/Logging/writeToLogFunction.func.php");
require_once(__DIR__ . "/../../Application/myFunctions.func.php");

class user implements JsonSerializable
{
  //private $user = null;
  private $userObj = null;
  private $object;
  private static $pdo;
  private $error;

  /**
  * Method for construct the class.
  * @param $email address of the user
  */
  public function __construct($email)
  {
    try {
      self::$pdo = dbConfig::getInstance();
      $statement = self::$pdo->prepare("SELECT * FROM users WHERE email = :email");
      $result = $statement->execute(array('email' => $email));
      $this->userObj = $statement->fetch(PDO::FETCH_OBJ);
      if ($this->userObj == false) {
        writeToLogFunction::write_to_log("email not found.", $_SERVER["SCRIPT_FILENAME"]);
        throw new Exception('email not found');
      }
    } catch (PDOException $err) {
      //Handling query/error
      writeToLogFunction::write_to_log("error code: " . $err->getCode(), $_SERVER["SCRIPT_FILENAME"]);
      $this->error = $err->getCode();
    }
  }

  /**
   * @deprecated Checks that the user is logged in.
   *
   * @return $user|null or False if user is not logged in
   * @throws Exception Return Exception message on error.
   * 
   */
  public static function check_user()
  {
    trigger_error('Method ' . __METHOD__ . ' is deprecated', E_USER_DEPRECATED);
    if (!isset($_SESSION['userId']) && isset($_COOKIE['identifier']) && isset($_COOKIE['securityToken'])) {
      $identifier = $_COOKIE['identifier'];
      $securityToken = $_COOKIE[''];
      try {
        $statement = self::$pdo->prepare("SELECT * FROM securityTokens WHERE identifier = ?");
        $result = $statement->execute(array($identifier));
        $securityToken_row = $statement->fetch();

        if (sha1($securityToken) !== $securityToken_row['securityToken']) {
          //The security token was probably stolen
          //If necessary, display a warning or similar here
        } else { //Token was correct
          //Set new token
          $neuer_securityToken = myFunctions::random_string();
          $insert = self::$pdo->prepare("UPDATE securityTokens SET securityToken = :securityToken WHERE identifier = :identifier");
          $insert->execute(array('securityToken' => sha1($neuer_securityToken), 'identifier' => $identifier));
          mds_set_remember_login_cookies($identifier, $neuer_securityToken);
          //Log in the user
          $_SESSION['userId'] = $securityToken_row['userId'];
        }
      } catch (PDOException $err) {
        writeToLogFunction::write_to_log("error code: " . $err->getCode(), $_SERVER["SCRIPT_FILENAME"]);
        //$this->error = $err->getCode();
      }
    }
    if (!isset($_SESSION['userId'])) {
      return false;
    }
    return dbGetData::getUserById($_SESSION['userId']);
  }

/**
  * Returns true, if user is exist.
  * @return bool as active
  */
  public function userExist()
  {
    if ($this->userObj == false) {
      return false;
    } else {
      return true;
    }
  }

/**
  * Returns true, if user is active.
  * @return bool as active
  */
  public function isActive()
  {
    if ($this->userObj == false) {
      return false;
    } else {
      return $this->userObj->active;
    }
  }

  /**
  * @deprecated Returns the user of the email address.
  * @param $email address of the user.
  * @return user as object
  */
  public function getUser($email)
  {
    trigger_error('Method ' . __METHOD__ . ' is deprecated', E_USER_DEPRECATED);
    if ($this->userObj === null) {
      $this->object = new self($email);
    }
    return $this->userObj;
  }

  /**
  * Returns the Id of the current User.
  * @return $id of the user
  */
  public function getId()
  {
    return $this->userObj->id;
  }

  /**
  * Returns the Email of the current User.
  * @return $email of the user
  */
  public function getEmail()
  {
    return $this->userObj->email;
  }

  /**
  * Returns the first name of the current User.
  * @return $firstName of the user
  */
  public function getFirstName()
  {
    return $this->userObj->firstName;
  }

  /**
  * Returns the password of the current User.
  * @return $password of the user
  */
  public function getPassword()
  {
    return $this->userObj->password;
  }

  /**
  * Set the first name and the last name of the current User.
  * @return $firstName and $lastName of the user
  */
  public function setName($post)
  {
    try {
      $updateUserDataStatus = dbUpdateData::updateUserData($post, $this->userObj->id);
      $this->userObj->firstName = trim($post['firstName']);
      $this->userObj->lastName = trim($post['lastName']);
      return $updateUserDataStatus;
    } catch (Exception $e) {
      throw new Exception('User Data not saved.');
    }
  }

  /**
  * Set the setUserTimeZone of the current User.
  * @return $Timezone of the user
  */
  public function setUserTimeZone($post)
  {
    try {
      $updateUserReturn = dbUpdateData::updateUserTimeZoneData($post, $this->userObj->id);
      $this->userObj->Timezone = $post['Timezone'];
      return $updateUserReturn;
    } catch (Exception $e) {
      throw new Exception('Timezone not saved.');
    }
  }

  public function setLanguage($post)
  {
    try {
      $language = mds_normalize_language($post['language'] ?? 'en');
      $updateUserReturn = dbUpdateData::updateUserLanguage($language, $this->userObj->id);
      $this->userObj->language = $language;
      mds_set_current_language($language);
      return $updateUserReturn;
    } catch (Exception $e) {
      throw new Exception('Language not saved.');
    }
  }

  /**
  * Set the Password of the current User.
  * @return $Password of the user
  */
  public function setUserPassword($password_hash)
  {
    try {
      $updateUserReturn = dbUpdateData::updateUserPassword($password_hash, $this->userObj->id);
      $this->userObj->password = $password_hash;
      return $updateUserReturn;
    } catch (Exception $e) {
      throw new Exception('Password not saved.');
    }
  }

  /**
  * Set the Email of the current User.
  * @return $updateUserEmailReturn
  */
  public function setEmail($post)
  {
    try {
      $updateUserEmailReturn = dbUpdateData::updateUserMail($post, $this->userObj->id);
      $this->userObj->email = trim($post['email']);
      return $updateUserEmailReturn;
    } catch (Exception $e) {
      throw new Exception('Email not saved.');
    }
  }

  /**
  * Returns the last name of the current User.
  * @return $lastName of the user
  */
  public function getLastName()
  {
    return $this->userObj->lastName;
  }

  /**
  * Returns the timezone of the current User.
  * @return $timezone of the user
  */
  public function getTimezone()
  {
    return $this->userObj->Timezone;
  }

  /**
  * Returns the UserGroupAdmin as Boolean of the User.
  * @return $userGroupAdmin of the user
  */
  public function getUserGroupAdmin()
  {
    return $this->userObj->userGroupAdmin;
  }

  /**
  * Returns the Error.
  * @return $Error
  */
  public function getError()
  {
    return $this->error;
  }

  /*
  * Get all of my Board by user id.
  */
  public function getMyBoardsAll() {
    if ($this->userObj->id !== null) {
      return dbGetData::getBoardsByOwnerId($this->userObj->id);
    }
    return array();
  }

  /*
  * Get all Board (only for admin).
  */
  public function getAllBoardsAdmin() {
    if(($this->userObj->userGroupAdmin == 1) ) {
      return dbGetData::getAllBoards();
    }
    return array();
  }

  public function jsonSerialize(): mixed
  {
    return 
    [
      'userId'   => $this->getId(),
      'email' => $this->getEmail(),
      'FirstName' => $this->getFirstName(),
      'LastName' => $this->getLastName(),
      'Timezone' => $this->getTimezone()
    ];
  }

  public function getDashboardUpdateInterval() {
    return $this->userObj->dashboardUpdateInterval;
  }

  public function setDashboardUpdateInterval($post) {
    try {
      $updateUserReturn = dbUpdateData::updateUserDashboardPreferences($post, $this->userObj->id);
      $this->userObj->dashboardUpdateInterval = (int)$post['updateInterval'];
      $this->userObj->dashboardOnlineOnly = isset($post['dashboardOnlineOnly']) ? 1 : 0;
      $this->userObj->preferredChartWindowDays = isset($post['preferredChartWindowDays']) ? (int)$post['preferredChartWindowDays'] : 7;
      $this->userObj->eventTimelineWindowHours = isset($post['eventTimelineWindowHours']) ? (int)$post['eventTimelineWindowHours'] : 24;
    } catch (Exception $e) {
      throw new Exception('Dashboard Update Interval not saved.');
    }
  }

  public function getReceiveNotifications() {
    return $this->userObj->receive_notifications;
  }

  public function setReceiveNotifications($post) {
    try {
      $updateUserReturn = dbUpdateData::updateUserReceiveNotifications($post, $this->userObj->id);
      $this->userObj->receive_notifications = isset($post['receiveNotifications']) ? (int)$post['receiveNotifications'] : 0;
      $this->userObj->receive_offline_notifications = isset($post['receiveOfflineNotifications']) ? (int)$post['receiveOfflineNotifications'] : 0;
      $this->userObj->receive_sensor_notifications = isset($post['receiveSensorNotifications']) ? (int)$post['receiveSensorNotifications'] : 0;
    } catch (Exception $e) {
      throw new Exception('Dashboard Update receiveNotifications not saved.');
    }
    //return $this->userObj->receive_notifications;
  }

  public function getReceiveOfflineNotifications() {
    if (!property_exists($this->userObj, 'receive_offline_notifications')) {
      return $this->userObj->receive_notifications ?? 0;
    }
    return $this->userObj->receive_offline_notifications;
  }

  public function getReceiveSensorNotifications() {
    if (!property_exists($this->userObj, 'receive_sensor_notifications')) {
      return $this->userObj->receive_notifications ?? 0;
    }
    return $this->userObj->receive_sensor_notifications;
  }

  public function getDashboardOnlineOnly() {
    if (!property_exists($this->userObj, 'dashboardOnlineOnly')) {
      return 0;
    }
    return $this->userObj->dashboardOnlineOnly;
  }

  public function getPreferredChartWindowDays() {
    if (!property_exists($this->userObj, 'preferredChartWindowDays')) {
      return 7;
    }
    return $this->userObj->preferredChartWindowDays;
  }

  public function getEventTimelineWindowHours() {
    if (!property_exists($this->userObj, 'eventTimelineWindowHours')) {
      return 24;
    }
    return $this->userObj->eventTimelineWindowHours;
  }

  public function getLanguage() {
    if (!property_exists($this->userObj, 'language')) {
      return 'en';
    }
    return mds_normalize_language($this->userObj->language);
  }
}
