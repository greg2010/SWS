<?php

interface IuserManagement {
    //function getUserCorpName();
    //function getUserAllianceName();
    function setNewPassword($password, $passwordRepeat);
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
    
    public function setNewEmail($email) {
        if ($this->id === -1) {
            throw new Exception("Object created in fake user mode. Can't change email.", 30);
        }
        $verify = new registerNewUser();
        $verify->testEmail($email);
        
        $query = "SELECT `email` FROM `users` WHERE `id` = '$this->id'";
        $oldEmail = $this->db->getMySQLResult($this->db->query($query));
        if ($email == $oldEmail) {
            throw new Exception("Email hasn't changed. Skipping...", -1);
        }
        try {
            $this->db->updateEmail($this->id, $email);
        } catch (Exception $ex) {
            throw new Exception("MySQL error: " . $ex->getMessage(), 30);
        }
    }

    public function setNewPassword($password, $passwordRepeat){
        if ($this->id === -1) {
            throw new Exception("Object created in fake user mode.", 30);
        }
        if ($passwordRepeat <> $password) {
            throw new Exception("Passwords don't match!", 11);
        }
        $verify = new registerNewUser();
        $verify->testPassword($password);
        try {
            $query = "SELECT `salt` FROM `users` WHERE `id` = '$this->id'";
            $salt = $this->db->getMySQLResult($this->db->query($query));
            $passwordHash = hash(config::password_hash_type, $password . $salt);
        } catch (Exception $ex) {
            throw new Exception("MySQL error: " . $ex->getMessage(), 30);
        }
        $query = "SELECT `passwordHash` FROM `users` WHERE `id` = '$this->id'";
        $oldPasswordHash = $this->db->getMySQLResult($this->db->query($query));
        if ($oldPasswordHash == $passwordHash) {
            throw new Exception("New password is the same as old password!", 11);
        }
        try {
            $query = "UPDATE `users` SET `passwordHash` = '$passwordHash' WHERE `id` = '$this->id'";
            $this->db->query($query);
        } catch (Exception $ex) {
            throw new Exception("MySQL error: " . $ex->getMessage(), 30);
        }
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