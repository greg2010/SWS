<?php

interface IuserManagement {
    function getUserCorpName();
    function getUserAllianceName();
    function setNewPassword($password);
    function setUserInfo();
    function getCorporationTicker($id);
    function getAllianceTicker($id);
    function recordCorporationInfo($id, $name, $ticker);
    function recordAllianceInfo($id, $name, $ticker);
}

class userManagement implements IuserManagement {
    
    
    protected $id;
    protected $db;
    protected $permissions;
    protected $pilotInfo;
    public $log;

    public function __construct($id = NULL) {
        $this->db = db::getInstance();
        $this->log = new logging();
        $this->permissions = new permissions($id);
         if (!isset($id)) {
                $this->id = -1;
            } else {
                $this->id = $id;
                $this->getDbPilotInfo();
            }
    }

    private function getDbPilotInfo() {
        //Populates $dbPilotInfo
        try {
            $query = "SELECT * FROM `pilotInfo` WHERE `id` = '$this->id'";
            $result = $this->db->query($query);
            $this->pilotInfo = $this->db->fetchAssoc($result);
            return true;
        } catch (Exception $ex) {
            $this->log->put("getDbPilotInfo", "err " . $ex->getMessage());
            return false;
        }
    }
    
    public function getPilotInfo() {
        return $this->pilotInfo;
    }

    public function getApiKey($keyStatus) {
        try {
            $query = "SELECT `keyID`, `vCode`, `characterID` FROM `apiList` WHERE `id` = '$this->id' AND `keyStatus` = '$keyStatus'";
            $result = $this->db->query($query);
            $apiKey = $this->db->fetchRow($result);
            return $apiKey;
        } catch (Exception $ex) {
            $this->log->put("getApiKey", "err " . $ex->getMessage());
        }
    }
    
    public function getAllowedListMask($maskOwner = NULL) {
        try {
            if (isset($maskOwner)) {
                $characterID = $maskOwner[characterID];
                $corporationID = $maskOwner[corporationID];
                $allianceID = $maskOwner[allianceID];
            } else {
                $characterID = $this->pilotInfo[characterID];
                $corporationID = $this->pilotInfo[corporationID];
                $allianceID = $this->pilotInfo[allianceID];
            }
            $query = "SELECT `accessMask` FROM `allowedList` WHERE "
                    . "(`characterID` = '$characterID' AND `corporationID` IS NULL AND `allianceID` IS NULL)"
                    . " OR "
                    . "(`characterID` IS NULL AND `corporationID` = '$corporationID' AND `allianceID` IS NULL)"
                    . " OR "
                    . "(`characterID` IS NULL AND `corporationID` IS NULL AND `allianceID` = '$allianceID')"
                    . " OR "
                    . "(`characterID` = '$characterID' AND `corporationID` = '$corporationID' AND `allianceID` IS NULL)"
                    . " OR "
                    . "(`characterID` = '$characterID' AND `corporationID` IS NULL AND `allianceID` = '$allianceID')"
                    . " OR "
                    . "(`characterID` IS NULL AND `corporationID` = '$corporationID' AND `allianceID` = '$allianceID')"
                    . " OR "
                    . "(`characterID` = '$characterID' AND `corporationID` = '$corporationID' AND `allianceID` IS NULL)"
                    . " OR "
                    . "(`characterID` = '$characterID' AND `corporationID` = '$corporationID' AND `allianceID` = '$allianceID')";
            $result = $this->db->query($query);
            $userMasks = $this->db->fetchRow($result);
            foreach ($userMasks as $userMask) {
                $accessMask = $accessMask | $userMask;
            }
            if ($accessMask == '') {
                $accessMask = 0;
            }
            return $accessMask;
        } catch (Exception $ex) {
            $this->log->put("getAllowedListMask", "err " . $ex->getMessage());
        }
    }
    
    public function getUserName() {
        return $this->pilotInfo[characterName];
    }
    
    public function getUserCorpName() {
        return $this->pilotInfo[corporationName];
    }
    
    public function getUserAllianceName(){
        return $this->pilotInfo[allianceName];
    }
    
    public function deleteAPI() {
        if ($this->id === -1) {
            throw new Exception("Object created in fake user mode.", 30);
        }
        $query = "UPDATE `apilist` SET `keyStatus` = '0' WHERE `id` = $this->id AND keyStatus = '1'";
        $this->db->query($query);
        if ($this->db->affectedRows() < 1) {
            throw new Exception("No such key found.", 15);
        }
    }


    public function setNewPassword($password){
        if ($id<>-1) {
            
        } else {
            return "Object created in fake user mode. Can't change password.";
        }
    }
    
    public function setUserInfo() {
        
    }

    public function getCorporationTicker($id){
        $query = "SELECT `ticker` FROM `corporationList` WHERE `id` = '$id'";
        $result = $this->db->query($query);
        return $this->db->getMysqlResult($result);
    }
    
    public function getAllianceTicker($id){
        $query = "SELECT `ticker` FROM `allianceList` WHERE `id` = '$id'";
        $result = $this->db->query($query);
        return $this->db->getMysqlResult($result);
    }

    public function recordCorporationInfo($id, $name, $ticker){
        $query = "INSERT INTO `corporationList` SET `id` = '$id', `name` = '$name', `ticker` = '$ticker'";
        $result = $this->db->query($query);
    }
    
    public function recordAllianceInfo($id, $name, $ticker){
        $query = "INSERT INTO `allianceList` SET `id` = '$id', `name` = '$name', `ticker` = '$ticker'";
        $result = $this->db->query($query);
    }
}