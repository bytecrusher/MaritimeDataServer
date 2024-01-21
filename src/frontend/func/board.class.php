<?php
/**
 * Class for create a boards as an object and provide some getter and setter functions.
 * 
 * @author: Guntmar Hoeche
 * @license: TBD
 */
include_once("dbConfig.func.php");
include_once("dbGetData.php");

class board //implements JsonSerializable
{
    private $boardObj = null;
    
    /**
    * Method for construct the class.
    * @param $boardId id of the board
    */
    public function __construct($boardId)
    {
        $pdo = dbConfig::getInstance();
        $statement = $pdo->prepare("SELECT boardConfig.*, boardtype.name as boardTypeName FROM boardConfig LEFT JOIN boardtype ON boardtype.id = boardConfig.boardtypeid  WHERE boardConfig.id =?");
        $result = $statement->execute(array($boardId));
        $this->boardObj = $statement->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Returns the board id
     * 
     * @return $id
     */
    public function getId() {
        return $this->boardObj->id;
    }

    /**
     * Returns the boardObj
     * 
     * @return $boardObj
     */
    public function boardObj() {
        return $this->boardObj;
    }

    /**
     * Returns the board name
     * 
     * @return $name of the board
     */
    public function getName() {
        return $this->boardObj->name;
    }

    /**
     * Returns the board macAddress
     * 
     * @return $macAddress of the board
     */
    public function getMacAddress() {
        return $this->boardObj->macAddress;
    }

    /**
     * Returns the board location
     * 
     * @return $location of the board
     */
    public function getLocation() {
        return $this->boardObj->location;
    }

    /**
     * Returns the board description
     * 
     * @return $description of the board
     */
    public function getDescription() {
        return $this->boardObj->description;
    }

    /**
     * Returns the board performUpdate
     * 
     * @return bool $performUpdate of the board
     */
    public function getPerformUpdate() {
        return $this->boardObj->performUpdate;
    }

    /**
     * Returns the board firmwareVersion
     * 
     * @return $firmwareVersion of the board
     */
    public function getFirmwareVersion() {
        return $this->boardObj->firmwareVersion;
    }

    /**
     * Returns the board alarmOnUnavailable
     * 
     * @return $alarmOnUnavailable of the board
     */
    public function getAlarmOnUnavailable() {
        return $this->boardObj->alarmOnUnavailable;
    }

    /**
     * Returns the board updateDataTimer
     * 
     * @return $updateDataTimer of the board
     */
    public function getUpdateDataTimer() {
        return $this->boardObj->updateDataTimer;
    }

    /**
     * Returns the board ownerUserId
     * 
     * @return $ownerUserId of the board
     */
    public function getOwnerUserId() {
        return $this->boardObj->ownerUserId;
    }

    /**
     * Returns the board offlineDataTimer
     * 
     * @return $offlineDataTimer of the board
     */
    public function getOfflineDataTimer() {
        return $this->boardObj->offlineDataTimer;
    }

    /**
     * Returns the board boardTypeId
     * 
     * @return $boardTypeId of the board
     */
    public function getBoardTypeId() {
        return $this->boardObj->boardTypeId;
    }

    /**
     * Returns the board boardTypeName
     * 
     * @return $boardTypeName of the board
     */
    public function getBoardTypeName() {
        return $this->boardObj->boardTypeName;
    }

    /**
     * Returns the board ttn_app_id
     * 
     * @return $ttn_app_id of the board
     */
    public function getTtnAppId() {
        return $this->boardObj->ttn_app_id;
    }

    /**
     * Returns the board ttn_dev_id
     * 
     * @return $ttn_dev_id of the board
     */
    public function getTtnDevId() {
        return $this->boardObj->ttn_dev_id;
    }

    /**
     * Returns the board onDashboard
     * 
     * @return $onDashboard of the board
     */
    public function isOnDashboard() {
        return $this->boardObj->onDashboard;
    }

    /*
    * Get all sensors of the board with dashboard = true.
    */
    public function getAllSensorsOfBoardWithDashboardWithTypeName() {
        $pdo = dbConfig::getInstance();
        $mysensors2 = $pdo->prepare("SELECT sensorconfig.*, sensortypes.name as typename FROM sensorconfig, sensortypes WHERE boardid = ? AND typid = sensortypes.id AND onDashboard = 1 ORDER BY id");
        $mysensors2->execute(array($this->boardObj->id));
        $sensorsOfBoard = $mysensors2->fetchAll(PDO::FETCH_ASSOC);
        return $sensorsOfBoard;
    }
}