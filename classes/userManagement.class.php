<?php

interface IuserManagement {
    function getUserInfo();
    function getUserCorpName();
    function getUserAllianceName();
    function setNewPassword();
    function setUserInfo();
}

class userManagement implements IuserManagement {
    
    
    protected $id;
    protected $db;
    protected $permissions;
    protected $pilotInfo;
    public $log;

    public function __construct($id) {
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
            $this->log->put("pilotInfo", "selection success");
        } catch (Exception $ex) {
            $this->log->put("pilotInfo", "selection failed: " . $ex->getMessage());
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
            $this->log->put("apiList", "selection success");
            return $apiKey;
        } catch (Exception $ex) {
            $this->log->put("apiList", "selection failed: " . $ex->getMessage());
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
            $this->log->put("allowedList", "selection success");
            return $accessMask;
        } catch (Exception $ex) {
            $this->log->put("allowedList", "selection failed: " . $ex->getMessage());
        }
    }
    
    public function getUserInfo() {
        
    }
    
    public function getUserCorpName() {
        
    }
    
    public function getUserAllianceName(){
        
    }
    
    public function setNewPassword(){
        
    }
    
    public function setUserInfo() {
        
    }
}