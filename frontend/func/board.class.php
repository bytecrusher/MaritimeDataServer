<?php
/**
 * Class for creade a boards as an object and provide some getter and setter functions.
 * 
 * @author: Guntmar Hoeche
 * @license: TBD
 */
include_once("dbConfig.func.php");
include_once("dbGetData.php");

class board //implements JsonSerializable
{
    private $boardobj = null;
    
    /**
    * Method for construct the class.
    * @param boardid id of the baord
    */
    public function __construct($boardId)
    {
        $pdo = dbConfig::getInstance();
        $statement = $pdo->prepare("SELECT * FROM boardconfig WHERE id = ?");
        $result = $statement->execute(array($boardId));
        $this->boardobj = $statement->fetch(PDO::FETCH_OBJ);
        //var_dump(gettype($this->boardobj));
    }

    /**
     * Returns the board id
     * 
     * @return id
     */
    public function getId() {
        return $this->boardobj->id;
    }

    /**
     * Returns the boardobj
     * 
     * @return boardobj
     */
    public function boardobj() {
        return $this->boardobj;
    }

    /**
     * Returns the board name
     * 
     * @return name of the board
     */
    public function getName() {
        return $this->boardobj->name;
    }

    /**
     * Returns the board macaddress
     * 
     * @return macaddress of the board
     */
    public function getMacaddress() {
        return $this->boardobj->macaddress;
    }

    /**
     * Returns the board location
     * 
     * @return location of the board
     */
    public function getLocation() {
        return $this->boardobj->location;
    }

    /**
     * Returns the board description
     * 
     * @return description of the board
     */
    public function getDescription() {
        return $this->boardobj->description;
    }

    /**
     * Returns the board performupdate
     * 
     * @return performupdate of the board
     */
    public function getPerformUpdate() {
        return $this->boardobj->performupdate;
    }

    /**
     * Returns the board firmwareversion
     * 
     * @return firmwareversion of the board
     */
    public function getFirmwareversion() {
        return $this->boardobj->firmwareversion;
    }

    /**
     * Returns the board alarmOnUnavailable
     * 
     * @return alarmOnUnavailable of the board
     */
    public function getAlarmOnUnavailable() {
        return $this->boardobj->alarmOnUnavailable;
    }

    /**
     * Returns the board updateDataTimer
     * 
     * @return updateDataTimer of the board
     */
    public function getUpdateDataTimer() {
        return $this->boardobj->updateDataTimer;
    }

    /**
     * Returns the board boardtypeid
     * 
     * @return boardtypeid of the board
     */
    // TODO extend to return the boardtypename
    public function getBoardtypeId() {
        return $this->boardobj->boardtypeid;
    }

    /**
     * Returns the board ttn_app_id
     * 
     * @return ttn_app_id of the board
     */
    public function getTtnAppId() {
        return $this->boardobj->ttn_app_id;
    }

    /**
     * Returns the board ttn_dev_id
     * 
     * @return ttn_dev_id of the board
     */
    public function getTtnDevId() {
        return $this->boardobj->ttn_dev_id;
    }

    /**
     * Returns the board onDashboard
     * 
     * @return onDashboard of the board
     */
    public function isOnDashboard() {
        return $this->boardobj->onDashboard;
    }

    /*
    * Get all sensors of the board with dashboard = true.
    */
    public function getAllSensorsOfBoardWithDashboardWithTypeName() {
        $pdo = dbConfig::getInstance();
        //$mysensors2 = $pdo->prepare("SELECT *, typeid as sensortypes.name FROM sensorconfig, sensortypes WHERE boardid = ? AND typid = sensortypes.name AND onDashboard = 1 ORDER BY id");
        $mysensors2 = $pdo->prepare("SELECT sensorconfig.*, sensortypes.name as typename FROM sensorconfig, sensortypes WHERE boardid = ? AND typid = sensortypes.id AND onDashboard = 1 ORDER BY id");
        $mysensors2->execute(array($this->boardobj->id));
        $sensorsOfBoard = $mysensors2->fetchAll(PDO::FETCH_ASSOC);
        return $sensorsOfBoard;
    }
}